# 🎓 Victoria AI - Role-Based System Design

## 🎯 **Concept: LinkedIn + VietnamWorks cho NCKH**

### **2 Vai Trò Chính:**

| Giảng Viên (Lecturer) | Sinh Viên (Student) |
|----------------------|---------------------|
| 👨‍🏫 Nhà tuyển dụng | 👨‍🎓 Ứng viên |
| Đăng đề tài NCKH | Tìm đề tài/cơ hội |
| Tìm sinh viên phù hợp | Tìm giảng viên hướng dẫn |
| Xem CV/profile SV | Xây dựng CV/Portfolio |
| Accept/Reject applications | Apply to projects |
| Quản lý team | Join research teams |

---

## 🏗️ **Architecture Overview**

```
┌─────────────────────────────────────────────────────────┐
│                    ROLE CHECK MIDDLEWARE                │
│         (Bắt buộc có role trước khi dùng app)          │
└──────────────────┬──────────────────────────────────────┘
                   │
        ┌──────────┴──────────┐
        │                     │
   ┌────▼─────┐         ┌────▼─────┐
   │ LECTURER │         │ STUDENT  │
   │Dashboard │         │Dashboard │
   └──────────┘         └──────────┘
        │                     │
   ┌────┴─────────────┐  ┌───┴──────────────┐
   │                  │  │                  │
   │ - Post Projects  │  │ - Browse Projects│
   │ - Browse Students│  │ - Browse Mentors │
   │ - Manage Apps    │  │ - Apply Jobs     │
   │ - Team Mgmt      │  │ - Build CV       │
   └──────────────────┘  └──────────────────┘
```

---

## 📊 **Database Schema - New Tables**

### **Table: research_projects**
```sql
CREATE TABLE research_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT NOT NULL,                -- FK to users (lecturer)
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,                        -- Yêu cầu (ngành, kỹ năng)
    duration VARCHAR(50),                     -- "3 tháng", "6 tháng", "1 năm"
    status ENUM('open', 'in_progress', 'completed', 'closed') DEFAULT 'open',
    max_students INT DEFAULT 3,
    current_students INT DEFAULT 0,
    tags TEXT,                                -- JSON array: ["AI", "ML", "Data Science"]
    requirements_major TEXT,                  -- Chuyên ngành yêu cầu
    requirements_skills TEXT,                 -- Kỹ năng cần thiết
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES users(id)
);
```

### **Table: applications**
```sql
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    student_id INT NOT NULL,                  -- FK to users (student)
    cover_letter TEXT,                        -- Thư xin tham gia
    status ENUM('pending', 'accepted', 'rejected', 'withdrawn') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    response_message TEXT,                    -- Phản hồi từ giảng viên
    FOREIGN KEY (project_id) REFERENCES research_projects(id),
    FOREIGN KEY (student_id) REFERENCES users(id)
);
```

### **Table: team_members**
```sql
CREATE TABLE team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    student_id INT NOT NULL,
    role VARCHAR(100),                        -- "Leader", "Member", "Data Analyst"
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES research_projects(id),
    FOREIGN KEY (student_id) REFERENCES users(id)
);
```

### **Table: saved_projects (Bookmark)**
```sql
CREATE TABLE saved_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    project_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (project_id) REFERENCES research_projects(id)
);
```

---

## 🎨 **UI Design - Role-Based Dashboards**

### **🧑‍🏫 Lecturer Dashboard**

#### **Layout:**
```
┌─────────────────────────────────────────────────┐
│ [Header: Logo | Notifications | Profile | ⚙️]  │
├─────────────────────────────────────────────────┤
│                                                 │
│  📊 Quick Stats                                 │
│  ┌─────────┬─────────┬─────────┬─────────┐    │
│  │ Active  │ Pending │ Students│ Projects│    │
│  │Projects │  Apps   │ Working │Completed│    │
│  └─────────┴─────────┴─────────┴─────────┘    │
│                                                 │
│  [➕ Đăng Đề Tài NCKH Mới]                     │
│                                                 │
│  📁 Đề Tài Của Tôi                             │
│  ┌───────────────────────────────────────┐    │
│  │ 🔬 Nghiên cứu AI trong Y tế          │    │
│  │ 👥 2/3 sinh viên | 📌 In Progress    │    │
│  │ [Xem Chi Tiết] [Quản Lý Team]        │    │
│  └───────────────────────────────────────┘    │
│                                                 │
│  📬 Applications Đang Chờ (5)                  │
│  ┌───────────────────────────────────────┐    │
│  │ 👤 Nguyễn Văn A - HCMUT               │    │
│  │ Apply: "Nghiên cứu AI trong Y tế"     │    │
│  │ [Xem Profile] [Accept] [Reject]       │    │
│  └───────────────────────────────────────┘    │
│                                                 │
│  🔍 Tìm Sinh Viên                              │
│  [Search by major, skills, university...]      │
│                                                 │
└─────────────────────────────────────────────────┘
```

