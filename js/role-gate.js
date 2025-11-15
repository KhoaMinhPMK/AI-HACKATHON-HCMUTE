/**
 * Victoria AI - Role Gate Middleware
 * Bắt buộc user phải có role và profile đủ trước khi dùng app
 * 
 * Usage:
 * import { requireRole, redirectToDashboard } from './role-gate.js';
 * const { user, role, profile } = await requireRole();
 */

import { requireAuth } from './auth-guard.js';

const API_BASE = 'https://bkuteam.site/php/api/profile';

/**
 * Require role - Bắt buộc phải có role và profile hoàn chỉnh
 * @returns {Promise<{user, role, profile}>}
 */
export async function requireRole() {
    // Step 1: Check authentication
    const user = await requireAuth();
    
    // Step 2: Get profile from API
    try {
        const token = await user.getIdToken();
        const response = await fetch(`${API_BASE}/check-complete.php`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to check profile');
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }
        
        const data = result.data;
        
        // Step 3: Check if role is set
        if (!data.role) {
            console.log('⚠️ Role not set - redirecting to onboarding');
            sessionStorage.setItem('return_url', window.location.href);
            window.location.href = '/pages/onboarding/select-role.html';
            throw new Error('Role required');
        }
        
        // Step 4: Check if profile is complete
        if (!data.complete) {
            console.log('⚠️ Profile incomplete - redirecting to onboarding');
            sessionStorage.setItem('return_url', window.location.href);
            window.location.href = '/pages/onboarding/complete-profile.html';
            throw new Error('Profile incomplete');
        }
        
        // Step 5: All good!
        console.log('✅ Role check passed:', data.role);
        
        return {
            user: user,
            role: data.role,
            profile: data,
            profileCompleted: true
        };
        
    } catch (error) {
        console.error('❌ Role check failed:', error);
        throw error;
    }
}

/**
 * Redirect to appropriate dashboard based on role
 * @param {string} role - 'student' or 'lecturer'
 */
export function redirectToDashboard(role) {
    if (role === 'lecturer') {
        window.location.href = '/pages/dashboard/lecturer/index.html';
    } else if (role === 'student') {
        window.location.href = '/pages/dashboard/student/index.html';
    } else {
        console.error('Unknown role:', role);
        window.location.href = '/pages/onboarding/select-role.html';
    }
}

/**
 * Check role only (không redirect)
 * @returns {Promise<string|null>} Role or null
 */
export async function checkRole() {
    try {
        const user = await requireAuth();
        const token = await user.getIdToken();
        
        const response = await fetch(`${API_BASE}/get-profile.php`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.data.user) {
            return result.data.user.role;
        }
        
        return null;
    } catch (error) {
        console.error('Check role error:', error);
        return null;
    }
}

/**
 * Get full profile data
 * @returns {Promise<Object>}
 */
export async function getFullProfile() {
    try {
        const user = await requireAuth();
        const token = await user.getIdToken();
        
        const response = await fetch(`${API_BASE}/get-profile.php`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            return result.data;
        }
        
        throw new Error(result.message);
    } catch (error) {
        console.error('Get profile error:', error);
        throw error;
    }
}

/**
 * Require specific role
 * @param {string} requiredRole - 'student' or 'lecturer'
 */
export async function requireSpecificRole(requiredRole) {
    const { user, role, profile } = await requireRole();
    
    if (role !== requiredRole) {
        console.error(`❌ Access denied: Required ${requiredRole}, got ${role}`);
        alert(`Trang này chỉ dành cho ${requiredRole === 'lecturer' ? 'Giảng viên' : 'Sinh viên'}`);
        redirectToDashboard(role); // Redirect về dashboard đúng của họ
        throw new Error('Role mismatch');
    }
    
    return { user, role, profile };
}

console.log('🚪 Role Gate loaded');

