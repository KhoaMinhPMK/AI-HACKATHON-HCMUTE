/**
 * Victoria AI - Authentication Guard
 * Bảo vệ các trang yêu cầu đăng nhập
 * 
 * Usage:
 * import { requireAuth, getCurrentUser } from './auth-guard.js';
 * await requireAuth(); // Redirect nếu chưa login
 */

import { initializeApp } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-app.js";
import { getAuth, onAuthStateChanged, setPersistence, browserLocalPersistence } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-auth.js";

// Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyA8zc27rx6YIJoyoXyf7dugS-zCjazE6lU",
    authDomain: "victoria-908a3.firebaseapp.com",
    projectId: "victoria-908a3",
    storageBucket: "victoria-908a3.firebasestorage.app",
    messagingSenderId: "906906328836",
    appId: "1:906906328836:web:b050d66d1b178f03f4fa51",
    measurementId: "G-DGG9GE81Z7"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);

// Enable persistent login (lưu vào localStorage)
setPersistence(auth, browserLocalPersistence)
    .then(() => {
        console.log('✅ Persistent login enabled');
    })
    .catch((error) => {
        console.warn('⚠️ Persistence error:', error.message);
    });

/**
 * Require authentication
 * Redirect to signin if not logged in
 * @param {string} redirectUrl - URL to redirect after login
 * @returns {Promise<User>} Firebase user object
 */
export async function requireAuth(redirectUrl = null) {
    return new Promise((resolve, reject) => {
        // Show loading
        showAuthLoading(true);
        
        const unsubscribe = onAuthStateChanged(auth, (user) => {
            unsubscribe(); // Cleanup listener
            
            if (user) {
                // User is signed in
                console.log('✅ User authenticated:', user.email);
                
                // Save redirect URL to session (if provided)
                if (redirectUrl) {
                    sessionStorage.setItem('auth_redirect', redirectUrl);
                }
                
                // Update last activity
                localStorage.setItem('last_activity', Date.now().toString());
                
                showAuthLoading(false);
                resolve(user);
            } else {
                // No user - redirect to signin
                console.log('❌ No user - redirecting to signin');
                
                // Save current URL for redirect after login
                const currentPath = window.location.pathname;
                if (!currentPath.includes('signin') && !currentPath.includes('register')) {
                    sessionStorage.setItem('return_url', window.location.href);
                }
                
                showAuthLoading(false);
                
                // Determine signin page path based on current location
                const signinPath = getSigninPath();
                window.location.href = signinPath;
                
                reject(new Error('Not authenticated'));
            }
        });
        
        // Timeout after 10 seconds
        setTimeout(() => {
            unsubscribe();
            showAuthLoading(false);
            reject(new Error('Auth check timeout'));
        }, 10000);
    });
}

/**
 * Get current authenticated user
 * @returns {User|null} Firebase user or null
 */
export function getCurrentUser() {
    return auth.currentUser;
}

/**
 * Check if user is authenticated
 * @returns {boolean}
 */
export function isAuthenticated() {
    return auth.currentUser !== null;
}

/**
 * Get Firebase Auth instance
 * @returns {Auth}
 */
export function getAuthInstance() {
    return auth;
}

/**
 * Logout user
 * @returns {Promise<void>}
 */
export async function logout() {
    try {
        await auth.signOut();
        
        // Clear session data
        sessionStorage.clear();
        localStorage.removeItem('last_activity');
        
        console.log('✅ Logged out successfully');
        
        // Redirect to home
        window.location.href = '/';
    } catch (error) {
        console.error('❌ Logout error:', error);
        throw error;
    }
}

/**
 * Auto logout after inactivity
 * @param {number} minutes - Minutes of inactivity before logout
 */
export function setupAutoLogout(minutes = 30) {
    const INACTIVITY_TIMEOUT = minutes * 60 * 1000;
    
    let timeoutId;
    
    function resetTimer() {
        clearTimeout(timeoutId);
        localStorage.setItem('last_activity', Date.now().toString());
        
        timeoutId = setTimeout(() => {
            console.log('⏰ Auto logout due to inactivity');
            logout();
        }, INACTIVITY_TIMEOUT);
    }
    
    // Reset timer on user activity
    ['mousedown', 'keydown', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, resetTimer, true);
    });
    
    // Check last activity on load
    const lastActivity = localStorage.getItem('last_activity');
    if (lastActivity) {
        const elapsed = Date.now() - parseInt(lastActivity);
        if (elapsed > INACTIVITY_TIMEOUT) {
            console.log('⏰ Session expired');
            logout();
            return;
        }
    }
    
    resetTimer();
}

/**
 * Get signin page path based on current location
 * @returns {string} Path to signin page
 */
function getSigninPath() {
    const currentPath = window.location.pathname;
    
    // If in dashboard folder
    if (currentPath.includes('/dashboard/')) {
        return '../auth/signin.html';
    }
    
    // If in other pages folder
    if (currentPath.includes('/pages/')) {
        return '../auth/signin.html';
    }
    
    // Default
    return '/pages/auth/signin.html';
}

/**
 * Show/hide loading overlay during auth check
 * @param {boolean} show
 */
function showAuthLoading(show) {
    let overlay = document.getElementById('authLoadingOverlay');
    
    if (!overlay && show) {
        // Create overlay if doesn't exist
        overlay = document.createElement('div');
        overlay.id = 'authLoadingOverlay';
        overlay.style.cssText = `
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            gap: 1rem;
        `;
        
        overlay.innerHTML = `
            <div style="
                width: 50px;
                height: 50px;
                border: 4px solid #e5e7eb;
                border-top-color: #5cc0eb;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
            "></div>
            <p style="font-size: 1rem; color: #6b7280; font-weight: 600;">Đang xác thực...</p>
            <style>
                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
            </style>
        `;
        
        document.body.appendChild(overlay);
    }
    
    if (overlay) {
        overlay.style.display = show ? 'flex' : 'none';
    }
}

/**
 * Redirect after successful login
 */
export function handlePostLogin() {
    // Check if there's a return URL
    const returnUrl = sessionStorage.getItem('return_url');
    
    if (returnUrl) {
        sessionStorage.removeItem('return_url');
        window.location.href = returnUrl;
    } else {
        // Default redirect to dashboard
        window.location.href = '/pages/dashboard/index.html';
    }
}

/**
 * Check session validity
 * @returns {boolean}
 */
export function isSessionValid() {
    const lastActivity = localStorage.getItem('last_activity');
    if (!lastActivity) return false;
    
    const THIRTY_MINUTES = 30 * 60 * 1000;
    const elapsed = Date.now() - parseInt(lastActivity);
    
    return elapsed < THIRTY_MINUTES;
}

// Export auth instance
export { auth };

console.log('🔐 Auth Guard loaded');

