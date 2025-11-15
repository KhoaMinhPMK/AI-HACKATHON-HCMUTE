# 🤖 Victoria AI - Intelligent Search System Plan

## 🎯 **Vision: AI-Powered Research Discovery**

### **Mô Tả:**
Trang feed giống **Facebook** nhưng cho **nghiên cứu khoa học**:
- Sinh viên nhập chủ đề (VD: "Machine Learning trong Y tế")
- AI tìm kiếm và hiển thị:
  - 📄 **Bài báo khoa học** (từ Google Scholar, arXiv, PubMed)
  - 💼 **Đề tài tuyển thành viên** (từ database nội bộ)
- AI Chatbot phân tích:
  - ✅ Ai đã làm chủ đề này?
  - ⚠️ Có phải hướng sai của người đi trước?
  - 💡 Ý tưởng mới hay đã có người làm?
  - 🎯 Gợi ý hướng nghiên cứu tốt hơn

---

## 🏗️ **Architecture**

```
┌─────────────────────────────────────────────┐
│        Student Dashboard (Feed Style)       │
│                                             │
│  🔍 [Search: "Machine Learning in ___"]    │
│      ↓ (Enter)                              │
│      ↓                                      │
│  ┌─────────────────────────────┐           │
│  │   AI Search Engine          │           │
│  │   - MegaLLM GPT-5           │           │
│  │   - Semantic search         │           │
│  │   - Query understanding     │           │
│  └─────────────────────────────┘           │
│      ↓                                      │
│  ┌───────────┬─────────────┐               │
│  │ Papers API│ Projects DB │               │
│  │ (External)│ (Internal)  │               │
│  └─────┬─────┴──────┬──────┘               │
│        │            │                       │
│        ↓            ↓                       │
│  ┌─────────────────────────────┐           │
│  │  Mixed Results (Feed)       │           │
│  │  - Paper Card 📄            │           │
│  │  - Project Card 💼          │           │
│  │  - Paper Card 📄            │           │
│  │  - AI Analysis 🤖           │           │
│  │  - Project Card 💼          │           │
│  └─────────────────────────────┘           │
│                                             │
│  💬 AI Chatbot:                             │
│  "Chủ đề này đã có 150 nghiên cứu...       │
│   Phương pháp X đã được thử nhưng...       │
│   Gợi ý: Hãy thử approach Y..."            │
└─────────────────────────────────────────────┘
```

---

## 🔧 **Technical Stack**

### **AI/ML:**
- **MegaLLM API** (https://docs.megallm.io)
  - Model: GPT-5 (reasoning + search)
  - Model: Claude Opus 4.1 (analysis)
  - API Key: `sk-mega-a871069e...`

### **Paper Sources:**
- **Google Scholar API** (via SerpAPI hoặc scraping)
- **arXiv API** (free, no key needed)
- **PubMed API** (free, medical papers)
- **Semantic Scholar API** (free, 200M+ papers)

### **Frontend:**
- Feed-style layout (infinite scroll)
- Card components (Paper, Project, AI Insight)
- Search bar với AI autocomplete
- Apply modal

### **Backend:**
- Cache search results (Redis/MySQL)
- Rate limiting
- Paper metadata storage

---

## 📋 **Detailed Implementation Plan**

### **Phase 1: AI Integration (Priority 🔥)**

#### **1.1. MegaLLM API Client**
File: `js/megallm-client.js`

```javascript
class MegaLLMClient {
    constructor(apiKey) {
        this.apiKey = apiKey;
        this.baseURL = 'https://ai.megallm.io/v1';
    }
    
    async searchPapers(query) {
        // GPT-5 để understand query và generate search terms
        const response = await fetch(`${this.baseURL}/chat/completions`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.apiKey}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                model: 'gpt-5',
                messages: [{
                    role: 'system',
                    content: 'You are a research assistant. Extract search terms from user query.'
                }, {
                    role: 'user',
                    content: query
                }]
            })
        });
        
        return response.json();
    }
    
    async analyzeTopic(query, papers) {
        // Claude Opus để analysis sâu
        const response = await fetch(`${this.baseURL}/chat/completions`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.apiKey}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                model: 'claude-opus-4-1-20250805',
                messages: [{
                    role: 'system',
                    content: 'Analyze research topic and papers. Identify: who did this, common mistakes, novel ideas.'
                }, {
                    role: 'user',
                    content: `Topic: ${query}\nPapers: ${JSON.stringify(papers)}`
                }]
            })
        });
        
        return response.json();
    }
}
```

#### **1.2. Papers API Integration**
File: `js/papers-api.js`

```javascript
// arXiv API (free, no key)
async function searchArXiv(query, maxResults = 10) {
    const url = `http://export.arxiv.org/api/query?search_query=all:${encodeURIComponent(query)}&max_results=${maxResults}`;
    // Parse XML response
}

