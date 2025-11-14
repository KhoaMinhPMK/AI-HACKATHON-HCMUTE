# 🚀 Victoria AI - PHP MySQL Integration

## 📋 Setup Instructions

### Step 1: Run SQL Scripts on phpMyAdmin

Login to: https://pma.bkuteam.site
- User: `root`
- Pass: `123456`

Execute SQL files **in order**:

1. **01_create_database.sql** - Tạo database `victoria_ai`
2. **02_create_tables.sql** - Tạo các bảng
3. **03_create_indexes.sql** - Tạo indexes
4. **04_insert_sample_data.sql** - (Optional) Thêm data mẫu

### Step 2: Upload PHP Files to VPS

Upload folder `php/` lên VPS của bạn:
```
/var/www/html/php/          (hoặc document root của bạn)
├── config/
├── helpers/
└── api/
```

### Step 3: Test Connection

Visit: `https://your-domain.com/php/api/test-connection.php`

Expected response:
```json
{
  "success": true,
  "message": "Database connection successful",
  "data": {
    "database": "victoria_ai",
    "mysql_version": "8.0.x",
    "tables_count": 5,
    "users_count": 0
  }
}
```

---

## 📡 API Endpoints

### 1. Sync User (Register/Login)
**POST** `/php/api/auth/sync-user.php`

Request body:
```json
{
  "idToken": "firebase_id_token_here",
  "user": {
    "uid": "firebase_user_id",
    "email": "user@example.com",
    "displayName": "User Name",
    "photoURL": "https://...",
    "emailVerified": true,
    "provider": "google"
  }
}
```

Response (201 Created or 200 OK):
```json
{
  "success": true,
  "message": "User created successfully",
  "data": {
    "user": {
      "id": 1,
      "firebase_uid": "...",
      "email": "...",
      "display_name": "...",
      "created_at": "..."
    },
    "action": "register"
  }
}
```

### 2. Verify Token
**POST** `/php/api/auth/verify-token.php`

Request:
```json
{
  "idToken": "firebase_id_token"
}
```

Response:
```json
{
  "success": true,
  "message": "Token verified successfully",
  "data": {
    "user": {...},
    "firebase_data": {...}
  }
}
```

### 3. Update Tokens
**POST** `/php/api/auth/update-token.php`

Request:
```json
{
  "firebaseUid": "user_firebase_id",
  "accessToken": "oauth_access_token",
  "refreshToken": "oauth_refresh_token",
  "expiresIn": 3600,
  "scope": "email profile"
}
```

### 4. Log Activity
**POST** `/php/api/logs/activity-log.php`

Request:
```json
{
  "firebaseUid": "user_firebase_id",
  "action": "login",
  "metadata": {
    "source": "web",
    "device": "desktop"
  }
}
```

---

## 🔧 Configuration

### Update `php/config/database.php` if needed:

```php
define('DB_HOST', 'localhost');  // Your MySQL host
define('DB_NAME', 'victoria_ai');
define('DB_USER', 'root');
define('DB_PASS', '123456');
```

### CORS Settings

In production, update `php/helpers/response.php`:
```php
header('Access-Control-Allow-Origin: https://your-domain.com');
```

---

## 🗄️ Database Schema

### Tables Created:

1. **users** - User accounts
2. **auth_tokens** - OAuth tokens
3. **activity_logs** - User activity tracking
4. **chat_history** - Chat messages
5. **user_preferences** - User settings

### Key Relationships:

```
users (1) -----> (N) auth_tokens
users (1) -----> (N) activity_logs
users (1) -----> (N) chat_history
users (1) -----> (1) user_preferences
```

---

## 🧪 Testing

### Test with cURL:

```bash
# Test connection
curl https://your-domain.com/php/api/test-connection.php

# Sync user
curl -X POST https://your-domain.com/php/api/auth/sync-user.php \
  -H "Content-Type: application/json" \
  -d '{
    "idToken": "your_token",
    "user": {
      "uid": "test123",
      "email": "test@test.com",
      "displayName": "Test User",
      "emailVerified": true,
      "provider": "google"
    }
  }'
```

---

## 🔐 Security Notes

1. ✅ All inputs are validated and sanitized
2. ✅ SQL injection protection with prepared statements
3. ✅ Rate limiting implemented (60 req/hour per IP)
4. ✅ CORS headers configured
5. ⚠️ In production, verify Firebase tokens server-side
6. ⚠️ Use HTTPS only
7. ⚠️ Change default database password

---

## 📊 Rate Limits

- Sync user: 60 requests/hour per IP
- Other endpoints: 100 requests/hour per IP

---

## 🐛 Troubleshooting

### "Database connection failed"
- Check MySQL is running
- Verify credentials in `config/database.php`
- Check database `victoria_ai` exists

### "Permission denied"
- Check file permissions (755 for directories, 644 for files)
- Ensure PHP has write access to `php/cache/` folder

### "Invalid token"
- Token must be from Firebase Authentication
- Token expires after 1 hour

---

## 📞 Support

Xem logs tại:
- PHP errors: `/var/log/php_errors.log`
- MySQL errors: Check `information_schema`

---

**✅ Setup Complete! MySQL + Firebase hybrid system ready!** 🎉
