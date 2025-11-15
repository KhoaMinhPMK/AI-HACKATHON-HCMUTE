# 🔍 Victoria AI - Research Monitoring System

## 🎯 **Concept: CodeRabbit for Research**

### **Giống CodeRabbit nhưng cho NCKH:**

| CodeRabbit (GitHub) | Victoria AI (Research) |
|---------------------|------------------------|
| Review code commits | Monitor search activities |
| Find bugs/issues | Find research gaps/mistakes |
| Suggest improvements | Suggest better directions |
| Generate PR reviews | Generate progress reports |
| Track team progress | Track student progress |

---

## 🏗️ **System Architecture**

```
┌────────────────────────────────────────────────────┐
│                STUDENT SIDE                        │
├────────────────────────────────────────────────────┤
│                                                    │
│  Student searches: "Machine Learning in Healthcare"│
│         ↓                                          │
│  [Track & Log] → search_logs table                │
│         ↓                                          │
│  Papers shown → [Track clicks, saves, time spent] │
│         ↓                                          │
│  Search patterns accumulated                       │
│         ↓                                          │
│  AI Background Analysis (every hour/day)           │
│         ↓                                          │
│  Insights stored → student_insights table          │
│                                                    │
└────────────────────────────────────────────────────┘
                        ↓
┌────────────────────────────────────────────────────┐
│                LECTURER SIDE                       │
├────────────────────────────────────────────────────┤
│                                                    │
│  Dashboard → Team Members List                     │
│         ↓                                          │
│  Click [📊 Check Report] on student                │
│         ↓                                          │
│  API Call: generate-report.php                     │
│         ↓                                          │
│  ┌────────────────────────────┐                   │
│  │   MegaLLM Claude Opus 4.1  │                   │
│  │   Analyze:                  │                   │
│  │   - Search history          │                   │
│  │   - Papers viewed           │                   │
│  │   - Time spent              │                   │
│  │   - Research direction      │                   │
│  └────────────────────────────┘                   │
│         ↓                                          │
│  AI Generated Report:                              │
│  ✅ Student đang nghiên cứu đúng hướng             │
│  ⚠️ Phát hiện gaps trong kiến thức                 │
│  💡 Gợi ý papers quan trọng chưa đọc               │
│  📊 Progress: 65% (Good)                           │
│  🎯 Next steps: Focus on methodology X             │
│         ↓                                          │
│  Display Report UI (giống PR review)               │
│                                                    │
└────────────────────────────────────────────────────┘
```

---

## 📊 **Database Schema**

### **Table: search_logs (Track mọi tìm kiếm)**
```sql
CREATE TABLE search_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    query TEXT NOT NULL COMMENT 'Nội dung search',
    search_type ENUM('papers', 'projects', 'mentors', 'general') DEFAULT 'papers',
    results_count INT DEFAULT 0,
    clicked_results JSON COMMENT 'IDs của papers/projects đã click',
    time_spent INT DEFAULT 0 COMMENT 'Seconds spent on results',
    session_id VARCHAR(64) COMMENT 'Để group searches trong 1 session',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_session (session_id)
);
```

### **Table: paper_interactions (Track tương tác với papers)**
```sql
CREATE TABLE paper_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    paper_id VARCHAR(255) NOT NULL COMMENT 'External paper ID (arXiv, DOI, etc)',
    paper_title TEXT,
    interaction_type ENUM('view', 'save', 'cite', 'download') NOT NULL,
    time_spent INT DEFAULT 0 COMMENT 'Seconds spent reading',
    notes TEXT COMMENT 'User notes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_paper (paper_id),
    INDEX idx_type (interaction_type)
);
```

### **Table: student_insights (AI generated insights)**
```sql
CREATE TABLE student_insights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT DEFAULT NULL COMMENT 'Nếu thuộc project cụ thể',
    
    insight_type ENUM('daily', 'weekly', 'on_demand') DEFAULT 'daily',
    
    research_direction TEXT COMMENT 'Hướng nghiên cứu hiện tại',
    search_patterns JSON COMMENT 'Patterns từ search history',
    knowledge_gaps JSON COMMENT 'Gaps phát hiện',
    recommended_papers JSON COMMENT 'Papers nên đọc',
    warnings JSON COMMENT 'Cảnh báo về hướng sai',
    progress_score INT DEFAULT 0 COMMENT '0-100',
    
    ai_model VARCHAR(50) COMMENT 'Model used: gpt-5, claude-opus-4.1',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP COMMENT 'Insight có thể expire sau 7 ngày',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES research_projects(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_project (project_id),
    INDEX idx_generated (generated_at)
);
```