// Semantic Scholar API (free, 200M papers)
async function searchSemanticScholar(query, limit = 10) {
    const url = `https://api.semanticscholar.org/graph/v1/paper/search?query=${encodeURIComponent(query)}&limit=${limit}`;
    // Returns: title, abstract, authors, year, citations, thumbnail
}

// PubMed API (medical papers)
async function searchPubMed(query, maxResults = 10) {
    // Esearch API
}
```

---

### **Phase 2: Feed UI (Priority 🔥)**

#### **2.1. Feed Layout**
File: `pages/dashboard/student/feed.html` hoặc update `index.html`

```html
<!-- Search Bar -->
<div class="search-container">
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Tìm kiếm: chủ đề, từ khóa, tên tác giả..." 
               id="aiSearchInput">
        <button class="btn-search">
            <i class="fas fa-magic"></i> AI Search
        </button>
    </div>
    <div class="search-suggestions" id="suggestions">
        <!-- AI autocomplete suggestions -->
    </div>
</div>

<!-- Feed Container -->
<div class="feed-container">
    <!-- Filter Bar -->
    <div class="feed-filters">
        <button class="filter-btn active">Tất Cả</button>
        <button class="filter-btn">Bài Báo</button>
        <button class="filter-btn">Đề Tài</button>
        <button class="filter-btn">Giảng Viên</button>
    </div>
    
    <!-- AI Analysis Card (Xuất hiện sau search) -->
    <div class="ai-insight-card" id="aiInsight" style="display: none;">
        <div class="ai-avatar">
            <i class="fas fa-robot"></i>
        </div>
        <div class="ai-content">
            <h3>🤖 Victoria AI Analysis</h3>
            <div id="aiAnalysisText">
                <!-- AI generated analysis -->
            </div>
            <div class="ai-tags">
                <span class="tag">150 nghiên cứu liên quan</span>
                <span class="tag">Xu hướng mới</span>
                <span class="tag">⚠️ Lưu ý phương pháp X</span>
            </div>
        </div>
    </div>
    
    <!-- Feed Items (Mixed: Papers + Projects) -->
    <div class="feed-items" id="feedItems">
        <!-- Paper Card -->
        <div class="feed-card paper-card">
            <div class="card-thumbnail">
                <img src="paper-thumb.jpg" alt="">
                <span class="card-type">📄 Paper</span>
            </div>
            <div class="card-content">
                <h3>Deep Learning for Medical Image Analysis</h3>
                <div class="card-meta">
                    <span>👤 John Doe et al.</span>
                    <span>📅 2024</span>
                    <span>⭐ 150 citations</span>
                </div>
                <p class="card-desc">
                    Abstract excerpt...
                </p>
                <div class="card-tags">
                    <span class="tag">Deep Learning</span>
                    <span class="tag">Medical Imaging</span>
                </div>
                <div class="card-actions">
                    <button class="btn-sm">Đọc</button>
                    <button class="btn-sm">💾 Lưu</button>
                    <button class="btn-sm">📋 Trích dẫn</button>
                </div>
            </div>
        </div>
        
        <!-- Project Card (Job Posting Style) -->
        <div class="feed-card project-card">
            <div class="card-header">
                <img src="lecturer-avatar.jpg" class="lecturer-avatar">
                <div>
                    <h4>TS. Trần Thị B</h4>
                    <p>Đại học Bách Khoa TP.HCM</p>
                </div>
                <span class="hiring-badge">🔥 Đang tuyển</span>
            </div>
            <h3>Nghiên cứu AI trong Y tế</h3>
            <div class="card-meta">
                <span>👥 2/3 slots</span>
                <span>⏰ 6 tháng</span>
                <span>📅 2 ngày trước</span>
            </div>
            <p class="card-desc">Mô tả đề tài...</p>
            <div class="card-tags">
                <span class="tag">AI</span>
                <span class="tag">Healthcare</span>
            </div>
            <div class="card-actions">
                <button class="btn-primary btn-sm">
                    ✈️ Apply Ngay
                </button>
                <button class="btn-outline btn-sm">Chi Tiết</button>
            </div>
        </div>
    </div>
    
    <!-- Load More -->
    <div class="load-more">
        <button class="btn-outline">Xem Thêm</button>
    </div>
