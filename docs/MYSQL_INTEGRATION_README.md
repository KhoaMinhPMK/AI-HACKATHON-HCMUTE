# 🔥 Firebase + MySQL Hybrid Authentication System

## 📋 Tổng quan

Hệ thống xác thực kết hợp **Firebase Authentication** (frontend) và **MySQL** (backend) cho Victoria AI.

### Tại sao dùng cả 2?

| Tính năng | Firebase | MySQL (VPS) |
|-----------|----------|-------------|
| Realtime sync | ✅ | ❌ |
| Google OAuth | ✅ | ❌ |
| Complex queries | ❌ | ✅ |
| Analytics/Reports | ❌ | ✅ |
| Data ownership | Limited | ✅ Full |
| Free tier limits | 50K ops/day | ♾️ Unlimited |
| Backup control | Auto | ✅ Manual |

**→ Best of both worlds!**

---

## 🗂️ Cấu trúc thư mục

```
AI-HACKATHON/
├── sql/                          # SQL scripts
│   ├── 01_create_database.sql
│   ├── 02_create_tables.sql
│   ├── 03_create_indexes.sql
│   └── 04_insert_sample_data.sql
│
├── php/                          # PHP Backend API
│   ├── config/
│   │   └── database.php         # MySQL config
│   ├── helpers/
│   │   ├── response.php         # JSON responses
│   │   └── validator.php        # Input validation
│   └── api/
│       ├── auth/
│       │   ├── sync-user.php    # Sync Firebase → MySQL
│       │   ├── verify-token.php # Verify token
│       │   └── update-token.php # Update tokens
│       ├── logs/
│       │   └── activity-log.php # Log activities
│       └── test-connection.php  # Test endpoint
│
├── js/
│   └── mysql-api-client.js      # Frontend API client
│
├── pages/
│   ├── auth/
│   │   ├── signin.html          # With MySQL sync
│   │   └── register.html        # With MySQL sync
│   └── dashboard/
│       └── index.html           # With activity logging
│
└── docs/                         # Documentation
    ├── PHP_API_GUIDE.md
    ├── FRONTEND_INTEGRATION.md
    └── DEPLOYMENT_CHECKLIST.md
```

---

## 🗄️ Database Schema

### Tables (5)

1. **users** - User accounts from Firebase
2. **auth_tokens** - OAuth access/refresh tokens
3. **activity_logs** - User activity tracking
4. **chat_history** - AI chat messages (future)
5. **user_preferences** - User settings

### Key Fields

**users:**
```sql
- firebase_uid (unique)
- email (unique)
- display_name
- photo_url
- email_verified
- auth_provider (google|password)
- created_at, last_login
```

**activity_logs:**
```sql
- user_id
- action (login, logout, register, etc)
- ip_address
- user_agent
- metadata (JSON)
- created_at
```

---

## 🚀 Quick Start

### 1. Setup Database (5 phút)

```sql
-- Login phpMyAdmin: https://pma.bkuteam.site
-- User: root, Pass: 123456

-- Run scripts in order:
SOURCE sql/01_create_database.sql;
SOURCE sql/02_create_tables.sql;
SOURCE sql/03_create_indexes.sql;
```

### 2. Deploy PHP API (5 phút)

```bash
# Upload php/ folder to VPS
# Set permissions
chmod 755 php/ -R
chmod 777 php/cache/

# Test connection
curl https://your-domain.com/php/api/test-connection.php
```

### 3. Configure Frontend (2 phút)

Edit `js/mysql-api-client.js`:
```javascript
const MYSQL_API_BASE_URL = 'https://your-domain.com/php/api';
const MYSQL_SYNC_ENABLED = true;
```

### 4. Test (3 phút)

1. Register/Login on your app
2. Check browser console: "User synced to MySQL"
3. Verify in phpMyAdmin: SELECT * FROM users;

---

## 📡 API Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/auth/sync-user.php` | POST | Sync Firebase user to MySQL |
| `/api/auth/verify-token.php` | POST | Verify Firebase token |
| `/api/auth/update-token.php` | POST | Update OAuth tokens |
| `/api/logs/activity-log.php` | POST | Log user activity |
| `/api/test-connection.php` | GET | Test MySQL connection |

See [PHP_API_GUIDE.md](PHP_API_GUIDE.md) for details.

---

## 🔄 Data Flow

```
User clicks "Login with Google"
         ↓
Firebase Auth (Google OAuth)
         ↓
Frontend gets: user object + idToken
         ↓
Call: syncUserToMySQL(user, idToken)
         ↓
PHP API: sync-user.php
         ↓
MySQL: INSERT/UPDATE users table
         ↓
MySQL: INSERT activity_logs
         ↓
Return: { success: true, user: {...} }
```

---

## 💻 Code Examples

### Register with Email (signin.html)

```javascript
import { syncUserToMySQL, logActivity } from '../../js/mysql-api-client.js';

try {
    const userCredential = await signInWithEmailAndPassword(auth, email, password);
    const user = userCredential.user;
    const idToken = await user.getIdToken();
    
    // Sync to MySQL (non-blocking)
    syncUserToMySQL(user, idToken);
    
    // Log activity
    logActivity(user.uid, 'email_login');
    
    // Redirect
    window.location.href = '../dashboard/index.html';
    
} catch (error) {
    console.error(error);
}
```

