# Hướng Dẫn Cài Đặt Database - Victoria AI

## 📋 Thông Tin Database

- **Database Name**: `victoria_ai`
- **Character Set**: `utf8mb4`
- **Collation**: `utf8mb4_unicode_ci`
- **VPS**: https://pma.bkuteam.site
- **User**: root
- **Password**: 123456

## 🚀 Cài Đặt Từng Bước

### Bước 1: Tạo Database
Chạy file này đầu tiên để tạo database:

```bash
mysql -u root -p < sql/01_create_database.sql
```

Hoặc trong phpMyAdmin:
1. Mở tab SQL
2. Copy toàn bộ nội dung file `01_create_database.sql`
3. Click "Go"

**Kết quả mong đợi**: Database `victoria_ai` được tạo thành công.

---

### Bước 2: Tạo Bảng Cơ Bản
Chạy file này để tạo các bảng users, auth_tokens, activity_logs, chat_history:

```bash
mysql -u root -p victoria_ai < sql/02_create_tables.sql
```

Hoặc trong phpMyAdmin:
1. Chọn database `victoria_ai` ở sidebar bên trái
2. Mở tab SQL
3. Copy toàn bộ nội dung file `02_create_tables.sql`
4. Click "Go"

**Kết quả mong đợi**: 
- Bảng `users` ✅
- Bảng `auth_tokens` ✅
- Bảng `activity_logs` ✅
- Bảng `chat_history` ✅
- Bảng `user_preferences` ✅

---

### Bước 3: Tạo Bảng Profile (Sinh viên & Giảng viên)
Chạy file này để thêm hệ thống profile:

```bash
mysql -u root -p victoria_ai < sql/05_create_profile_tables.sql
```

Hoặc trong phpMyAdmin:
1. Đảm bảo đang chọn database `victoria_ai`
2. Mở tab SQL
3. Copy toàn bộ nội dung file `05_create_profile_tables.sql`
4. Click "Go"

**Kết quả mong đợi**:
- Bảng `users` được thêm cột `role`, `profile_completed`, `phone` ✅
- Bảng `student_profiles` được tạo ✅
- Bảng `lecturer_profiles` được tạo ✅
- Bảng `profile_update_logs` được tạo ✅
- Views: `v_students`, `v_lecturers`, `v_available_lecturers` ✅
- Stored Procedure: `check_profile_complete` ✅
- Triggers: Auto-check profile completeness ✅

---

### Bước 4: Tạo Indexes (Tối ưu hiệu suất)
```bash
mysql -u root -p victoria_ai < sql/03_create_indexes.sql
```

Hoặc trong phpMyAdmin:
1. Chọn database `victoria_ai`
2. Mở tab SQL
3. Copy nội dung file `03_create_indexes.sql`
4. Click "Go"

**Kết quả mong đợi**: Các composite indexes được tạo để tăng tốc truy vấn.

---

### Bước 5 (Optional): Insert Sample Data
Chỉ dùng cho môi trường testing/development:

```bash
mysql -u root -p victoria_ai < sql/04_insert_sample_data.sql
```

**LƯU Ý**: KHÔNG chạy trên production!

---

## ✅ Kiểm Tra Cài Đặt

Sau khi chạy xong tất cả các script, kiểm tra:

```sql
-- 1. Kiểm tra database
SHOW DATABASES LIKE 'victoria_ai';

-- 2. Chọn database
USE victoria_ai;

-- 3. Xem tất cả bảng
SHOW TABLES;

-- Kết quả mong đợi:
-- +-------------------------+
-- | Tables_in_victoria_ai   |
-- +-------------------------+
-- | activity_logs           |
-- | auth_tokens             |
-- | chat_history            |
-- | lecturer_profiles       |
-- | profile_update_logs     |
-- | student_profiles        |
-- | user_preferences        |
-- | users                   |
-- +-------------------------+

-- 4. Kiểm tra cấu trúc bảng users
DESCRIBE users;

-- Phải có các cột:
-- - role (ENUM: student, lecturer)
-- - profile_completed (BOOLEAN)
-- - phone (VARCHAR)

-- 5. Kiểm tra Views
SHOW FULL TABLES WHERE TABLE_TYPE LIKE 'VIEW';

-- Kết quả mong đợi:
-- v_students
-- v_lecturers
-- v_available_lecturers

-- 6. Kiểm tra Stored Procedures
SHOW PROCEDURE STATUS WHERE Db = 'victoria_ai';

-- Kết quả: check_profile_complete
```

