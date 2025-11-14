# ✅ Deployment Checklist - MySQL Integration

## 📦 Files Created

### SQL Scripts (`sql/`)
- [x] `01_create_database.sql` - Tạo database victoria_ai
- [x] `02_create_tables.sql` - 5 tables (users, auth_tokens, activity_logs, chat_history, user_preferences)
- [x] `03_create_indexes.sql` - Performance indexes
- [x] `04_insert_sample_data.sql` - Sample data (optional)

### PHP Backend (`php/`)
- [x] `config/database.php` - MySQL connection config
- [x] `helpers/response.php` - JSON response utilities
- [x] `helpers/validator.php` - Input validation
- [x] `api/auth/sync-user.php` - Sync Firebase user to MySQL
- [x] `api/auth/verify-token.php` - Verify Firebase token
- [x] `api/auth/update-token.php` - Update OAuth tokens
- [x] `api/logs/activity-log.php` - Log user activities
- [x] `api/test-connection.php` - Test MySQL connection

### Frontend (`js/`)
- [x] `mysql-api-client.js` - API client utilities

### Documentation
- [x] `PHP_API_GUIDE.md` - Complete API documentation
- [x] `FRONTEND_INTEGRATION.md` - Integration guide

---

## 🚀 Deployment Steps

### Phase 1: Database Setup (10 phút)

1. **Login phpMyAdmin**
   - URL: https://pma.bkuteam.site
   - User: root
   - Pass: 123456

2. **Execute SQL Scripts**
   ```
   Run in order:
   ✅ 01_create_database.sql
   ✅ 02_create_tables.sql
   ✅ 03_create_indexes.sql
   ✅ 04_insert_sample_data.sql (optional)
   ```

3. **Verify Tables**
   ```sql
   USE victoria_ai;
   SHOW TABLES;
   -- Should show: users, auth_tokens, activity_logs, chat_history, user_preferences
   ```

### Phase 2: Upload PHP Files (5 phút)

1. **Upload to VPS**
   ```
   Upload folder php/ to:
   /var/www/html/php/   (or your document root)
   ```

2. **Set Permissions**
   ```bash
   chmod 755 php/
   chmod 755 php/config/
   chmod 755 php/helpers/
   chmod 755 php/api/
   chmod 644 php/**/*.php
   
   # Create cache directory
   mkdir -p php/cache/rate-limits
   chmod 777 php/cache/
   ```

3. **Update Config**
   Edit `php/config/database.php` if needed:
   ```php
   define('DB_HOST', 'localhost'); // Change if remote MySQL
   define('DB_NAME', 'victoria_ai');
   define('DB_USER', 'root');
   define('DB_PASS', '123456'); // CHANGE IN PRODUCTION!
   ```

### Phase 3: Test Backend (5 phút)

1. **Test Connection**
   ```
   Visit: https://your-domain.com/php/api/test-connection.php
   
   Expected:
   {
     "success": true,
     "data": {
       "database": "victoria_ai",
       "connection_status": "connected",
       "tables_count": 5
     }
   }
   ```

2. **Test with cURL**
   ```bash
   # Test sync-user endpoint
   curl -X POST https://your-domain.com/php/api/auth/sync-user.php \
     -H "Content-Type: application/json" \
     -d '{
       "idToken": "dummy_token_for_test",
       "user": {
         "uid": "test123",
         "email": "test@test.com",
         "displayName": "Test",
         "emailVerified": true,
         "provider": "password"
       }
     }'
   ```

### Phase 4: Frontend Integration (10 phút)

1. **Update API URL**
   Edit `js/mysql-api-client.js`:
   ```javascript
   const MYSQL_API_BASE_URL = 'https://your-actual-domain.com/php/api';
   ```

2. **Enable Sync**
   ```javascript
   const MYSQL_SYNC_ENABLED = true;
   ```

3. **Add Imports** (see FRONTEND_INTEGRATION.md)
   - signin.html
   - register.html
   - dashboard/index.html

4. **Test in Browser**
   - Open DevTools Console (F12)
   - Login/Register
   - Check for: "User synced to MySQL"