### **Table: supervisor_reports (Reports cho giảng viên)**
```sql
CREATE TABLE supervisor_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT NOT NULL,
    student_id INT NOT NULL,
    project_id INT DEFAULT NULL,
    
    report_type ENUM('progress', 'onboarding', 'milestone', 'final') DEFAULT 'progress',
    
    time_period_start DATE,
    time_period_end DATE,
    
    summary TEXT COMMENT 'Tóm tắt AI-generated',
    research_focus TEXT COMMENT 'Student đang focus vào gì',
    papers_reviewed_count INT DEFAULT 0,
    search_activity_level ENUM('low', 'medium', 'high', 'very_high'),
    
    strengths JSON COMMENT 'Điểm mạnh phát hiện',
    concerns JSON COMMENT 'Điểm cần lưu ý',
    recommendations JSON COMMENT 'Gợi ý cho giảng viên',
    
    next_steps TEXT COMMENT 'Bước tiếp theo đề xuất',
    
    overall_score INT COMMENT '0-100',
    
    ai_model VARCHAR(50),
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    viewed_at TIMESTAMP NULL,
    
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES research_projects(id) ON DELETE SET NULL,
    
    INDEX idx_lecturer (lecturer_id),
    INDEX idx_student (student_id),
    INDEX idx_generated (generated_at)
);
```

### **Table: team_activity_feed (Activity log cho team)**
```sql
CREATE TABLE team_activity_feed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    
    activity_type ENUM('search', 'paper_save', 'paper_cite', 'note_add', 'milestone') NOT NULL,
    activity_data JSON COMMENT 'Chi tiết activity',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES research_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_project (project_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
);
```

---

## 🤖 **AI Report Generation Flow**

### **Khi Giảng Viên Click "Check Report":**

```javascript
// Frontend
async function checkStudentReport(studentId) {
    showLoading('AI đang phân tích hoạt động của sinh viên...');
    
    const response = await fetch('/api/reports/generate.php', {
        method: 'POST',
        body: JSON.stringify({
            student_id: studentId,
            period: 'last_7_days' // hoặc 'last_30_days', 'all_time'
        })
    });
    
    const report = await response.json();
    displayReport(report);
}
```

### **Backend: AI Report Generator**
```php
// File: php/api/reports/generate.php

function generateStudentReport($studentId, $period = 'last_7_days') {
    // Step 1: Get student's search history
    $searches = getSearchLogs($studentId, $period);
    
    // Step 2: Get papers interactions
    $papers = getPaperInteractions($studentId, $period);
    
    // Step 3: Get team activities
    $activities = getTeamActivities($studentId, $period);
    
    // Step 4: Call MegaLLM Claude Opus for deep analysis
    $megallm = new MegaLLMService();
    
    $prompt = buildAnalysisPrompt($searches, $papers, $activities);
    
    $aiAnalysis = $megallm->analyze($prompt, [
        'model' => 'claude-opus-4-1-20250805',
        'max_tokens' => 4000,
        'temperature' => 0.3 // Factual analysis
    ]);
    
    // Step 5: Structure report
    $report = [
        'student_id' => $studentId,
        'period' => $period,
        'generated_at' => date('Y-m-d H:i:s'),
        
        'summary' => $aiAnalysis['summary'],
        
        'activity_stats' => [
            'searches_count' => count($searches),
            'papers_viewed' => count($papers),
            'total_time_spent' => array_sum(array_column($papers, 'time_spent')),
            'activity_level' => calculateActivityLevel($searches, $papers)
        ],
        
        'research_focus' => $aiAnalysis['focus'],
        'search_patterns' => $aiAnalysis['patterns'],
        
        'strengths' => $aiAnalysis['strengths'],
        'concerns' => $aiAnalysis['concerns'],
        
        'knowledge_gaps' => $aiAnalysis['gaps'],
        'recommended_papers' => $aiAnalysis['must_read'],
        
        'warnings' => $aiAnalysis['warnings'],
        'suggestions' => $aiAnalysis['suggestions'],
        
        'progress_score' => $aiAnalysis['score'], // 0-100
        'next_steps' => $aiAnalysis['next_steps']
    ];
    
    // Step 6: Save report to database
    saveReport($report);
    
    return $report;
}

function buildAnalysisPrompt($searches, $papers, $activities) {
    $prompt = "You are an expert research supervisor. Analyze this student's research activities:\n\n";
    
    $prompt .= "=== SEARCH HISTORY (Last 7 days) ===\n";
    foreach ($searches as $search) {
        $prompt .= "- Query: \"{$search['query']}\" (Date: {$search['created_at']})\n";
    }
    
    $prompt .= "\n=== PAPERS VIEWED ===\n";
    foreach ($papers as $paper) {
        $prompt .= "- \"{$paper['title']}\" (Time spent: {$paper['time_spent']}s)\n";
    }
    
    $prompt .= "\n=== PROVIDE ANALYSIS ===\n";
    $prompt .= "1. Research Direction: What is student focusing on?\n";
    $prompt .= "2. Search Patterns: Are searches coherent and focused?\n";
    $prompt .= "3. Strengths: What is student doing well?\n";
    $prompt .= "4. Concerns: Any red flags? (e.g., scattered focus, outdated methods)\n";
    $prompt .= "5. Knowledge Gaps: What important topics are missing?\n";
    $prompt .= "6. Warnings: Is student going in wrong direction?\n";
    $prompt .= "7. Must-Read Papers: Top 3 papers student should read\n";
    $prompt .= "8. Progress Score: 0-100\n";
    $prompt .= "9. Next Steps: What should student do next?\n";
    $prompt .= "\nReturn as JSON format.\n";
    
    return $prompt;
}
```

