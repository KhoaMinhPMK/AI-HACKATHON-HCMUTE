# 🚀 Quick Start Guide - Victoria AI Platform

## 📖 Tổng quan dự án

**Victoria AI** là nền tảng hỗ trợ nghiên cứu học thuật với:
- 🔍 Tìm kiếm papers khoa học từ 200M+ papers (Semantic Scholar, arXiv)
- 🤖 AI phân tích và đề xuất papers liên quan
- 👥 Kết nối sinh viên - giảng viên qua projects
- 💬 Chat AI hỗ trợ nghiên cứu

---

## ✅ Các tính năng đã hoàn thiện

### 1. Backend APIs ✅
- ✅ **Papers Search**: Real-time search từ Semantic Scholar & arXiv
- ✅ **Projects Search**: Tìm kiếm đề tài từ database
- ✅ **Search Logging**: Track user searches (optional)
- ✅ **Error handling**: Graceful fallback, không crash

### 2. Frontend Pages ✅
- ✅ **Landing page**: Modern, responsive
- ✅ **Authentication**: Register, Login, Forgot Password (Firebase)
- ✅ **Student Dashboard**: Browse projects, search papers
- ✅ **Lecturer Dashboard**: Manage projects, teams
- ✅ **Chat Interface**: AI-powered chat

### 3. UI/UX ✅
- ✅ **Màu sắc đồng nhất**: #5cc0eb (sky blue)
- ✅ **Không gradient**: Solid colors, tinh tế
- ✅ **Typography**: Rõ ràng, dễ đọc
- ✅ **Responsive**: Desktop, tablet, mobile

### 4. Integrations ✅
- ✅ **Semantic Scholar API**: 200M+ papers
- ✅ **arXiv API**: Research papers
- ✅ **MegaLLM AI**: GPT-5, Claude 3.5 Sonnet
- ✅ **Firebase**: Authentication, Firestore
- ✅ **MySQL**: Projects, users data

---

## 🎯 Hướng dẫn sử dụng

### Cho Sinh viên:

1. **Đăng ký tài khoản**:
   - Truy cập: `https://bkuteam.site/pages/auth/register.html`
   - Chọn role: Student
   - Hoàn thành profile

2. **Tìm kiếm papers**:
   - Vào: `https://bkuteam.site/pages/dashboard/student/browse-projects.html`
   - Gõ từ khóa (VD: "cancer", "machine learning", "climate change")
   - Nhận kết quả từ Semantic Scholar + AI insights

3. **Browse projects**:
   - Xem các đề tài đang tuyển thành viên
   - Filter theo lĩnh vực
   - Apply vào project

### Cho Giảng viên:

1. **Tạo project**:
   - Dashboard: `https://bkuteam.site/pages/dashboard/lecturer/index.html`
   - Create new project
   - Set requirements

2. **Quản lý team**:
   - Team management: `https://bkuteam.site/pages/dashboard/lecturer/team-management.html`
   - Invite members
   - Track progress

---

## 🔧 Cài đặt & Deploy

### Yêu cầu:
- **Server**: Windows Server IIS 10.0
- **PHP**: 8.4.14+
- **Extensions**: OpenSSL, json, simplexml, mbstring
- **Database**: MySQL 8.0+
- **Git**: For deployment

### Deploy lên server:

```powershell
# 1. Clone repository
cd C:\inetpub\wwwroot
git clone https://github.com/KhoaMinhPMK/AI-HACKATHON-HCMUTE.git bkuteam.site

# 2. Configure PHP (php.ini)
extension=openssl
allow_url_fopen = On

# 3. Set up database
# Import SQL files from /sql/ directory

# 4. Configure IIS
# Point to bkuteam.site folder
# Set default document: index.html

# 5. Restart IIS
iisreset /restart
```

### Update code:

```powershell
cd C:\inetpub\wwwroot\bkuteam.site
git pull origin main
iisreset /restart
```

---

## 🧪 Testing

### Test APIs:

```powershell
# Papers search
Invoke-WebRequest "https://bkuteam.site/php/api/search/papers-search-v2.php?q=cancer"

# Projects search  
Invoke-WebRequest "https://bkuteam.site/php/api/projects/search.php?q=AI"

# Config check
Invoke-WebRequest "https://bkuteam.site/php/api/test/check-config.php"
```

### Test Pages:
1. Landing: `https://bkuteam.site`
2. Register: `https://bkuteam.site/pages/auth/register.html`
3. Browse: `https://bkuteam.site/pages/dashboard/student/browse-projects.html`
4. Chat: `https://bkuteam.site/pages/chat/index.html`

---

## 📊 API Documentation

### 1. Papers Search API

**Endpoint**: `/php/api/search/papers-search-v2.php`

**Method**: GET

**Parameters**:
- `q` (required): Search query
- Example: `?q=machine learning`

**Response**:
```json
{
  "success": true,
  "query": "machine learning",
  "results": [
    {
      "id": "paper_id",
      "source": "semantic_scholar",
      "title": "Paper Title",
      "abstract": "Abstract text...",
      "authors": "Author names",
      "year": 2024,
      "citations": 1234,
      "url": "https://...",
      "pdf_url": "https://..."
    }
  ],
  "total": 20,
  "sources": ["semantic_scholar", "arxiv"]
}
```

