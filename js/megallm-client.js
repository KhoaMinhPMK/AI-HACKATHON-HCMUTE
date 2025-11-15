/**
 * Victoria AI - MegaLLM API Client
 * Integration with MegaLLM for AI-powered research assistance
 * API Key: sk-mega-a871069e3800ca98042da57b6a019814e9bd173a42a5870412b88895d52eea5e
 */

class MegaLLMClient {
    constructor() {
        this.apiKey = 'sk-mega-a871069e3800ca98042da57b6a019814e9bd173a42a5870412b88895d52eea5e';
        this.baseURL = 'https://ai.megallm.io/v1';
        this.defaultModel = 'gpt-5';
    }
    
    /**
     * Chat completion with any model
     * @param {Array} messages - Array of {role, content}
     * @param {string} model - Model name (default: gpt-5)
     * @param {Object} options - Additional options
     * @returns {Promise<Object>}
     */
    async chat(messages, model = null, options = {}) {
        try {
            const response = await fetch(`${this.baseURL}/chat/completions`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.apiKey}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    model: model || this.defaultModel,
                    messages: messages,
                    temperature: options.temperature ?? 0.7,
                    max_tokens: options.maxTokens ?? 2000,
                    ...options
                })
            });
            
            if (!response.ok) {
                const error = await response.text();
                console.warn('MegaLLM API error:', response.status, error);
                
                // Return fallback response for 402 (Payment Required) or other errors
                if (response.status === 402) {
                    console.warn('⚠️ MegaLLM API: Payment required - using fallback mode');
                }
                
                // Return a fallback response instead of throwing
                return this._getFallbackResponse(messages);
            }
            
            return await response.json();
            
        } catch (error) {
            console.error('MegaLLM chat error:', error);
            // Return fallback instead of throwing
            return this._getFallbackResponse(messages);
        }
    }
    
    /**
     * Fallback response when API is unavailable
     * @private
     */
    _getFallbackResponse(messages) {
        const lastMessage = messages[messages.length - 1]?.content || '';
        
        return {
            choices: [{
                message: {
                    role: 'assistant',
                    content: this._generateFallbackContent(lastMessage)
                }
            }],
            usage: { total_tokens: 0 },
            _fallback: true
        };
    }
    
    /**
     * Generate simple fallback content based on query
     * @private
     */
    _generateFallbackContent(query) {
        // Simple keyword extraction for fallback mode
        const keywords = query.toLowerCase()
            .split(/[,;.\s]+/)
            .filter(w => w.length > 3)
            .slice(0, 5);
        
        return JSON.stringify({
            terms: keywords,
            field: 'General',
            intent: 'search for: ' + query.substring(0, 100),
            suggested_queries: keywords.slice(0, 3)
        });
    }
    
    /**
     * Understand search query và extract terms
     * @param {string} query - User search query
     * @returns {Promise<Object>} {terms: [], field: '', intent: ''}
     */
    async understandQuery(query) {
        const messages = [
            {
                role: 'system',
                content: `You are a research assistant. Extract search terms from user query.
Return JSON format:
{
  "terms": ["term1", "term2"],
  "field": "Computer Science",
  "intent": "find papers about X",
  "suggested_queries": ["query1", "query2"]
}`
            },
            {
                role: 'user',
                content: query
            }
        ];
        
        const response = await this.chat(messages, 'gpt-5', { 
            temperature: 0.3,
            maxTokens: 500 
        });
        
        try {
            const content = response.choices[0].message.content;
            return JSON.parse(content);
        } catch (e) {
            console.error('Parse error:', e);
            return {
                terms: [query],
                field: 'unknown',
                intent: query
            };
        }
    }
    
    /**
     * Analyze topic và papers
     * @param {string} query - Research topic
     * @param {Array} papers - Papers found
     * @returns {Promise<Object>} Analysis
     */
    async analyzeTopic(query, papers) {
        const papersText = papers.slice(0, 10).map((p, i) => 
            `${i+1}. "${p.title}" by ${p.authors} (${p.year}) - ${p.citations} citations`
        ).join('\n');
        
        const messages = [
            {
                role: 'system',
                content: `You are an expert research advisor. Analyze research topic and papers.
Provide:
1. Who already researched this topic?
2. Common mistakes/failed approaches
3. Is this idea novel or already done?
4. Warnings about wrong directions
5. Suggestions for better approaches

Return in Vietnamese, be specific and actionable.`
            },
            {
                role: 'user',
                content: `Topic: "${query}"\n\nTop papers found:\n${papersText}\n\nProvide comprehensive analysis.`
            }
        ];
        
        const response = await this.chat(messages, 'claude-opus-4-1-20250805', {
            temperature: 0.4,
            maxTokens: 3000
        });
        
        return response.choices[0].message.content;
    }
    
    /**
     * Generate progress report for student
     * @param {Object} data - {searches, papers, activities}
     * @returns {Promise<Object>} Structured report
     */
    async generateProgressReport(data) {
        const { searches, papers, activities, studentName, period } = data;
        
        const prompt = this.buildReportPrompt(searches, papers, activities, studentName, period);
        
        const messages = [
            {
                role: 'system',
                content: `You are an AI research supervisor analyzing student progress.
Generate a comprehensive report in JSON format with these fields:
{
  "summary": "Overall assessment",
  "research_focus": "What student is focusing on",
  "strengths": ["strength1", "strength2"],
  "concerns": [{"severity": "high/medium/low", "issue": "", "recommendation": ""}],
  "knowledge_gaps": [{"gap": "", "priority": "high/medium/low"}],
  "warnings": [{"type": "", "message": "", "action": ""}],
  "must_read_papers": [{"title": "", "reason": "", "priority": ""}],
  "progress_score": 0-100,
  "next_steps": ["step1", "step2"]
}

Be specific, actionable, and supportive in Vietnamese.`
            },
            {
                role: 'user',
                content: prompt
            }
        ];
        
        const response = await this.chat(messages, 'claude-opus-4-1-20250805', {
            temperature: 0.3,
            maxTokens: 4000
        });
        
        try {
            const content = response.choices[0].message.content;
            // Extract JSON from response (might have markdown code blocks)
            const jsonMatch = content.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                return JSON.parse(jsonMatch[0]);
            }
            return JSON.parse(content);
        } catch (e) {
            console.error('Parse report error:', e);
            return {
                summary: content,
                error: 'Could not parse structured report'
            };
        }
    }
    
    /**
     * Build comprehensive prompt for report generation
     */
    buildReportPrompt(searches, papers, activities, studentName, period) {
        let prompt = `Analyze research activities of student: ${studentName}\n`;
        prompt += `Period: ${period}\n\n`;
        
        prompt += `=== SEARCH HISTORY (${searches.length} searches) ===\n`;
        searches.forEach((s, i) => {
            prompt += `${i+1}. "${s.query}" - ${s.created_at}\n`;
        });
        
        prompt += `\n=== PAPERS VIEWED/SAVED (${papers.length} papers) ===\n`;
        papers.forEach((p, i) => {
            prompt += `${i+1}. "${p.paper_title}"\n`;
            prompt += `   Type: ${p.interaction_type}, Time spent: ${p.time_spent}s\n`;
        });
        
        prompt += `\n=== ACTIVITY STATS ===\n`;
        prompt += `- Total searches: ${searches.length}\n`;
        prompt += `- Total papers: ${papers.length}\n`;
        prompt += `- Avg time per paper: ${this.calculateAvgTime(papers)}s\n`;
        
        prompt += `\n=== PROVIDE COMPREHENSIVE ANALYSIS ===\n`;
        
        return prompt;
    }
    
    /**
     * Calculate average time spent
     */
    calculateAvgTime(papers) {
        if (papers.length === 0) return 0;
        const total = papers.reduce((sum, p) => sum + (p.time_spent || 0), 0);
        return Math.round(total / papers.length);
    }
    
    /**
     * Suggest cover letter for application
     * @param {Object} data - {studentProfile, projectDetails}
     * @returns {Promise<string>} Suggested cover letter
     */
    async suggestCoverLetter(data) {
        const { studentProfile, projectDetails } = data;
        
        const messages = [
            {
                role: 'system',
                content: 'You are a career advisor. Write a professional cover letter for research project application.'
            },
            {
                role: 'user',
                content: `Student: ${JSON.stringify(studentProfile)}\nProject: ${JSON.stringify(projectDetails)}\n\nWrite cover letter in Vietnamese.`
            }
        ];
        
        const response = await this.chat(messages, 'gpt-5', {
            temperature: 0.7,
            maxTokens: 800
        });
        
        return response.choices[0].message.content;
    }
}

// Export singleton instance
export const megallm = new MegaLLMClient();
export default MegaLLMClient;

console.log('🤖 MegaLLM Client loaded');