---

## 🎨 **UI Design**

### **Lecturer View: Team Management**

```html
<!-- File: pages/dashboard/lecturer/team-management.html -->

<section class="team-container">
    <h1>Quản Lý Nhóm Nghiên Cứu</h1>
    
    <!-- Add Student to Team -->
    <div class="add-member-card">
        <button class="btn-primary" onclick="showAddMemberModal()">
            <i class="fas fa-user-plus"></i>
            Thêm Thành Viên
        </button>
    </div>
    
    <!-- Team Members List -->
    <div class="team-members-grid">
        <!-- Member Card -->
        <div class="member-card">
            <div class="member-header">
                <img src="avatar.jpg" class="member-avatar">
                <div class="member-info">
                    <h3>Nguyễn Văn A</h3>
                    <p>MSSV: 20520001</p>
                    <p>Khoa học máy tính - HCMUT</p>
                </div>
                <div class="member-status">
                    <span class="status-badge active">🟢 Active</span>
                </div>
            </div>
            
            <div class="member-stats">
                <div class="stat-item">
                    <i class="fas fa-search"></i>
                    <span>45 searches</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-book"></i>
                    <span>28 papers</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-clock"></i>
                    <span>12.5h</span>
                </div>
            </div>
            
            <div class="member-activity">
                <h4>Recent Activity:</h4>
                <ul class="activity-list">
                    <li>
                        <i class="fas fa-search"></i>
                        Searched: "Deep Learning for Medical Images"
                        <span class="time">2h ago</span>
                    </li>
                    <li>
                        <i class="fas fa-bookmark"></i>
                        Saved paper: "CNN for X-Ray Analysis"
                        <span class="time">5h ago</span>
                    </li>
                </ul>
            </div>
            
            <div class="member-actions">
                <button class="btn-primary btn-check-report" 
                        onclick="generateReport('{{student_id}}')">
                    <i class="fas fa-chart-line"></i>
                    📊 Check Report
                </button>
                <button class="btn-outline">
                    <i class="fas fa-comment"></i>
                    Chat
                </button>
                <button class="btn-outline">
                    <i class="fas fa-eye"></i>
                    View Details
                </button>
            </div>
        </div>
    </div>
</section>
```

### **AI Report View (Giống GitHub PR Review)**