### Google Login

```javascript
try {
    const result = await signInWithPopup(auth, googleProvider);
    const user = result.user;
    const idToken = await user.getIdToken();
    
    // Sync to MySQL
    await syncUserToMySQL(user, idToken);
    
    // Log activity
    await logActivity(user.uid, 'google_login');
    
    // Redirect
    window.location.href = '../dashboard/index.html';
    
} catch (error) {
    console.error(error);
}
```

---

## 🔒 Security

### Implemented:
- ✅ SQL injection protection (prepared statements)
- ✅ Input validation & sanitization
- ✅ Rate limiting (60 req/hour)
- ✅ CORS headers
- ✅ Error logging
- ✅ Token verification

### Todo (Production):
- [ ] Change MySQL password
- [ ] HTTPS only
- [ ] Update CORS to specific domain
- [ ] Firebase Admin SDK for token verification
- [ ] Environment variables for secrets
- [ ] Regular security audits

---

## 📊 Use Cases

### What you can do with MySQL that Firebase can't:

1. **Complex Analytics**
   ```sql
   -- Users by registration date
   SELECT DATE(created_at), COUNT(*) 
   FROM users 
   GROUP BY DATE(created_at);
   
   -- Most active users
   SELECT u.email, COUNT(a.id) as activity_count
   FROM users u
   JOIN activity_logs a ON u.id = a.user_id
   GROUP BY u.id
   ORDER BY activity_count DESC
   LIMIT 10;
   ```

2. **Advanced Reports**
   - Daily/Monthly active users
   - User retention analysis
   - Feature usage statistics
   - Export to CSV/Excel

3. **Full-text Search**
   ```sql
   -- Search chat history
   SELECT * FROM chat_history 
   WHERE MATCH(message) AGAINST('keyword');
   ```

4. **Custom Queries**
   - Join multiple tables
   - Complex WHERE conditions
   - Aggregate functions
   - Subqueries

---

## 🧪 Testing

### Test MySQL Connection
```bash
curl https://your-domain.com/php/api/test-connection.php
```

### Test User Sync
```javascript
// Browser console
import { syncUserToMySQL } from './js/mysql-api-client.js';
await syncUserToMySQL(auth.currentUser, await auth.currentUser.getIdToken());
```

### Verify in Database
```sql
-- Check users
SELECT * FROM users ORDER BY created_at DESC LIMIT 5;

-- Check activity logs
SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10;
```

---

## 📚 Documentation

| File | Description |
|------|-------------|
| [PHP_API_GUIDE.md](PHP_API_GUIDE.md) | Complete API documentation |
| [FRONTEND_INTEGRATION.md](FRONTEND_INTEGRATION.md) | How to integrate frontend |
| [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md) | Step-by-step deployment |

---

## 🐛 Troubleshooting

### "Database connection failed"
- Check MySQL is running
- Verify credentials in `php/config/database.php`
- Test: `mysql -u root -p victoria_ai`

### "CORS error"
- Update CORS headers in `php/helpers/response.php`
- Clear browser cache
- Use HTTPS

### "User not synced"
- Check `MYSQL_SYNC_ENABLED = true`
- Verify API URL is correct
- Check browser console for errors
- Test API endpoint with cURL

### "Rate limit exceeded"
- Wait 1 hour or clear rate limit cache
- Adjust limits in `php/helpers/validator.php`

---

## 📈 Performance Tips

1. **Database indexes** - Already created in script 03
2. **Connection pooling** - Use persistent connections
3. **Caching** - Cache frequent queries
4. **Async operations** - Don't block UI
5. **Batch operations** - Group similar requests

---

## 🎯 Roadmap

### Completed ✅
- [x] Database schema design
- [x] PHP API endpoints
- [x] Frontend integration utilities
- [x] Documentation
- [x] Security basics

### Next Steps ⏳
1. Deploy to VPS
2. Test end-to-end
3. Add Firebase Admin SDK for token verification
4. Implement caching layer (Redis)
5. Add more API endpoints (user CRUD, analytics)
6. Build admin dashboard
7. Set up automated backups

---

## 💡 Tips

- **Start with test data**: Use `04_insert_sample_data.sql` to populate test data
- **Non-blocking sync**: MySQL sync errors won't crash your app
- **Monitor logs**: Check `activity_logs` table regularly
- **Backup database**: Schedule daily backups
- **Use phpMyAdmin**: Great for quick data inspection

---

## 🆘 Support

### Logs Location:
- PHP errors: `/var/log/php_errors.log`
- MySQL errors: Check `SHOW ENGINE INNODB STATUS;`
- Browser: DevTools Console (F12)

### Common Issues:
See [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md) → Troubleshooting section

---

**🎉 System Ready!** Firebase + MySQL hybrid authentication với:
- ✅ Google OAuth
- ✅ Email/Password auth
- ✅ User profile sync
- ✅ Activity logging
- ✅ Extensible for analytics

**Total setup time: ~30 minutes** ⚡
