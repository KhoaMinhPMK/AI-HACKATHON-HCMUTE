# 🔄 Frontend Integration Guide

## Tích hợp MySQL API vào Auth Pages

### Step 1: Update API Base URL

Mở `js/mysql-api-client.js`, đổi:
```javascript
const MYSQL_API_BASE_URL = 'https://your-vps-domain.com/php/api';
```
→ URL thật của VPS bạn

### Step 2: Enable MySQL Sync

```javascript
const MYSQL_SYNC_ENABLED = true; // Bật sau khi deploy PHP API
```

### Step 3: Integrate vào signin.html

Thêm import và gọi sync function:

```javascript
// Add to top of script
import { syncUserToMySQL, logActivity } from '../../js/mysql-api-client.js';

// In signInWithEmailAndPassword success handler:
try {
    const userCredential = await signInWithEmailAndPassword(auth, email, password);
    const user = userCredential.user;
    
    // Get ID token
    const idToken = await user.getIdToken();
    
    // Sync to MySQL (non-blocking)
    syncUserToMySQL(user, idToken).catch(err => {
        console.warn('MySQL sync failed (non-critical):', err);
    });
    
    // Log activity
    logActivity(user.uid, 'email_login');
    
    // Continue with redirect...
}
```

### Step 4: Integrate vào register.html

```javascript
import { syncUserToMySQL, logActivity } from '../../js/mysql-api-client.js';

// In createUserWithEmailAndPassword success handler:
try {
    const userCredential = await createUserWithEmailAndPassword(auth, email, password);
    const user = userCredential.user;
    
    await updateProfile(user, { displayName: name });
    await user.reload();
    
    // Get token and sync
    const idToken = await auth.currentUser.getIdToken();
    await syncUserToMySQL(auth.currentUser, idToken);
    
    // Log registration
    await logActivity(user.uid, 'register', { method: 'email' });
    
    // Continue...
}
```

### Step 5: Integrate Google Login

```javascript
// In signInWithPopup success handler:
try {
    const result = await signInWithPopup(auth, googleProvider);
    const user = result.user;
    
    // Get ID token
    const idToken = await user.getIdToken();
    
    // Sync to MySQL
    await syncUserToMySQL(user, idToken);
    
    // Log activity
    await logActivity(user.uid, 'google_login');
    
    // If Google provides access token:
    const credential = GoogleAuthProvider.credentialFromResult(result);
    if (credential?.accessToken) {
        await updateTokens(user.uid, {
            accessToken: credential.accessToken,
            expiresIn: 3600
        });
    }
    
    // Continue with redirect...
}
```

### Step 6: Integrate vào Dashboard

```javascript
import { logActivity } from '../../js/mysql-api-client.js';

// In onAuthStateChanged:
onAuthStateChanged(auth, async (user) => {
    if (user) {
        // Existing code...
        
        // Log dashboard view
        logActivity(user.uid, 'dashboard_view').catch(err => {
            console.warn('Activity log failed:', err);
        });
    }
});
```

---

## 🧪 Testing Workflow

### 1. Test MySQL Connection

```javascript
import { testMySQLConnection } from './js/mysql-api-client.js';

// Run in browser console
testMySQLConnection().then(console.log);
```

Expected output:
```json
{
  "success": true,
  "data": {
    "database": "victoria_ai",
    "connection_status": "connected"
  }
}
```

### 2. Test User Sync

1. Đăng nhập vào app
2. Mở Browser Console (F12)
3. Check logs:
   - ✅ "User synced to MySQL"
   - ✅ "Activity logged"

4. Verify trong phpMyAdmin:
   - Table `users` → Có user mới
   - Table `activity_logs` → Có log "login"

### 3. Test Error Handling

Tắt MySQL hoặc sai config → App vẫn hoạt động bình thường (non-blocking)

---

## 🔄 Sync Strategy

### Non-blocking Approach (Recommended)

```javascript
// App không crash nếu MySQL fail
syncUserToMySQL(user, token).catch(err => {
    console.warn('MySQL sync failed, continuing...', err);
});

// Continue with app logic
redirectToDashboard();
```

### Blocking Approach (Optional)

```javascript
// Đợi MySQL sync xong mới redirect
try {
    await syncUserToMySQL(user, token);
    redirectToDashboard();
} catch (error) {
    alert('Sync failed, please try again');
}
```

---

## 📊 What Gets Synced

### On Register/Login:
- ✅ User profile (email, name, photo)
- ✅ Firebase UID
- ✅ Email verification status
- ✅ Auth provider (Google/Email)
- ✅ Created/Last login timestamps

### On Activity:
- ✅ User actions (login, logout, etc)
- ✅ IP address
- ✅ User agent
- ✅ Timestamps

### OAuth Tokens (if available):
- ✅ Access token
- ✅ Refresh token
- ✅ Expiration time

---

## 🎯 Benefits

### Firebase + MySQL Hybrid:
- ✅ Firebase: Realtime auth, easy integration
- ✅ MySQL: Complex queries, analytics, reports
- ✅ Redundancy: Backup if Firebase has issues
- ✅ Control: Full ownership of user data

### Use Cases:
- 📊 Generate reports (users by date, active users, etc)
- 🔍 Advanced search across chat history
- 📈 Analytics dashboard
- 💾 Data export to CSV/Excel
- 🔐 Audit logs for compliance

---

## ⚡ Performance Tips

1. **Async operations**: Don't block UI
2. **Batch logs**: Send activity logs in batches (not every action)
3. **Cache tokens**: Store access tokens locally, update only when expired
4. **Error handling**: Always catch and handle MySQL errors gracefully

---

## 🔒 Security Checklist

- [ ] API chỉ chạy trên HTTPS
- [ ] CORS configured đúng domain
- [ ] Rate limiting enabled
- [ ] Input validation & sanitization
- [ ] Firebase tokens verified server-side
- [ ] Sensitive data encrypted
- [ ] Database password changed from default

---

**Ready to integrate!** Làm theo từng bước, test kỹ mỗi phần! 🚀
