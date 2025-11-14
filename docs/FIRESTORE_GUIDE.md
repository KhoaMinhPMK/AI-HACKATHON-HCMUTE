# 🔥 Firestore Integration Guide

## 📋 Tổng quan

Firestore đã được tích hợp vào Victoria AI để lưu trữ:
- ✅ **User Profiles** - Thông tin người dùng
- ✅ **Chat History** - Lịch sử chat với AI
- ✅ **User Preferences** - Cài đặt & tùy chọn

---

## 🚀 Setup Firestore trên Firebase Console

### Bước 1: Tạo Database
1. Vào [Firebase Console](https://console.firebase.google.com/)
2. Chọn project **victoria-908a3**
3. **Firestore Database** → **Create database**
4. Chọn **Standard edition**
5. Location: **asia-southeast1 (Singapore)** hoặc **asia-east1 (Taiwan)**
6. Security rules: Chọn **Production mode** (sẽ config sau)

### Bước 2: Config Security Rules
Vào **Firestore Database** → **Rules** tab, paste code này:

```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    
    // User profiles - chỉ owner mới đọc/ghi được
    match /users/{userId} {
      allow read, write: if request.auth != null && request.auth.uid == userId;
    }
    
    // Chat history - chỉ owner mới truy cập được
    match /users/{userId}/chatHistory/{messageId} {
      allow read, write: if request.auth != null && request.auth.uid == userId;
    }
  }
}
```

Click **Publish** để áp dụng.

---

## 📊 Cấu trúc Database

### Collection: `users`
```javascript
users/{userId}/
  ├── displayName: string
  ├── email: string
  ├── photoURL: string | null
  ├── emailVerified: boolean
  ├── authProvider: 'password' | 'google.com'
  ├── createdAt: timestamp
  ├── lastLogin: timestamp
  └── preferences: {
      ├── theme: 'light' | 'dark'
      ├── language: 'vi' | 'en'
      └── notifications: boolean
  }
```

### Subcollection: `chatHistory`
```javascript
users/{userId}/chatHistory/{messageId}/
  ├── role: 'user' | 'ai'
  ├── text: string
  ├── timestamp: timestamp
  └── metadata: object (optional)
```

---

## 💻 Cách sử dụng

### Đã tự động tích hợp:
✅ **Đăng ký/Đăng nhập** → Tự động lưu user profile  
✅ **Dashboard** → Tự động cập nhật lastLogin  
✅ **Auth flow** → Firestore đã được import và config

### Sử dụng Firestore Utils (cho tính năng mới):

#### Import module:
```javascript
import { 
    initFirestore, 
    saveUserProfile,
    getUserProfile,
    updateUserPreferences,
    saveChatMessage,
    getChatHistory 
} from './js/firestore-utils.js';

// Initialize
import { initializeApp } from 'firebase/app';
const app = initializeApp(firebaseConfig);
initFirestore(app);
```

#### Lưu chat message:
```javascript
// User gửi tin nhắn
await saveChatMessage(userId, {
    role: 'user',
    text: 'Xin chào Victoria AI!'
});

// AI trả lời
await saveChatMessage(userId, {
    role: 'ai',
    text: 'Chào bạn! Tôi có thể giúp gì?',
    metadata: { model: 'gpt-4', tokens: 150 }
});
```

#### Lấy lịch sử chat:
```javascript
const history = await getChatHistory(userId, 50); // 50 tin nhắn gần nhất
console.log(history);
/*
[
  {
    id: 'msg123',
    role: 'user',
    text: 'Xin chào',
    timestamp: { seconds: 1700000000 }
  },
  {
    id: 'msg124',
    role: 'ai',
    text: 'Chào bạn!',
    timestamp: { seconds: 1700000005 }
  }
]
*/
```

#### Cập nhật preferences:
```javascript
await updateUserPreferences(userId, {
    theme: 'dark',
    language: 'en',
    notifications: false
});
```

---

## 🧪 Test Firestore

### 1. Test đăng ký/đăng nhập:
- Đăng ký user mới → Check Firestore Console
- Vào **Firestore Database** → Collection `users`
- Xem document với userId mới tạo

### 2. Test từ Console:
```javascript
// Mở Console (F12) trên dashboard
import { saveChatMessage, getChatHistory } from '../../js/firestore-utils.js';

// Lưu message
const auth = getAuth();
await saveChatMessage(auth.currentUser.uid, {
    role: 'user',
    text: 'Test message'
});

// Lấy history
const history = await getChatHistory(auth.currentUser.uid);
console.log(history);
```

---

## 📈 Giới hạn Free Tier

Firebase Firestore **Standard Edition** miễn phí:
- ✅ **Reads**: 50,000 documents/day
- ✅ **Writes**: 20,000 documents/day
- ✅ **Deletes**: 20,000 documents/day
- ✅ **Storage**: 1 GB
- ✅ **Network**: 10 GB/month

→ Đủ cho development và small/medium apps!

---

## 🔐 Bảo mật

✅ **Security Rules** đã config: Chỉ owner mới truy cập data của mình  
✅ **Auth required**: Phải đăng nhập mới read/write  
✅ **No admin access**: Không ai có thể đọc data của user khác  

---

## 🎯 Tính năng tiếp theo

Sau khi Firestore hoạt động, bạn có thể:
1. **Chat interface** với AI + lưu history
2. **User settings page** để thay đổi preferences
3. **Activity dashboard** hiển thị stats & analytics
4. **Search history** tìm kiếm trong chat cũ
5. **Export data** tải xuống chat history

---

## 🆘 Troubleshooting

### Lỗi: "Missing or insufficient permissions"
→ Check Security Rules, đảm bảo user đã login

### Lỗi: "Firestore not initialized"
→ Gọi `initFirestore(app)` trước khi dùng utils

### Không thấy data trong Firestore Console
→ Check Network tab (F12), xem có lỗi 403/401 không

---

**✅ Setup hoàn tất! Firestore đã sẵn sàng sử dụng!** 🎉