### 2. Projects Search API

**Endpoint**: `/php/api/projects/search.php`

**Method**: GET

**Parameters**:
- `q` (optional): Search query
- Example: `?q=AI`

**Response**:
```json
{
  "success": true,
  "results": [
    {
      "id": 1,
      "title": "Project Title",
      "description": "Description...",
      "lecturer_name": "Dr. Name",
      "field": "Computer Science",
      "status": "recruiting"
    }
  ]
}
```

---

## 🎨 Design System

### Colors:
```css
--primary-color: #5cc0eb;      /* Main color */
--primary-hover: #3da9d4;      /* Hover state */
--primary-light: #e0f4fc;      /* Light background */
--text-primary: #1f2937;       /* Main text */
--text-secondary: #57606A;     /* Secondary text */
--bg-primary: #ffffff;         /* White background */
--bg-secondary: #fafafa;       /* Light gray */
--border-light: #e5e7eb;       /* Border */
```

### Typography:
```css
/* Headings */
h1: 2.4rem, font-weight: 700
h2: 2rem, font-weight: 700
h3: 1.8rem, font-weight: 600

/* Body */
p: 1.5rem, line-height: 1.7
small: 1.3rem

/* Colors */
color: #1f2937 (dark gray)
```

### Spacing:
```css
--spacing-xs: 8px
--spacing-sm: 16px
--spacing-md: 24px
--spacing-lg: 32px
--spacing-xl: 48px
```

### Border Radius:
```css
--radius-sm: 8px
--radius-md: 12px
--radius-lg: 16px
--radius-xl: 20px
```

---

## 🔐 Security

### API Keys (Server-side only):
```php
// MegaLLM API Key
$apiKey = 'sk-mega-a871069e3800ca98042da57b6a019814e9bd173a42a5870412b88895d52eea5e';

// Firebase config (public - OK)
// In firebaseConfig.js
```

### Database Credentials:
```php
// In php/config/database.php
// Never commit to Git!
```

---

## 📈 Performance

### Current metrics:
- **Landing page**: ~1.5s load time
- **Dashboard**: ~2s load time
- **Papers search**: 3-8s (external API calls)
- **AI analysis**: 5-10s (MegaLLM processing)

### Optimization tips:
1. Enable browser caching
2. Minify CSS/JS in production
3. Use CDN for static assets
4. Add Redis caching for API results

---

## 🐛 Troubleshooting

### Common Issues:

**1. API returns 500 error**:
- Check PHP error log: `C:\inetpub\logs`
- Verify OpenSSL enabled
- Test with: `php/api/test/check-config.php`

**2. Papers search không trả về data**:
- Check network: `php/api/test/network-diagnostic.php`
- Verify HTTPS wrapper: `allow_url_fopen = On`
- Test step-by-step: `php/api/test/step-by-step.php`

**3. UI không đúng màu**:
- Clear browser cache: Ctrl+Shift+R
- Check CSS loaded: View Source
- Verify variables.css imported

**4. Authentication không work**:
- Check Firebase config
- Verify auth-guard.js loaded
- Check browser console errors

---

## 📚 Documentation

### Available docs:
- `TESTING_CHECKLIST.md` - Comprehensive testing guide
- `REAL_API_INTEGRATION.md` - API integration details
- `DEPLOYMENT_GUIDE.md` - Full deployment guide
- `PROJECT_STRUCTURE.md` - Project organization

---

## 🤝 Contributing

### Workflow:
```bash
# 1. Create branch
git checkout -b feature/your-feature

# 2. Make changes
# Edit files...

# 3. Test locally
# Verify everything works

# 4. Commit
git add .
git commit -m "Description"

# 5. Push
git push origin feature/your-feature

# 6. Create Pull Request
# On GitHub
```

---

## 📞 Support

### Contact:
- **GitHub**: KhoaMinhPMK/AI-HACKATHON-HCMUTE
- **Email**: phuonglethiha977@gmail.com
- **Website**: https://bkuteam.site

### Resources:
- Semantic Scholar API: https://www.semanticscholar.org/product/api
- arXiv API: https://arxiv.org/help/api
- Firebase Docs: https://firebase.google.com/docs
- MegaLLM: https://ai.megallm.io

---

## ✨ Features Roadmap

### Completed ✅:
- [x] Papers search với real API
- [x] UI/UX tinh tế, đồng nhất
- [x] Authentication flows
- [x] Projects management
- [x] AI chat interface

### Upcoming 🚧:
- [ ] Bookmark papers
- [ ] Export citations
- [ ] Team collaboration
- [ ] Real-time notifications
- [ ] Mobile app

---

**Version**: 2.0  
**Last Updated**: November 15, 2025  
**Status**: ✅ Production Ready

---

## 🎯 Quick Links

- 🏠 **Homepage**: https://bkuteam.site
- 📝 **Register**: https://bkuteam.site/pages/auth/register.html
- 🔍 **Browse**: https://bkuteam.site/pages/dashboard/student/browse-projects.html
- 💬 **Chat**: https://bkuteam.site/pages/chat/index.html
- 🧪 **Test API**: https://bkuteam.site/php/api/test/

---

**Made with ❤️ by BKU Team**