#### **Navigation:**
- 🏠 Dashboard
- 📁 Đề Tài Của Tôi
- 👥 Tìm Sinh Viên
- 📬 Applications
- 👤 Profile & Settings

---

### **🎓 Student Dashboard**

#### **Layout:**
```
┌─────────────────────────────────────────────────┐
│ [Header: Logo | Notifications | Profile | ⚙️]  │
├─────────────────────────────────────────────────┤
│                                                 │
│  📊 Tổng Quan                                   │
│  ┌─────────┬─────────┬─────────┬─────────┐    │
│  │Projects │ Applied │ Saved   │ Active  │    │
│  │Available│         │Projects │Projects │    │
│  └─────────┴─────────┴─────────┴─────────┘    │
│                                                 │
│  🔍 Tìm Đề Tài NCKH                            │
│  [Search by topic, lecturer, university...]    │
│  [Filters: Ngành, Thời gian, Khó, ...]        │
│                                                 │
│  📁 Đề Tài Phù Hợp Với Bạn                     │
│  ┌───────────────────────────────────────┐    │
│  │ 🔬 Nghiên cứu Computer Vision         │    │
│  │ 👨‍🏫 TS. Nguyễn Văn B - HCMUT         │    │
│  │ 👥 Cần 2/3 SV | ⏰ 6 tháng           │    │
│  │ 🏷️ AI, Computer Vision, Python       │    │
│  │ [Xem Chi Tiết] [Apply] [💾 Save]     │    │
│  └───────────────────────────────────────┘    │
│                                                 │
│  👨‍🏫 Giảng Viên Nổi Bật                       │
│  [Carousel: Top lecturers đang tuyển]          │
│                                                 │
│  📝 Đề Tài Đã Apply (2)                        │
│  ┌───────────────────────────────────────┐    │
│  │ Research X | Status: ⏳ Pending       │    │
│  │ Research Y | Status: ✅ Accepted      │    │
│  └───────────────────────────────────────┘    │
│                                                 │
└─────────────────────────────────────────────────┘
```

#### **Navigation:**
- 🏠 Dashboard
- 🔍 Tìm Đề Tài
- 👨‍🏫 Tìm Mentor
- 💼 Applications của Tôi
- 💾 Đã Lưu
- 📄 CV/Portfolio
- 👤 Profile & Settings

---

## 🔐 **Role Gate System**

### **Middleware: role-gate.js**
```javascript
// Bắt buộc phải có role
async function requireRole() {
    const user = await requireAuth();
    
    // Check if user has role
    const profile = await getProfile(user);
    
    if (!profile.role) {
        // Redirect to role selection
        window.location.href = '/pages/onboarding/select-role.html';
        throw new Error('Role required');
    }
    
    return { user, role: profile.role };
}

// Redirect based on role
function redirectToDashboard(role) {
    if (role === 'lecturer') {
        window.location.href = '/pages/dashboard/lecturer/index.html';
    } else if (role === 'student') {
        window.location.href = '/pages/dashboard/student/index.html';
    }
}
```

---

## 📁 **Cấu Trúc Folder Mới:**

```
pages/
├── auth/
│   ├── signin.html
│   ├── register.html
│   └── styles.css
│
├── onboarding/
│   ├── select-role.html              ← BẮT BUỘC chọn role trước
│   ├── complete-profile.html         ← BẮT BUỘC hoàn thiện profile
│   └── styles.css
│
├── dashboard/
│   ├── lecturer/
│   │   ├── index.html                ← Dashboard GV
│   │   ├── browse-students.html      ← Tìm sinh viên
│   │   ├── post-project.html         ← Đăng đề tài
│   │   ├── manage-projects.html      ← Quản lý đề tài
│   │   ├── applications.html         ← Xem applications
│   │   └── styles.css
│   │
│   ├── student/
│   │   ├── index.html                ← Dashboard SV
│   │   ├── browse-projects.html      ← Tìm đề tài
│   │   ├── browse-mentors.html       ← Tìm giảng viên
│   │   ├── my-applications.html      ← Applications của tôi
│   │   ├── saved-projects.html       ← Đã lưu
│   │   ├── portfolio.html            ← CV/Portfolio
│   │   └── styles.css
│   │
│   └── settings.html                 ← Chung cho cả 2
│
└── project/
    ├── detail.html                   ← Chi tiết đề tài (public)
    └── styles.css
```

---

## 🎨 **UI Components - Giống LinkedIn/VietnamWorks**

### **For Lecturer (Giống Employer):**