```html
<!-- Report Modal -->
<div class="report-modal">
    <div class="report-header">
        <div class="report-meta">
            <img src="ai-avatar.png" class="ai-avatar">
            <div>
                <h2>🤖 Victoria AI Report</h2>
                <p>Analysis for: <strong>Nguyễn Văn A</strong></p>
                <p>Period: Last 7 days | Generated: 2 mins ago</p>
            </div>
        </div>
        <div class="report-score">
            <div class="score-circle" data-score="85">
                <span>85</span>
            </div>
            <p>Progress Score</p>
        </div>
    </div>
    
    <div class="report-body">
        <!-- Summary -->
        <div class="report-section summary">
            <h3>📋 Tóm Tắt</h3>
            <p>{{ai_summary}}</p>
        </div>
        
        <!-- Research Focus -->
        <div class="report-section">
            <h3>🎯 Hướng Nghiên Cứu</h3>
            <div class="focus-tags">
                <span class="tag-large">Deep Learning</span>
                <span class="tag-large">Medical Imaging</span>
                <span class="tag-large">CNN</span>
            </div>
            <p>{{research_direction_analysis}}</p>
        </div>
        
        <!-- Strengths (Green) -->
        <div class="report-section strengths">
            <h3>✅ Điểm Mạnh</h3>
            <ul>
                <li>Focused on relevant papers (cited 100+ times)</li>
                <li>Good coverage of fundamental concepts</li>
                <li>Systematic search approach</li>
            </ul>
        </div>
        
        <!-- Concerns (Yellow/Red) -->
        <div class="report-section concerns">
            <h3>⚠️ Cần Lưu Ý</h3>
            <ul>
                <li>⚠️ Chưa đọc về phương pháp validation trong medical AI</li>
                <li>⚠️ Thiếu papers về data augmentation techniques</li>
                <li>🔴 Đang theo hướng CNN thuần, nhưng Transformer đang trending</li>
            </ul>
        </div>
        
        <!-- Knowledge Gaps -->
        <div class="report-section gaps">
            <h3>📚 Knowledge Gaps</h3>
            <div class="gaps-grid">
                <div class="gap-item">
                    <h4>Data Preprocessing</h4>
                    <p>Chưa tìm hiểu về DICOM format và medical image preprocessing</p>
                    <button class="btn-sm">📖 Recommended Papers (3)</button>
                </div>
                <div class="gap-item">
                    <h4>Validation Methods</h4>
                    <p>Thiếu kiến thức về cross-validation trong medical datasets</p>
                    <button class="btn-sm">📖 Recommended Papers (2)</button>
                </div>
            </div>
        </div>
        
        <!-- Must-Read Papers -->
        <div class="report-section must-read">
            <h3>📌 Bài Báo Quan Trọng Chưa Đọc</h3>
            <div class="papers-list">
                <div class="paper-item">
                    <div class="paper-priority">🔥 High</div>
                    <div>
                        <h4>"Attention U-Net: Learning Where to Look for the Pancreas"</h4>
                        <p>Fundamental paper for medical segmentation - 2000+ citations</p>
                    </div>
                    <button class="btn-sm">Xem</button>
                </div>
            </div>
        </div>
        
        <!-- Warnings -->
        <div class="report-section warnings">
            <h3>🚨 Cảnh Báo</h3>
            <div class="warning-item">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <h4>Phương pháp có thể đã lỗi thời</h4>
                    <p>Student đang tìm hiểu về CNN cơ bản (2015-2018), 
                       trong khi Vision Transformers đã vượt trội hơn từ 2021.</p>
                    <button class="btn-sm">Xem Paper Mới Hơn</button>
                </div>
            </div>
        </div>
        
        <!-- Next Steps -->
        <div class="report-section next-steps">
            <h3>🎯 Bước Tiếp Theo</h3>
            <ol>
                <li>Đọc survey paper về Vision Transformers in Medical Imaging (2024)</li>
                <li>Tìm hiểu về DICOM format và preprocessing pipelines</li>
                <li>Research validation methods for medical AI (FDA guidelines)</li>
                <li>Tìm datasets: ChestX-ray14, MIMIC-CXR</li>
            </ol>
        </div>
        
        <!-- Timeline Visualization -->
        <div class="report-section timeline">
            <h3>📈 Research Timeline</h3>
            <div class="timeline-chart">
                <!-- Chart.js hoặc D3.js visualization -->
            </div>
        </div>
    </div>
    
    <div class="report-footer">
        <button class="btn-outline">💾 Lưu Report</button>
        <button class="btn-outline">📧 Email to Student</button>
        <button class="btn-primary">💬 Discuss with Student</button>
    </div>
</div>
```