### Phase 5: Verify Data (5 phút)

1. **Check phpMyAdmin**
   ```sql
   -- View users
   SELECT * FROM users ORDER BY created_at DESC LIMIT 10;
   
   -- View activity logs
   SELECT u.email, a.action, a.created_at 
   FROM activity_logs a 
   JOIN users u ON a.user_id = u.id 
   ORDER BY a.created_at DESC 
   LIMIT 20;
   ```

2. **Test Complete Flow**
   - Register new user → Check `users` table
   - Login → Check `activity_logs`
   - Dashboard → Verify lastLogin updated

---

## 🔒 Security Hardening

### Before Production:

- [ ] Change MySQL password from "123456"
- [ ] Update CORS to specific domain (not *)
- [ ] Enable HTTPS only
- [ ] Set up SSL certificate
- [ ] Add rate limiting (already implemented)
- [ ] Enable error logging
- [ ] Disable phpMyAdmin public access
- [ ] Set up database backups
- [ ] Use environment variables for credentials
- [ ] Enable firewall rules

### Recommended Changes:

**1. Update `php/helpers/response.php`:**
```php
// Change from:
header('Access-Control-Allow-Origin: *');

// To:
header('Access-Control-Allow-Origin: https://your-domain.com');
```

**2. Create `.htaccess` for PHP API:**
```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Block direct PHP file access (except API)
<FilesMatch "\.php$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Allow API endpoints
<FilesMatch "(sync-user|verify-token|update-token|activity-log|test-connection)\.php$">
    Allow from all
</FilesMatch>
```

**3. Create `php.ini` config:**
```ini
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
max_execution_time = 30
memory_limit = 128M
post_max_size = 10M
upload_max_filesize = 10M
```

---

## 📊 Monitoring & Maintenance

### Daily Checks:
- [ ] Check error logs: `/var/log/php_errors.log`
- [ ] Monitor database size
- [ ] Check failed login attempts

### Weekly Tasks:
- [ ] Review activity logs
- [ ] Check API response times
- [ ] Database optimization: `OPTIMIZE TABLE users;`
- [ ] Clean old logs (>90 days)

### Monthly Tasks:
- [ ] Database backup
- [ ] Security audit
- [ ] Update dependencies
- [ ] Review rate limit settings

---

## 🐛 Troubleshooting

### "Database connection failed"
```bash
# Check MySQL is running
systemctl status mysql

# Test connection
mysql -u root -p victoria_ai
```

### "Permission denied" errors
```bash
# Fix permissions
chown -R www-data:www-data /var/www/html/php
chmod 755 /var/www/html/php
```

### "CORS error" in browser
- Update CORS headers in `php/helpers/response.php`
- Clear browser cache
- Check browser DevTools Network tab

### "Token verification failed"
- Firebase token expires after 1 hour
- Get fresh token: `await user.getIdToken(true)`
- Check Firebase API key is correct

---

## 📈 Performance Optimization

### Database:
```sql
-- Add composite indexes for common queries
CREATE INDEX idx_user_email_provider ON users(email, auth_provider);
CREATE INDEX idx_logs_user_date ON activity_logs(user_id, created_at);

-- Analyze tables
ANALYZE TABLE users, activity_logs, chat_history;
```

### PHP:
- Enable OPcache
- Use connection pooling
- Cache frequently accessed data

### Frontend:
- Batch API calls when possible
- Cache user data in localStorage
- Use service workers for offline support

---

## ✅ Final Checklist

Before going live:

- [ ] All SQL scripts executed successfully
- [ ] PHP files uploaded and permissions set
- [ ] Test connection endpoint working
- [ ] Frontend integrated and tested
- [ ] Security hardening applied
- [ ] HTTPS enabled
- [ ] Backups configured
- [ ] Error logging enabled
- [ ] Monitoring set up
- [ ] Documentation reviewed

---

**🎉 Ready to deploy!** Follow checklist step by step.

**Next Steps:**
1. Execute Phase 1-5 sequentially
2. Test each phase before moving to next
3. Apply security hardening
4. Go live! 🚀
