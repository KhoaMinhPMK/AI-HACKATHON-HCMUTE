# 🔥 Firestore Quick Reference

## ✅ Đã tích hợp sẵn

### Auth Pages
```
✅ pages/auth/signin.html       → Lưu profile khi đăng nhập
✅ pages/auth/register.html     → Tạo profile khi đăng ký
✅ pages/dashboard/index.html   → Cập nhật lastLogin
```

### Files Created
```
✅ js/firestore-utils.js        → Helper functions
✅ FIRESTORE_GUIDE.md           → Hướng dẫn chi tiết
✅ firestore-test.html          → Testing interface
```

---

## 🚀 Setup trên Firebase Console

### 1. Tạo Database
```
Firebase Console → victoria-908a3 project
→ Firestore Database → Create database
→ Standard edition
→ Location: asia-southeast1 (Singapore)
→ Production mode
```

### 2. Security Rules (QUAN TRỌNG!)
```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /users/{userId} {
      allow read, write: if request.auth.uid == userId;
    }
    match /users/{userId}/chatHistory/{messageId} {
      allow read, write: if request.auth.uid == userId;
    }
  }
}
```

---

## 💻 Sử dụng nhanh

### Import Utils
```javascript
import { 
    initFirestore,
    saveUserProfile,
    getUserProfile,
    saveChatMessage,
    getChatHistory
} from './js/firestore-utils.js';
```

### Lưu Chat
```javascript
// User message
await saveChatMessage(userId, {
    role: 'user',
    text: 'Hello AI!'
});

// AI response
await saveChatMessage(userId, {
    role: 'ai',
    text: 'Hi! How can I help?'
});
```

### Lấy History
```javascript
const messages = await getChatHistory(userId, 50);
// Returns array of 50 most recent messages
```

### Get Profile
```javascript
const profile = await getUserProfile(userId);
console.log(profile.preferences);
```

---

## 🧪 Testing

### Method 1: Test Page
```
Mở: http://localhost:8000/firestore-test.html
Đăng nhập → Test các functions
```

### Method 2: Browser Console
```javascript
// F12 Console trên dashboard
import { saveChatMessage } from '../../js/firestore-utils.js';
await saveChatMessage(auth.currentUser.uid, {
    role: 'user',
    text: 'Test'
});
```

### Method 3: Firebase Console
```
Firestore Database → users collection
Xem realtime data updates
```

---

## 📊 Database Structure

```
/users/{userId}
  ├─ displayName
  ├─ email
  ├─ photoURL
  ├─ createdAt
  ├─ lastLogin
  └─ preferences/
      ├─ theme
      ├─ language
      └─ notifications

/users/{userId}/chatHistory/{messageId}
  ├─ role (user|ai)
  ├─ text
  ├─ timestamp
  └─ metadata
```

---

## 🎯 Next Steps

1. ✅ Setup Firestore database
2. ✅ Add security rules
3. 🔄 Test với firestore-test.html
4. 🔄 Verify data trong Firebase Console
5. ⏳ Integrate vào chat interface
6. ⏳ Add user preferences page

---

## 🆘 Common Issues

**"Missing permissions"**
→ Add security rules

**"Firestore not initialized"**
→ Call `initFirestore(app)` first

**"Document not found"**
→ Login first, profile auto-created

**Can't see data**
→ Check auth, check rules, check console errors

---

**📝 Status: READY TO USE!**