#### **1. Project Card (Job Posting Card)**
```html
<div class="project-card">
    <div class="project-header">
        <h3>Nghiên cứu Computer Vision trong Y tế</h3>
        <span class="status-badge open">🟢 Đang tuyển</span>
    </div>
    <div class="project-meta">
        <span>👥 2/3 sinh viên</span>
        <span>⏰ 6 tháng</span>
        <span>📅 Bắt đầu: 01/2025</span>
    </div>
    <div class="project-tags">
        <span class="tag">Computer Vision</span>
        <span class="tag">Python</span>
        <span class="tag">Deep Learning</span>
    </div>
    <div class="project-actions">
        <button>Xem Chi Tiết</button>
        <button>Sửa</button>
        <button>📬 5 Applications</button>
    </div>
</div>
```

#### **2. Student Card (Candidate Card)**
```html
<div class="student-card">
    <div class="student-header">
        <img src="avatar.jpg" class="avatar">
        <div class="student-info">
            <h3>Nguyễn Văn A</h3>
            <p>Sinh viên năm 3 - HCMUT</p>
            <p>Khoa học máy tính</p>
        </div>
        <div class="match-score">
            <div class="score-circle">85%</div>
            <small>Phù hợp</small>
        </div>
    </div>
    <div class="student-skills">
        <span class="skill">Python</span>
        <span class="skill">TensorFlow</span>
        <span class="skill">Computer Vision</span>
    </div>
    <div class="student-stats">
        <span>📚 GPA: 3.5/4.0</span>
        <span>📄 2 projects</span>
    </div>
    <div class="student-actions">
        <button>Xem Profile</button>
        <button>Mời Tham Gia</button>
    </div>
</div>
```

---

### **For Student (Giống Job Seeker):**

#### **1. Project Listing (Job Listing)**
```html
<div class="job-card">
    <div class="job-header">
        <div class="lecturer-info">
            <img src="lecturer-avatar.jpg" class="avatar-small">
            <div>
                <h4>TS. Trần Thị B</h4>
                <p>Đại học Bách Khoa TP.HCM</p>
            </div>
        </div>
        <button class="btn-save">💾</button>
    </div>
    <h3>Nghiên cứu AI cho Chăm sóc Sức khỏe</h3>
    <div class="job-meta">
        <span>👥 Cần 2 sinh viên</span>
        <span>⏰ 6 tháng</span>
        <span>📅 Posted: 2 ngày trước</span>
    </div>
    <p class="job-desc">
        Nghiên cứu ứng dụng AI trong chẩn đoán bệnh qua hình ảnh y tế...
    </p>
    <div class="job-requirements">
        <span>🎓 CNTT, Y khoa</span>
        <span>💻 Python, PyTorch</span>
    </div>
    <div class="job-tags">
        <span class="tag">AI</span>
        <span class="tag">Healthcare</span>
        <span class="tag">Computer Vision</span>
    </div>
    <div class="job-actions">
        <button class="btn-primary">Apply Ngay</button>
        <button class="btn-outline">Xem Chi Tiết</button>
    </div>
</div>
```

#### **2. Lecturer Card (Company Card)**
```html
<div class="lecturer-card">
    <div class="lecturer-banner"></div>
    <div class="lecturer-content">
        <img src="avatar.jpg" class="lecturer-avatar">
        <h3>TS. Trần Thị B</h3>
        <p class="lecturer-title">Tiến sĩ | Khoa KHMT</p>
        <p class="lecturer-university">Đại học Bách Khoa TP.HCM</p>
        
        <div class="lecturer-stats">
            <span>📚 15 publications</span>
            <span>👥 8 sinh viên đã hướng dẫn</span>
            <span>⭐ 4.8/5</span>
        </div>
        
        <div class="lecturer-interests">
            <span class="tag">AI</span>
            <span class="tag">Machine Learning</span>
            <span class="tag">Data Science</span>
        </div>
        
        <div class="lecturer-projects">
            <h4>Đang tuyển (3 đề tài):</h4>
            <ul>
                <li>Research project A</li>
                <li>Research project B</li>
            </ul>
        </div>
        
        <button class="btn-primary">Xem Profile</button>
        <button class="btn-outline">Follow</button>
    </div>
</div>
```

---

## 🔄 **User Flows**

### **Flow 1: Onboarding (Lần Đầu Đăng Ký)**

```
Register
   ↓
Dashboard (generic)
   ↓
Check role: NULL
   ↓
Redirect → select-role.html
   ↓ (Chọn Student/Lecturer)
   ↓
Redirect → complete-profile.html
   ↓ (Điền đầy đủ thông tin)
   ↓
Redirect → Dashboard theo role
   ↓
✅ Lecturer → lecturer/index.html
✅ Student → student/index.html
```

### **Flow 2: Lecturer Posts Project**