---

## 🔍 **Search Tracking Implementation**

### **Frontend: Track Everything**

```javascript
// File: js/search-tracker.js

class SearchTracker {
    constructor(userId) {
        this.userId = userId;
        this.sessionId = this.generateSessionId();
        this.currentSearch = null;
    }
    
    async logSearch(query, results) {
        const data = {
            user_id: this.userId,
            query: query,
            results_count: results.length,
            session_id: this.sessionId,
            timestamp: Date.now()
        };
        
        await fetch('/api/tracking/log-search.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        
        this.currentSearch = data;
    }
    
    async logPaperView(paperId, paperTitle) {
        await fetch('/api/tracking/log-interaction.php', {
            method: 'POST',
            body: JSON.stringify({
                user_id: this.userId,
                paper_id: paperId,
                paper_title: paperTitle,
                interaction_type: 'view',
                search_query: this.currentSearch?.query
            })
        });
    }
    
    async logPaperSave(paperId) {
        // Similar
    }
    
    startTimeTracking(paperId) {
        this.timeStart = Date.now();
        this.currentPaper = paperId;
    }
    
    stopTimeTracking() {
        if (this.timeStart && this.currentPaper) {
            const timeSpent = Math.floor((Date.now() - this.timeStart) / 1000);
            
            fetch('/api/tracking/update-time.php', {
                method: 'POST',
                body: JSON.stringify({
                    paper_id: this.currentPaper,
                    time_spent: timeSpent
                })
            });
        }
    }
}

// Auto-track khi user rời trang
window.addEventListener('beforeunload', () => {
    tracker.stopTimeTracking();
});
```

---

## 💬 **AI Chatbot Integration**

### **Chatbot Context-Aware**

```javascript
// Khi sinh viên hỏi chatbot
async function askAI(question) {
    // Get search context
    const recentSearches = await getRecentSearches(userId);
    const recentPapers = await getRecentPapers(userId);
    
    // Build context for AI
    const context = `
        Student recent searches: ${recentSearches.map(s => s.query).join(', ')}
        Papers viewed: ${recentPapers.map(p => p.title).join('; ')}
        Current research focus: ${inferFocus(recentSearches)}
    `;
    
    // Call MegaLLM với context
    const response = await megallm.chat({
        model: 'gpt-5',
        messages: [
            {
                role: 'system',
                content: `You are Victoria AI, a research assistant. Context: ${context}`
            },
            {
                role: 'user',
                content: question
            }
        ]
    });
    
    return response;
}
```

---

## 📊 **Report Example (JSON)**

```json
{
  "student_id": 123,
  "student_name": "Nguyễn Văn A",
  "period": "2025-11-08 to 2025-11-15",
  "generated_at": "2025-11-15 14:30:00",
  
  "summary": "Sinh viên đang tập trung nghiên cứu về Deep Learning trong Medical Imaging. Có tiến bộ tốt trong việc tìm hiểu các phương pháp cơ bản, nhưng cần chú ý đến các phương pháp hiện đại hơn như Vision Transformers.",
  
  "activity_stats": {
    "searches_count": 45,
    "papers_viewed": 28,
    "papers_saved": 12,
    "total_time_spent": 45000,
    "activity_level": "high"
  },
  
  "research_focus": {
    "main_topics": ["Deep Learning", "Medical Imaging", "CNN"],
    "sub_topics": ["X-Ray Analysis", "CT Scan", "Classification"],
    "coherence_score": 85,
    "focus_assessment": "Focused and coherent research direction"
  },
  
  "strengths": [
    "Systematic literature review approach",
    "Reading highly-cited foundational papers",
    "Good coverage of deep learning basics",
    "Active learner - high search frequency"
  ],
  
  "concerns": [
    {
      "severity": "medium",
      "issue": "Focusing on outdated CNN architectures (2015-2018)",
      "recommendation": "Explore Vision Transformers (2021+)"
    },
    {
      "severity": "low",
      "issue": "Missing papers on medical data validation",
      "recommendation": "Research FDA guidelines for medical AI"
    }
  ],
  
  "knowledge_gaps": [
    {
      "gap": "Data Preprocessing for Medical Images",
      "papers_missing": ["DICOM standard", "Medical image normalization"],
      "priority": "high"
    },
    {
      "gap": "Validation Methods",
      "papers_missing": ["Cross-validation in healthcare", "Clinical trial design"],
      "priority": "medium"
    }
  ],
  
  "warnings": [
    {
      "type": "methodology",
      "message": "Student đang theo approach CNN thuần túy. 70% papers gần đây chuyển sang Transformer-based models. Cần update!",
      "action": "Suggest modern papers on ViT, Swin Transformer"
    }
  ],
  
  "must_read_papers": [
    {
      "title": "An Image is Worth 16x16 Words: Transformers for Image Recognition at Scale",
      "reason": "Foundational ViT paper - must-know for modern medical imaging",
      "priority": "critical",
      "citations": 15000
    },
    {
      "title": "Attention U-Net: Learning Where to Look",
      "reason": "Key paper for medical image segmentation",
      "priority": "high",
      "citations": 2500
    }
  ],
  
  "progress_score": 75,
  "score_breakdown": {
    "search_activity": 90,
    "paper_quality": 80,
    "focus_coherence": 85,
    "knowledge_coverage": 60
  },
  
  "next_steps": [
    "Đọc survey paper về Vision Transformers in Medical Imaging (2024)",
    "Tìm hiểu về DICOM format và medical image preprocessing",
    "Research validation methods cho medical AI",
    "Tìm datasets: ChestX-ray14, MIMIC-CXR",
    "Set up meeting với giảng viên để discuss direction"
  ],
  
  "ai_model_used": "claude-opus-4-1-20250805",
  "tokens_used": 3500
}
```

