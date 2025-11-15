/**
 * Groq API Client for Reasoning Models
 * Using gpt-oss-120b for advanced thinking
 */

class GroqClient {
    constructor(apiKey) {
        this.apiKey = apiKey;
        this.baseURL = 'https://api.groq.com/openai/v1/chat/completions';
        this.model = 'openai/gpt-oss-120b'; // Advanced reasoning model
    }

    /**
     * Send chat completion request with reasoning
     * @param {Array} messages - Array of {role, content}
     * @param {Object} options - Additional options
     * @returns {Promise<Object>} Response with content and reasoning
     */
    async chat(messages, options = {}) {
        const payload = {
            model: this.model,
            messages: messages,
            temperature: options.temperature || 0.6,
            max_completion_tokens: options.max_tokens || 2048,
            top_p: options.top_p || 0.95,
            stream: options.stream || false,
            reasoning_effort: options.reasoning_effort || 'high', // low, medium, high
            include_reasoning: options.include_reasoning !== false // default true
        };

        try {
            const response = await fetch(this.baseURL, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.apiKey}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(`Groq API error: ${response.status} ${JSON.stringify(error)}`);
            }

            const data = await response.json();
            
            // Extract content and reasoning
            const choice = data.choices[0];
            const result = {
                content: choice.message.content,
                reasoning: choice.message.reasoning || null,
                model: data.model,
                usage: data.usage,
                finish_reason: choice.finish_reason
            };

            console.log('✅ Groq API response:', {
                model: result.model,
                tokens: result.usage,
                reasoning_length: result.reasoning?.length || 0
            });

            return result;

        } catch (error) {
            console.error('❌ Groq API error:', error);
            throw error;
        }
    }

    /**
     * Stream chat completion (for real-time responses)
     * @param {Array} messages 
     * @param {Function} onChunk - Callback for each chunk
     * @param {Object} options 
     */
    async chatStream(messages, onChunk, options = {}) {
        const payload = {
            model: this.model,
            messages: messages,
            temperature: options.temperature || 0.6,
            max_completion_tokens: options.max_tokens || 2048,
            top_p: 0.95,
            stream: true,
            reasoning_effort: options.reasoning_effort || 'high',
            include_reasoning: options.include_reasoning !== false
        };

        try {
            const response = await fetch(this.baseURL, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.apiKey}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error(`Groq API error: ${response.status}`);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';

                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        const data = line.slice(6);
                        if (data === '[DONE]') continue;

                        try {
                            const parsed = JSON.parse(data);
                            const delta = parsed.choices[0]?.delta;
                            
                            if (delta?.content) {
                                onChunk({
                                    type: 'content',
                                    text: delta.content
                                });
                            }

                            if (delta?.reasoning) {
                                onChunk({
                                    type: 'reasoning',
                                    text: delta.reasoning
                                });
                            }
                        } catch (e) {
                            console.warn('Failed to parse SSE chunk:', e);
                        }
                    }
                }
            }

            onChunk({ type: 'done' });

        } catch (error) {
            console.error('❌ Groq stream error:', error);
            throw error;
        }
    }

    /**
     * Generate research advice with deep reasoning
     * @param {string} topic - Research topic
     * @param {string} context - Additional context
     * @returns {Promise<Object>}
     */
    async getResearchAdvice(topic, context = '') {
        const messages = [
            {
                role: 'user',
                content: `Bạn là chuyên gia tư vấn nghiên cứu khoa học. Hãy phân tích và đề xuất hướng nghiên cứu cho đề tài sau:

**Đề tài:** ${topic}

${context ? `**Thông tin thêm:** ${context}` : ''}

Hãy đưa ra:
1. 📊 Phân tích tổng quan về chủ đề (xu hướng, thách thức, cơ hội)
2. 🎯 3-5 hướng nghiên cứu cụ thể có thể thực hiện
3. 📚 Các phương pháp nghiên cứu phù hợp
4. ⚠️ Những lưu ý, khó khăn tiềm ẩn
5. 💡 Gợi ý tài liệu, công cụ, dataset

Trả lời bằng tiếng Việt, rõ ràng, có cấu trúc.`
            }
        ];

        return await this.chat(messages, {
            temperature: 0.7,
            max_tokens: 3000,
            reasoning_effort: 'high'
        });
    }

    /**
     * Analyze papers and generate insights
     * @param {string} query - Search query
     * @param {Array} papers - List of papers
     * @returns {Promise<Object>}
     */
    async analyzePapers(query, papers) {
        const paperSummaries = papers.slice(0, 5).map((p, i) => 
            `${i+1}. "${p.title}" (${p.year}) - ${p.citationCount} citations\n   ${p.abstract.slice(0, 200)}...`
        ).join('\n\n');

        const messages = [
            {
                role: 'user',
                content: `Phân tích các bài báo khoa học liên quan đến: "${query}"

**Top 5 bài báo:**
${paperSummaries}

Hãy đưa ra:
1. 🔍 Tổng quan xu hướng nghiên cứu
2. 📈 Những tiến bộ chính trong lĩnh vực
3. 🎯 Gap còn thiếu, hướng nghiên cứu tiềm năng
4. 💡 Gợi ý cho người muốn nghiên cứu chủ đề này

Trả lời ngắn gọn, súc tích, bằng tiếng Việt.`
            }
        ];

        return await this.chat(messages, {
            temperature: 0.6,
            max_tokens: 1500,
            reasoning_effort: 'medium'
        });
    }
}

// Create singleton instance with API key from config
// Make sure to load config.js before this file
const groqClient = typeof API_CONFIG !== 'undefined' && API_CONFIG.GROQ_API_KEY 
    ? new GroqClient(API_CONFIG.GROQ_API_KEY)
    : null;

if (!groqClient) {
    console.warn('⚠️ Groq API key not configured. Please set API_CONFIG.GROQ_API_KEY in config.js');
}

console.log('🤖 Groq Client loaded with gpt-oss-120b (thinking model)');

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { GroqClient, groqClient };
}