</div>

<!-- Apply Modal -->
<div class="modal" id="applyModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Apply to Project</h2>
            <button class="modal-close">&times;</button>
        </div>
        <form id="applyForm">
            <div class="form-group">
                <label>Cover Letter</label>
                <textarea rows="8" required></textarea>
            </div>
            <button type="submit" class="btn-primary">Gửi Đơn</button>
        </form>
    </div>
</div>
```

---

### **Phase 3: Backend Integration**

#### **3.1. MegaLLM Service**
File: `php/services/megallm-service.php`

```php
<?php
class MegaLLMService {
    private $apiKey = 'sk-mega-a871069e3800ca98042da57b6a019814e9bd173a42a5870412b88895d52eea5e';
    private $baseURL = 'https://ai.megallm.io/v1';
    
    public function searchPapers($query) {
        // Step 1: AI understand query
        $searchTerms = $this->extractSearchTerms($query);
        
        // Step 2: Search papers
        $papers = $this->queryPaperAPIs($searchTerms);
        
        // Step 3: AI analyze results
        $analysis = $this->analyzeResults($query, $papers);
        
        return [
            'query' => $query,
            'search_terms' => $searchTerms,
            'papers' => $papers,
            'analysis' => $analysis
        ];
    }
    
    private function extractSearchTerms($query) {
        $response = $this->callMegaLLM('gpt-5', [
            'role' => 'system',
            'content' => 'Extract academic search terms from user query. Return JSON: {terms: [], field: ""}'
        ], [
            'role' => 'user',
            'content' => $query
        ]);
        
        return json_decode($response['choices'][0]['message']['content'], true);
    }
    
    private function analyzeResults($query, $papers) {
        $prompt = "Analyze research topic: '$query'\n\n";
        $prompt .= "Found papers: " . json_encode(array_slice($papers, 0, 5)) . "\n\n";
        $prompt .= "Provide:\n";
        $prompt .= "1. Who already researched this?\n";
        $prompt .= "2. Common mistakes/failed approaches\n";
        $prompt .= "3. Is this idea novel or already done?\n";
        $prompt .= "4. Suggestions for better directions\n";
        
        $response = $this->callMegaLLM('claude-opus-4-1-20250805', [
            'role' => 'system',
            'content' => 'You are an expert research advisor.'
        ], [
            'role' => 'user',
            'content' => $prompt
        ]);
        
        return $response['choices'][0]['message']['content'];
    }
    
    private function callMegaLLM($model, ...$messages) {
        $ch = curl_init($this->baseURL . '/chat/completions');
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'messages' => $messages
            ])
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
```

#### **3.2. Papers Search API**
File: `php/api/search/papers.php`

```php
<?php
// Search papers from multiple sources
// GET /api/search/papers.php?q=machine+learning&limit=20

require_once '../../services/megallm-service.php';
require_once '../../services/papers-api.php';

$query = $_GET['q'] ?? '';
$limit = (int)($_GET['limit'] ?? 20);

// Step 1: AI understand query
$megallm = new MegaLLMService();
$searchTerms = $megallm->extractSearchTerms($query);

// Step 2: Search papers from multiple sources
$papersAPI = new PapersAPI();
$results = [
    'arxiv' => $papersAPI->searchArXiv($searchTerms, $limit),
    'semantic_scholar' => $papersAPI->searchSemanticScholar($searchTerms, $limit),
    'pubmed' => $papersAPI->searchPubMed($searchTerms, $limit)
];

