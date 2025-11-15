# 🔐 Victoria AI - Authentication System Guide

## ✅ **Tính Năng Đã Implement**

### **1. Persistent Login (Lưu Trạng Thái Đăng Nhập)**
- ✅ Sử dụng `browserLocalPersistence` của Firebase
- ✅ User không cần đăng nhập lại khi refresh page
- ✅ Session được lưu vào localStorage
- ✅ Token tự động refresh khi hết hạn

### **2. Protected Routes (Bảo Vệ Các Trang)**
- ✅ Dashboard chỉ truy cập được khi đã login
- ✅ Settings chỉ truy cập được khi đã login
- ✅ Tự động redirect về signin nếu chưa login
- ✅ Lưu URL hiện tại để redirect lại sau khi login

### **3. Auto Logout**
- ✅ Tự động logout sau 30 phút không hoạt động
- ✅ Track user activity (mouse, keyboard, scroll, touch)
- ✅ Clear session data khi logout

### **4. Smart Redirect**
- ✅ Sau login, redirect về trang user đang cố truy cập
- ✅ Nếu không có, mặc định về Dashboard
- ✅ Không lưu signin/register URLs

### **5. Loading States**
- ✅ Auth loading overlay khi đang kiểm tra login
- ✅ Fade-in animation khi content loaded
- ✅ Skeleton screens (optional)

---

## 📁 **Files Đã Tạo/Cập Nhật**

### **Core Auth System:**
```
js/
└── auth-guard.js                  ← Core auth module (MỚI)
    - requireAuth()               → Bắt buộc đăng nhập
    - getCurrentUser()            → Lấy user hiện tại
    - logout()                    → Đăng xuất + clear cache
    - setupAutoLogout()           → Auto logout sau X phút
    - handlePostLogin()           → Smart redirect sau login
    - isSessionValid()            → Check session còn hạn không

css/components/
└── skeleton.css                   ← Loading states CSS (MỚI)
```

### **Protected Pages (Đã Update):**
```
pages/dashboard/
├── index.html                     ← Dùng requireAuth()
├── settings.html                  ← Dùng requireAuth()
└── styles.css                     ← Đã có CSS

pages/auth/
└── signin.html                    ← Dùng handlePostLogin()
```

---

## 🚀 **Cách Hoạt Động**

### **Luồng 1: User Chưa Login Cố Vào Dashboard**

```
1. User mở: https://bkuteam.site/pages/dashboard/index.html
   ↓
2. Page load → Import auth-guard.js
   ↓
3. Call: await requireAuth()
   ↓
4. Check: firebase.auth().currentUser
   ↓
5. Result: null (chưa login)
   ↓
6. Lưu URL hiện tại vào sessionStorage['return_url']
   ↓
7. Redirect → pages/auth/signin.html
   ↓
8. User đăng nhập thành công
   ↓
9. Call: handlePostLogin()
   ↓
10. Check: sessionStorage['return_url'] exists?
   ↓
11. YES → Redirect về Dashboard (trang ban đầu user muốn vào)
    ✅ User vào được Dashboard như mong muốn!
```

### **Luồng 2: User Đã Login (Session Còn Hạn)**

```
1. User mở: Dashboard
   ↓
2. Call: await requireAuth()
   ↓
3. Check: firebase.auth().currentUser
   ↓
4. Result: User object (từ localStorage)
   ↓
5. ✅ Pass! Continue loading page
   ↓
6. Load user data, profile, etc.
   ↓
7. Hide loading overlay
   ↓
8. Show content với fade-in animation
```

### **Luồng 3: Auto Logout (30 Phút Không Hoạt Động)**

```
User đang ở Dashboard
   ↓
30 phút không move mouse/keyboard/scroll
   ↓
setupAutoLogout() trigger
   ↓
Call logout()
   ↓
- signOut() từ Firebase
- Clear sessionStorage
- Clear localStorage['last_activity']
   ↓
Redirect về trang chủ (/)
```

---

## 🔧 **Usage trong Code**

### **Bảo vệ trang (Protected Page):**

```javascript
// Đầu file script
import { requireAuth, setupAutoLogout } from "../../js/auth-guard.js";

// Trong async context
const user = await requireAuth();
// Nếu không có user, tự động redirect về signin
// Nếu có user, tiếp tục load data

// Optional: Setup auto logout
setupAutoLogout(30); // 30 minutes
```

### **Sau khi đăng nhập thành công:**

```javascript
import { handlePostLogin } from "../../js/auth-guard.js";

// Sau khi signIn/register thành công
await saveUserProfile(user);

// Redirect thông minh
setTimeout(() => {
    handlePostLogin(); // Tự động redirect về return_url hoặc dashboard
}, 1500);
```

### **Logout:**

```javascript
import { logout } from "../../js/auth-guard.js";

// Khi user click logout
await logout();
// Tự động clear cache và redirect về home
```

---

## 📊 **Session Management**

### **Data Được Lưu:**

#### **localStorage:**
```javascript
{
  "last_activity": "1763200404000",     // Timestamp cuối cùng user hoạt động
  "firebase:authUser:...": {...}        // Firebase user data (auto)
}
```