```
Lecturer Dashboard
   ↓
Click [➕ Đăng Đề Tài]
   ↓
post-project.html
   ↓ Fill form:
   - Title, Description
   - Requirements (major, skills)
   - Duration, Max students
   - Tags
   ↓
Submit → API: /api/projects/create.php
   ↓
Success → Redirect manage-projects.html
   ↓
✅ Project xuất hiện trong "Đề Tài Của Tôi"
✅ Project xuất hiện trong search của Students
```

### **Flow 3: Student Applies to Project**

```
Student Dashboard
   ↓
Browse projects hoặc Search
   ↓
Tìm thấy đề tài phù hợp
   ↓
Click "Xem Chi Tiết"
   ↓
Project detail page
   ↓
Click "Apply Ngay"
   ↓
Modal: Write cover letter
   ↓
Submit → API: /api/applications/apply.php
   ↓
Success → Toast "Đã gửi đơn!"
   ↓
✅ Application status: Pending
✅ Lecturer thấy application trong dashboard
```

### **Flow 4: Lecturer Reviews Application**

```
Lecturer Dashboard
   ↓
Section: "Applications Đang Chờ"
   ↓
Click "Xem Profile" của student
   ↓
Modal/Page: Student full profile
   - GPA, Projects, Skills, Bio
   - Cover letter
   ↓
Decision:
   ├─ Accept → API: /api/applications/accept.php
   │    ↓
   │    Add to team_members
   │    Notification to student
   │    ✅ Student joins project
   │
   └─ Reject → API: /api/applications/reject.php
        ↓
        Send feedback message
        Notification to student
```

---

## 🎯 **Matching/Recommendation System**

### **For Lecturers: Recommend Students**

Algorithm:
```javascript
Match Score = 
  - Major match (40%)
  - Skills match (30%)
  - GPA (15%)
  - Past experience (15%)
```

### **For Students: Recommend Projects**

Algorithm:
```javascript
Relevance Score = 
  - Major match (35%)
  - Skills match (30%)
  - Research interests (25%)
  - Lecturer rating (10%)
```

---

## 📱 **Features List**

### **🧑‍🏫 Lecturer Features:**
- ✅ Dashboard với stats
- ✅ Đăng đề tài NCKH
- ✅ Browse/Search sinh viên
- ✅ Filter SV theo: ngành, kỹ năng, GPA, trường
- ✅ Xem student profile đầy đủ
- ✅ Manage applications (Accept/Reject)
- ✅ Quản lý team members
- ✅ Notifications khi có apply
- ✅ Chat với sinh viên (future)

### **🎓 Student Features:**
- ✅ Dashboard với recommended projects
- ✅ Browse/Search đề tài NCKH
- ✅ Filter projects: ngành, topic, duration
- ✅ Apply to projects với cover letter
- ✅ Save/Bookmark projects
- ✅ Browse lecturers
- ✅ Xây dựng CV/Portfolio
- ✅ Track application status
- ✅ Notifications khi được accept
- ✅ Chat với giảng viên (future)

---

## 🎨 **Design Inspiration**

### **LinkedIn-like:**
- Profile cards với avatar, stats, tags
- Connection suggestions
- Activity feed
- Endorsements/Recommendations

### **VietnamWorks-like:**
- Job/Project listings với filters
- Apply với cover letter
- Application status tracking
- Saved jobs/projects
- Employer (Lecturer) profiles

---

## 🚀 **Implementation Priority**

### **Phase 1: Core (QUAN TRỌNG NHẤT)**
1. ✅ Role gate middleware
2. ✅ SQL schema (projects, applications, teams)
3. ✅ Dashboard riêng cho Student
4. ✅ Dashboard riêng cho Lecturer

### **Phase 2: Project Management**
5. ✅ Post project (Lecturer)
6. ✅ Browse projects (Student)
7. ✅ Apply to project
8. ✅ Accept/Reject application

### **Phase 3: Discovery**
9. ✅ Browse students (Lecturer)
10. ✅ Browse lecturers (Student)
11. ✅ Search & Filters
12. ✅ Matching algorithm

### **Phase 4: Advanced**
13. ⏳ Portfolio/CV builder
14. ⏳ Chat system
15. ⏳ Notifications
16. ⏳ Rating/Review system

---

## 💡 **Next Steps**

Bạn muốn tôi bắt đầu implement ngay không? Tôi sẽ:

1. ✅ Tạo SQL schema cho projects, applications, teams
2. ✅ Tạo role-gate middleware
3. ✅ Tạo onboarding flow (select role → complete profile)
4. ✅ Tạo Dashboard riêng cho Lecturer
5. ✅ Tạo Dashboard riêng cho Student
6. ✅ Implement core features (post, apply, browse)

**Bắt đầu ngay không?** 🚀