// Step 3: Merge and rank
$papers = $papersAPI->mergeAndRank($results);

// Step 4: Get thumbnails
$papers = $papersAPI->addThumbnails($papers);

// Step 5: AI analysis
$analysis = $megallm->analyzeResults($query, $papers);

echo json_encode([
    'success' => true,
    'query' => $query,
    'results' => [
        'papers' => $papers,
        'analysis' => $analysis,
        'total' => count($papers)
    ]
]);
```

---

### **Phase 4: Mixed Feed**

#### **4.1. Feed API**
File: `php/api/feed/get-feed.php`

```php
<?php
// Mixed feed: Papers + Projects
// Intelligent mixing based on relevance

$query = $_GET['q'] ?? '';
$userId = getUserId(); // From token

// Get papers
$papers = searchPapers($query);

// Get relevant projects from DB
$projects = searchProjects($query);

// AI ranks and mixes results
$megallm = new MegaLLMService();
$mixedFeed = $megallm->mixAndRank($papers, $projects, $query);

// Format for frontend
$feedItems = [];
foreach ($mixedFeed as $item) {
    if ($item['type'] === 'paper') {
        $feedItems[] = [
            'type' => 'paper',
            'id' => $item['id'],
            'title' => $item['title'],
            'authors' => $item['authors'],
            'year' => $item['year'],
            'abstract' => $item['abstract'],
            'thumbnail' => $item['thumbnail'] ?? '/assets/paper-default.png',
            'citations' => $item['citations'],
            'url' => $item['url'],
            'tags' => $item['tags']
        ];
    } else {
        $feedItems[] = [
            'type' => 'project',
            'id' => $item['id'],
            'title' => $item['title'],
            'description' => $item['description'],
            'lecturer' => [...],
            'requirements' => [...],
            'slots' => $item['max_students'] - $item['current_students'],
            'tags' => $item['tags']
        ];
    }
}