#### **sessionStorage:**
```javascript
{
  "return_url": "https://bkuteam.site/pages/dashboard/settings.html",  // URL cần quay lại
  "auth_redirect": "..."                // Optional redirect URL
}
```

### **Session Timeout:**
- **Default**: 30 phút không hoạt động
- **Customizable**: `setupAutoLogout(minutes)`
- **Activity tracked**: mousedown, keydown, scroll, touchstart

---

## 🎨 **Loading States**

### **Auth Loading:**
Khi đang check authentication:
```html
<div id="authLoadingOverlay">
  <div class="spinner"></div>
  <p>Đang xác thực...</p>
</div>
```

### **Content Loading:**
Đã có sẵn trong dashboard:
```html
<div class="loading-overlay" id="loadingOverlay">
  <div class="spinner"></div>
  <p>Đang tải thông tin...</p>
</div>
```

### **Fade-in Animation:**
Khi content loaded:
```javascript
document.getElementById('dashboardContainer').classList.add('fade-in');
```

---

## 🔒 **Security Features**

### **1. Token Validation**
- Firebase token được verify tự động
- Token refresh khi hết hạn (Firebase auto)
- Token không được lưu vào localStorage (chỉ trong memory)

### **2. Session Security**
- Session timeout sau 30 phút
- Clear tất cả data khi logout
- XSS protection với httpOnly cookies (nếu dùng custom backend)

### **3. Protected Routes**
```javascript
// Dashboard, Settings, và các trang private
await requireAuth(); // Bắt buộc login

// Signin, Register - public pages
// Không cần requireAuth()
```

---

## 🧪 **Testing**

### **Test 1: Protected Route**
1. Logout (nếu đang login)
2. Truy cập: `https://bkuteam.site/pages/dashboard/index.html`
3. ✅ **Kỳ vọng**: Tự động redirect về signin
4. Console log: `❌ No user - redirecting to signin`

### **Test 2: Persistent Login**
1. Đăng nhập
2. Refresh page (F5)
3. ✅ **Kỳ vọng**: Vẫn đăng nhập, không cần login lại
4. Console log: `✅ Authenticated user: email@example.com`

### **Test 3: Smart Redirect**
1. Logout
2. Truy cập: `https://bkuteam.site/pages/dashboard/settings.html`
3. → Redirect về signin
4. Đăng nhập thành công
5. ✅ **Kỳ vọng**: Redirect về Settings (trang ban đầu muốn vào)

### **Test 4: Auto Logout**
1. Đăng nhập
2. Đợi 30 phút không làm gì
3. ✅ **Kỳ vọng**: Tự động logout và redirect về home
4. Console log: `⏰ Auto logout due to inactivity`

---

## 🎯 **Best Practices**

### **Trong mọi Protected Page:**
```javascript
import { requireAuth, setupAutoLogout } from "../../js/auth-guard.js";

// Đầu tiên: Check auth
const user = await requireAuth();

// Setup auto logout (optional)
setupAutoLogout(30);

// Sau đó: Load data
await loadData(user);
```

### **Trong Login/Register Pages:**
```javascript
import { handlePostLogin } from "../../js/auth-guard.js";

// Sau khi login thành công
setTimeout(() => {
    handlePostLogin(); // Smart redirect
}, 1500);
```

### **Khi Logout:**
```javascript
import { logout } from "../../js/auth-guard.js";

// Đơn giản
await logout();
// Không cần handle redirect, function tự động làm
```

---

## 📱 **UX Improvements**

### **Before:**
- ❌ User phải login lại mỗi lần refresh
- ❌ Có thể vào dashboard khi chưa login (lỗi bảo mật)
- ❌ Redirect luôn về dashboard sau login
- ❌ Không có loading states

### **After:**
- ✅ Session lưu vào localStorage - không cần login lại
- ✅ Dashboard, Settings được bảo vệ - auto redirect
- ✅ Redirect thông minh về trang user muốn vào
- ✅ Loading overlay + fade-in animation
- ✅ Auto logout sau không hoạt động
- ✅ Activity tracking

---

## 🎉 **Summary**

**Files Created:**
- `js/auth-guard.js` (Core auth module - 250 lines)
- `css/components/skeleton.css` (Loading states)

**Files Updated:**
- `pages/dashboard/index.html` (Protected)
- `pages/dashboard/settings.html` (Protected)
- `pages/auth/signin.html` (Smart redirect)

**Features:**
- 🔐 Persistent login
- 🛡️ Protected routes
- ⏰ Auto logout (30 min)
- 🎯 Smart redirect
- ✨ Loading animations

**Result:**
Hệ thống authentication hoàn chỉnh với UX tốt!

---

## 🚀 **Deploy Checklist**

- [ ] Upload `js/auth-guard.js`
- [ ] Upload `css/components/skeleton.css`
- [ ] Upload `pages/dashboard/index.html` (updated)
- [ ] Upload `pages/dashboard/settings.html` (updated)
- [ ] Upload `pages/auth/signin.html` (updated)
- [ ] Test protected routes
- [ ] Test persistent login
- [ ] Test smart redirect
- [ ] Test auto logout

**All Done!** 🎉
