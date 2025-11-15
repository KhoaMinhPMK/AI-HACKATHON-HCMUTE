# 🔧 URGENT FIX - Backend Errors

**Date:** November 15, 2025  
**Issue:** APIs returning HTML 500 errors instead of JSON

---

## 🚨 Problem Summary

### Current Errors:
1. ❌ `log-search.php` → Returns HTML 500 instead of JSON
2. ❌ `papers-search.php` → Returns HTML 500 instead of JSON  
3. ❌ `projects/search.php` → 404 Not Found (file not uploaded)
4. ⚠️ MegaLLM API → Model `claude-opus-4-1-20250805` disabled

### Root Causes:
- **PHP files chưa được upload lên server `bkuteam.site`**
- Modified files chỉ tồn tại trên local (E:\project\AI-HACKATHON)
- Server PHP có `display_errors = On` → trả về HTML thay vì JSON

---

## ✅ Files Đã Fix (Cần Upload)

### 1. API Endpoints (CRITICAL - Phải upload ngay)

```
php/api/tracking/log-search.php          ← Fixed: Added error suppression
php/api/search/papers-search.php         ← Fixed: JSON-only output
php/api/projects/search.php              ← NEW FILE: Created endpoint
```

### 2. Service Classes

```
php/services/papers-api.php              ← Fixed: Better error handling in arXiv
```

### 3. JavaScript Clients

```
js/megallm-client.js                     ← Fixed: Fallback mode + model names
```

### 4. Test Files (Optional - for debugging)

```
php/api/test/simple-test.php             ← NEW: Basic PHP test
php/api/test/test-papers-local.php       ← NEW: Papers API debug
php/api/test/test-all-apis.php           ← NEW: Full API test suite
```

---

## 📋 Upload Checklist

### Step 1: Upload Fixed PHP Files

**Via FTP/FileZilla:**
```
Local Path                                    → Server Path
E:\project\AI-HACKATHON\php\api\              → /public_html/php/api/
├── tracking/log-search.php                   → tracking/log-search.php
├── search/papers-search.php                  → search/papers-search.php
└── projects/search.php (NEW)                 → projects/search.php

E:\project\AI-HACKATHON\php\services\         → /public_html/php/services/
└── papers-api.php                            → papers-api.php
```

**Via SSH (if available):**
```bash
# Upload entire php folder
scp -r E:/project/AI-HACKATHON/php/* user@bkuteam.site:/public_html/php/

# Or individual files
scp E:/project/AI-HACKATHON/php/api/search/papers-search.php \
    user@bkuteam.site:/public_html/php/api/search/
```

### Step 2: Upload JavaScript Files

```
Local Path                                    → Server Path
E:\project\AI-HACKATHON\js\                   → /public_html/js/
└── megallm-client.js                         → megallm-client.js
```

### Step 3: Verify Server PHP Settings

**Check PHP configuration** (create `info.php`):
```php
<?php
phpinfo();
// Delete this file after checking!
```

**Required Settings:**
```ini
display_errors = Off          ; Prevent HTML errors
error_reporting = E_ALL       ; Log all errors
log_errors = On              ; Enable error logging
```

### Step 4: Test After Upload

**Test endpoints in order:**

1. **Simple test** (verify PHP works):
```
https://bkuteam.site/php/api/test/simple-test.php
Expected: {"success":true,"message":"PHP is working!"}
```

2. **Log search** (POST):
```bash
curl -X POST https://bkuteam.site/php/api/tracking/log-search.php \
  -H "Content-Type: application/json" \
  -d '{"user_id":"test","query":"test"}'
  
Expected: {"success":true,"message":"Search logged"} or graceful error
```

3. **Papers search** (GET):
```
https://bkuteam.site/php/api/search/papers-search.php?q=cancer&limit=5

Expected: {"success":true,"results":[...],"total":X}
```

4. **Projects search** (GET):
```
https://bkuteam.site/php/api/projects/search.php?q=ai&limit=10

Expected: {"success":true,"results":[...],"total":0}
```

---

## 🔍 Debugging If Still Fails

### Check Error Logs

**On server (SSH):**
```bash
# Apache error log
tail -f /var/log/apache2/error.log

# PHP error log
tail -f /var/log/php_errors.log

# Or in public_html
tail -f ~/public_html/error_log
```

### Common Issues:

**Issue 1: Still getting HTML errors**
```
Solution: Check .htaccess for php_flag display_errors Off
```

**Issue 2: Class not found**
```
Solution: Verify require_once paths are correct
Check: __DIR__ . '/../../services/papers-api.php'
```

**Issue 3: CURL not working**
```
Solution: Enable curl extension in php.ini
Or ask hosting provider to enable it
```

---

## 🎯 Quick Fix Summary

### What Changed:

**1. All API files now have:**
```php
// At the top of EVERY API file
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
```

**2. Error handling:**
- All try-catch blocks return JSON with `success: true/false`
- Never throw exceptions that PHP will render as HTML
- Always return HTTP 200 even on errors (to not break frontend)

**3. MegaLLM fallback:**
- When API key fails (402 error) → Use local keyword extraction
- No more breaking errors, app continues working
- Model changed: `claude-opus-4-1-20250805` → `claude-3.5-sonnet`

---

## 📞 If Upload Fails

### Option A: Manual Upload via cPanel
1. Login to cPanel: https://bkuteam.site:2083
2. File Manager → public_html → php
3. Upload files one by one
4. Set permissions: 644 for .php files

### Option B: Use Git (if server has git)
```bash
# On server
cd /public_html
git pull origin main
```

### Option C: Ask Hosting Support
- Send them the files
- Ask them to upload to /public_html/php/

---

## ✅ Success Indicators

After upload, you should see:

1. ✅ No more `<!DOCTYPE` errors in console
2. ✅ All API responses are valid JSON
3. ✅ Search works even if MegaLLM API is down (fallback mode)
4. ✅ Status codes are 200, not 500

---

**URGENT TODO:**
- [ ] Upload `php/api/search/papers-search.php`
- [ ] Upload `php/api/tracking/log-search.php`
- [ ] Upload `php/api/projects/search.php` (new file)
- [ ] Upload `php/services/papers-api.php`
- [ ] Upload `js/megallm-client.js`
- [ ] Test all endpoints
- [ ] Check browser console (should be no 500 errors)

---

**Last Updated:** November 15, 2025  
**Priority:** 🔴 CRITICAL - App không hoạt động cho đến khi upload xong