---

## 🔧 Troubleshooting

### Lỗi: Unknown database 'victoria_db'
**Nguyên nhân**: Tên database sai.
**Giải pháp**: Đảm bảo tất cả file SQL dùng `victoria_ai` (không phải `victoria_db`).

### Lỗi: Table 'users' already exists
**Nguyên nhân**: Bảng đã tồn tại.
**Giải pháp**: 
1. Nếu muốn reset: Drop database và tạo lại
```sql
DROP DATABASE victoria_ai;
```
2. Nếu muốn giữ dữ liệu: Chạy migration script thay vì create script

### Lỗi: Cannot add foreign key constraint
**Nguyên nhân**: Bảng cha (users) chưa tồn tại.
**Giải pháp**: Chạy đúng thứ tự: 01 → 02 → 05 → 03 → 04

### Lỗi: Duplicate column name 'role'
**Nguyên nhân**: Cột đã được thêm trước đó.
**Giải pháp**: Bỏ qua lỗi này (script có `IF NOT EXISTS`) hoặc dùng migration script.

---

## 🔄 Migration cho Database Hiện Có

Nếu bạn đã có database `victoria_ai` với dữ liệu và muốn thêm hệ thống profile:

1. **Backup trước khi migrate**:
```bash
mysqldump -u root -p victoria_ai > backup_$(date +%Y%m%d).sql
```

2. **Chạy migration script**:
```bash
mysql -u root -p victoria_ai < sql/06_migration_add_profiles.sql
```

Migration script sẽ:
- Kiểm tra và chỉ thêm cột mới nếu chưa có
- Không xóa dữ liệu hiện tại
- Đánh dấu user cũ là `profile_completed = FALSE`

3. **Nếu có lỗi, rollback**:
Uncomment phần rollback trong file `06_migration_add_profiles.sql` và chạy.

---

## 📝 Thứ Tự Chạy File SQL

### Cài Đặt Mới (Fresh Install):
```
1. 01_create_database.sql    ← Tạo database
2. 02_create_tables.sql       ← Tạo bảng cơ bản
3. 05_create_profile_tables.sql ← Thêm profile system
4. 03_create_indexes.sql      ← Tối ưu indexes
5. 04_insert_sample_data.sql  ← (Optional) Test data
```

### Cập Nhật Database Hiện Có:
```
1. 06_migration_add_profiles.sql ← Migration script
```

---

## 🎯 Quick Setup (All-in-One)

Nếu muốn setup nhanh toàn bộ:

```bash
# Linux/Mac
mysql -u root -p < sql/01_create_database.sql && \
mysql -u root -p victoria_ai < sql/02_create_tables.sql && \
mysql -u root -p victoria_ai < sql/05_create_profile_tables.sql && \
mysql -u root -p victoria_ai < sql/03_create_indexes.sql

# Windows PowerShell
Get-Content sql\01_create_database.sql | mysql -u root -p
Get-Content sql\02_create_tables.sql | mysql -u root -p victoria_ai
Get-Content sql\05_create_profile_tables.sql | mysql -u root -p victoria_ai
Get-Content sql\03_create_indexes.sql | mysql -u root -p victoria_ai
```

---

## ✨ Hoàn Thành!

Sau khi setup xong, bạn có thể:
1. ✅ Đăng ký user với role (student/lecturer)
2. ✅ Cập nhật profile qua Settings page
3. ✅ API backend đã sẵn sàng
4. ✅ Validation và security đã được implement

Chúc bạn thành công! 🎉

noteId: "6b0b19c0c20511f092b0fdf5dc8510aa"
tags: []

---