---

## 🚀 **Implementation Order**

### **Sprint 1: Foundation (Week 1)**
1. ✅ SQL schema (search_logs, insights, reports)
2. ✅ MegaLLM client integration
3. ✅ Search tracker (log searches)
4. ✅ Basic report generator

### **Sprint 2: Core Features (Week 2)**
5. ✅ Team management UI
6. ✅ Report viewer UI
7. ✅ Paper interactions tracking
8. ✅ AI analysis engine

### **Sprint 3: Polish (Week 3)**
9. ✅ Real-time activity feed
10. ✅ Advanced insights
11. ✅ Email reports
12. ✅ Export reports (PDF)

---

## 🎯 **Key Features**

### **For Lecturers:**
- ✅ Add students to team/project
- ✅ View team dashboard với activity feed
- ✅ Click "Check Report" → AI generates comprehensive analysis
- ✅ See search patterns, papers viewed, time spent
- ✅ Get warnings about wrong directions
- ✅ AI recommendations for guidance

### **For Students:**
- ✅ Every search is tracked (transparent)
- ✅ Paper views/saves logged
- ✅ Time spent tracked
- ✅ AI insights available anytime
- ✅ Self-assessment dashboard

---

## 💡 **AI Analysis Capabilities**

### **MegaLLM Models Usage:**

| Task | Model | Why |
|------|-------|-----|
| Query Understanding | GPT-5 | Best for intent detection |
| Deep Analysis | Claude Opus 4.1 | Superior reasoning |
| Quick Insights | GPT-4o | Fast, cost-effective |
| Report Generation | Claude Opus 4.1 | Best writing quality |

### **Analysis Points:**

1. **Research Direction** - Đang focus vào gì?
2. **Search Coherence** - Searches có liên kết không?
3. **Paper Quality** - Đọc papers tốt không?
4. **Knowledge Coverage** - Có gaps nào?
5. **Methodology Awareness** - Biết phương pháp hiện đại không?
6. **Progress Speed** - Tiến độ có OK không?
7. **Risk Detection** - Có đang đi sai hướng không?

---

## 🎉 **Tổng Kết**

**Hệ thống sẽ có:**
- 🔍 AI-powered search engine
- 📊 Automatic progress monitoring
- 🤖 Intelligent insights generation
- 📈 Visual progress reports
- ⚠️ Early warning system
- 💡 Smart recommendations
- 📧 Automated reports for supervisors

**Giống như:**
- CodeRabbit review PRs → Victoria AI review research progress
- GitHub Insights → Research Insights
- LinkedIn feed → Research feed

---

**Tôi bắt đầu implement ngay nhé?** 🚀

Tôi sẽ tạo từng component một, bắt đầu với MegaLLM integration và search tracking!
