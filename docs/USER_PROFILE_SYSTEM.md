# Hệ Thống Quản Lý Thông Tin Người Dùng - Victoria AI

## 📋 Tổng Quan

Hệ thống quản lý thông tin người dùng cho 2 loại đối tượng:
- **Sinh viên**: Nghiên cứu khoa học dưới sự hướng dẫn
- **Giảng viên**: Hướng dẫn và quản lý nghiên cứu

## 👥 Phân Loại Người Dùng

### Sinh Viên (Student)
Thông tin bắt buộc:
- Họ và tên
- Email
- Mã số sinh viên (MSSV)
- Trường đại học
- Chuyên ngành
- Khóa học (năm nhập học)
- Số điện thoại

### Giảng Viên (Lecturer)
Thông tin bắt buộc:
- Họ và tên
- Email
- Mã giảng viên
- Trường đại học
- Khoa/Bộ môn
- Học vị (Thạc sĩ, Tiến sĩ, PGS, GS)
- Chuyên môn nghiên cứu
- Số điện thoại

## 🔄 Quy Trình Đăng Ký & Cập Nhật

### Đăng ký bằng Email/Password:
1. Người dùng chọn vai trò (Sinh viên/Giảng viên)
2. Điền thông tin cơ bản (tên, email, mật khẩu)
3. Chuyển đến trang Settings để bổ sung thông tin chi tiết
4. **Bắt buộc** hoàn thiện profile trước khi sử dụng đầy đủ

### Đăng ký bằng Google:
1. Đăng ký nhanh qua Google
2. Chuyển đến Dashboard
3. **Banner nhắc nhở** xuất hiện nếu chưa cập nhật thông tin
4. Người dùng vào Settings để chọn role và cập nhật

## 🎯 Luồng Hoạt Động

```
┌─────────────────┐
│  Đăng Ký/Login  │
└────────┬────────┘
         │
         ▼
┌─────────────────────┐
│  Check Profile      │
│  Complete?          │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
   YES       NO
    │         │
    ▼         ▼
Dashboard  Settings
            (Required)
```

## 🗃️ Database Schema

### Bảng `users`
```sql
- id (PK)
- firebase_uid (unique)
- email (unique)
- display_name
- role (enum: 'student', 'lecturer', NULL)
- profile_completed (boolean, default: false)
- created_at
- updated_at
```

### Bảng `student_profiles`
```sql
- id (PK)
- user_id (FK -> users.id)
- student_id (MSSV)
- university
- major
- academic_year
- phone
- avatar_url
```

### Bảng `lecturer_profiles`
```sql
- id (PK)
- user_id (FK -> users.id)
- lecturer_id
- university
- department
- degree (enum: 'master', 'phd', 'associate_prof', 'professor')
- research_interests (TEXT)
- phone
- avatar_url
```

## 🔌 API Endpoints

### 1. Get Profile
- **URL**: `/php/api/profile/get-profile.php`
- **Method**: GET
- **Headers**: `Authorization: Bearer {firebase_token}`
- **Response**: User + Student/Lecturer profile

### 2. Update Profile
- **URL**: `/php/api/profile/update-profile.php`
- **Method**: POST
- **Headers**: `Authorization: Bearer {firebase_token}`
- **Body**: JSON with profile fields
- **Validation**: Kiểm tra các trường bắt buộc

### 3. Check Profile Completeness
- **URL**: `/php/api/profile/check-complete.php`
- **Method**: GET
- **Headers**: `Authorization: Bearer {firebase_token}`
- **Response**: `{ complete: true/false, missing_fields: [] }`

## 🎨 UI Components

### 1. Dashboard
- Banner cảnh báo nếu chưa hoàn thiện profile
- Nút "Settings" ở header
- Hiển thị thông tin profile đầy đủ

### 2. Settings Page
- Form động dựa trên role (student/lecturer)
- Validation real-time
- Upload avatar
- Lưu và cập nhật

### 3. Register Page
- Thêm radio button chọn role
- Form thông tin cơ bản
- Redirect to Settings sau đăng ký

## ✅ Validation Rules

### Sinh Viên:
- MSSV: 8-10 ký tự số
- Số điện thoại: 10 số
- Email: Format email hợp lệ
- Các trường khác: không rỗng

### Giảng Viên:
- Mã GV: 4-10 ký tự
- Học vị: Trong danh sách cho phép
- Số điện thoại: 10 số
- Research interests: Ít nhất 20 ký tự

## 🔒 Security

- Tất cả API yêu cầu Firebase Authentication token
- Validate token qua Firebase Admin SDK hoặc verify endpoint
- Chỉ user có thể cập nhật profile của chính họ
- SQL Injection protection với prepared statements

## 📝 Testing

Files test trong `/php/test/`:
- `test-profile-api.html` - Test UI cho các API
- `test-update-profile.php` - Test backend validation
- `test-profile-complete-check.php` - Test logic kiểm tra

## 🚀 Deployment Checklist

- [ ] Chạy SQL migrations
- [ ] Cập nhật Firebase rules
- [ ] Test tất cả API endpoints
- [ ] Test UI flow từ đăng ký đến hoàn thiện profile
- [ ] Verify validation rules
- [ ] Test với cả Email/Password và Google login