echo json_encode([
    'success' => true,
    'feed' => $feedItems,
    'ai_analysis' => $analysis
]);
```

---

### **Phase 5: UI Components**

#### **5.1. Paper Card Component**
```html
<div class="feed-card paper-card">
    <div class="card-thumbnail">
        <img src="{{thumbnail}}" alt="" onerror="this.src='/assets/paper-default.png'">
        <div class="card-type-badge">📄 Research Paper</div>
        <div class="card-year">2024</div>
    </div>
    <div class="card-content">
        <h3 class="card-title">{{title}}</h3>
        <div class="card-authors">
            <i class="fas fa-user"></i> {{authors}}
        </div>
        <div class="card-meta">
            <span>📖 {{journal}}</span>
            <span>⭐ {{citations}} citations</span>
            <span>🔗 {{source}}</span>
        </div>
        <p class="card-abstract">{{abstract_excerpt}}</p>
        <div class="card-tags">
            {{#each tags}}
            <span class="tag">{{this}}</span>
            {{/each}}
        </div>
        <div class="card-actions">
            <button class="btn-sm btn-primary">
                <i class="fas fa-book-open"></i> Đọc Bài
            </button>
            <button class="btn-sm btn-outline">
                <i class="fas fa-bookmark"></i> Lưu
            </button>
            <button class="btn-sm btn-outline">
                <i class="fas fa-quote-right"></i> Trích Dẫn
            </button>
        </div>
    </div>
</div>
```

#### **5.2. AI Insight Card**
```html
<div class="ai-insight-card">
    <div class="insight-header">
        <div class="ai-avatar-animated">
            <i class="fas fa-robot"></i>
        </div>
        <div>
            <h3>Victoria AI Analysis</h3>
            <p>Dựa trên 150 bài báo liên quan</p>
        </div>
    </div>
    
    <div class="insight-content">
        <div class="insight-section">
            <h4>👥 Ai Đã Nghiên Cứu:</h4>
            <p>{{researchers_summary}}</p>
        </div>
        
        <div class="insight-section warning">
            <h4>⚠️ Lưu Ý:</h4>
            <p>{{common_mistakes}}</p>
        </div>
        
        <div class="insight-section success">
            <h4>💡 Ý Tưởng Của Bạn:</h4>
            <p>{{novelty_assessment}}</p>
        </div>
        
        <div class="insight-section">
            <h4>🎯 Gợi Ý:</h4>
            <ul>
                {{#each suggestions}}
                <li>{{this}}</li>
                {{/each}}
            </ul>
        </div>
    </div>
    
    <div class="insight-actions">
        <button class="btn-sm">💬 Hỏi AI</button>
        <button class="btn-sm">📥 Lưu Analysis</button>
    </div>
</div>
```

#### **5.3. Apply Modal**
```html
<div class="modal-overlay" id="applyModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h2>Apply to Research Project</h2>
            <button class="modal-close" onclick="closeApplyModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="project-preview">
                <h3>{{project_title}}</h3>
                <p>{{lecturer_name}} - {{university}}</p>
            </div>
            
            <form id="applyForm">
                <div class="form-group">
                    <label>
                        <i class="fas fa-envelope"></i>
                        Thư Xin Tham Gia <span class="required">*</span>
                    </label>
                    <textarea 
                        name="coverLetter" 
                        rows="8" 
                        required
                        placeholder="Giới thiệu bản thân, lý do muốn tham gia, kinh nghiệm liên quan..."
                    ></textarea>
                    <div class="ai-suggest">
                        <button type="button" class="btn-sm btn-outline" onclick="aiSuggestCoverLetter()">
                            <i class="fas fa-magic"></i> AI Gợi Ý
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-star"></i>
                        Kinh Nghiệm Liên Quan
                    </label>
                    <textarea 
                        name="relevantExperience" 
                        rows="4"
                        placeholder="Projects, courses, skills liên quan đến đề tài này..."
                    ></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeApplyModal()">
                        Hủy
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Gửi Đơn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

---

## 🎨 **UI/UX Features**

### **Search Experience:**
1. **AI Autocomplete** - Gợi ý khi gõ
2. **Voice Search** - Search bằng giọng nói
3. **Recent Searches** - Lịch sử tìm kiếm
4. **Trending Topics** - Chủ đề hot

### **Feed Experience:**
1. **Infinite Scroll** - Load thêm khi scroll
2. **Real-time Updates** - New projects/papers xuất hiện
3. **Personalized** - Theo major, interests của user
4. **Mixed Content** - Papers + Projects xen kẽ thông minh

### **AI Analysis:**
1. **Instant Analysis** - Hiện ngay sau search
2. **Citation Network** - Ai trích dẫn ai
3. **Trend Detection** - Xu hướng mới/cũ
4. **Risk Assessment** - Cảnh báo hướng sai

---

## 📊 **Implementation Priorities**

### **🔥 Critical (Làm Ngay):**
1. ✅ Tích hợp MegaLLM API client
2. ✅ Search papers từ Semantic Scholar (free, có thumbnail)
3. ✅ AI analysis với Claude Opus
4. ✅ Feed UI layout
5. ✅ Paper cards với thumbnails
6. ✅ Apply modal

### **🟡 Important (Sau đó):**
7. ⏳ Mix papers + projects trong feed
8. ⏳ AI autocomplete search
9. ⏳ Save/bookmark papers
10. ⏳ Citation manager

### **🟢 Nice-to-have (Tương lai):**
11. ⏳ Voice search
12. ⏳ Real-time feed updates
13. ⏳ AI suggest cover letter
14. ⏳ Trend analysis dashboard

---

## 🚀 **Tôi Bắt Đầu Implement Ngay?**

Tôi sẽ tạo:
1. ✅ MegaLLM API client (JS)
2. ✅ Papers API service (PHP + JS)
3. ✅ Feed UI mới cho Student Dashboard
4. ✅ Paper cards với thumbnails
5. ✅ AI analysis card
6. ✅ Apply modal với form đẹp
7. ✅ Backend PHP services

**Estimated**: ~2000 lines code, 15+ files

**Bạn có muốn tôi bắt đầu ngay không?** 🎯🚀
