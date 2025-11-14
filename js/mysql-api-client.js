/**
 * Victoria AI - MySQL API Client
 * Frontend utility for syncing Firebase auth with MySQL backend
 */

// API Configuration
const MYSQL_API_BASE_URL = 'https://bkuteam.site/php/api'; // ✅ Updated!

// Enable/disable MySQL sync (set false if API not ready)
const MYSQL_SYNC_ENABLED = true; // ✅ Ready to use!

/**
 * Sync user to MySQL database
 * @param {Object} firebaseUser - Firebase user object
 * @param {string} idToken - Firebase ID token
 * @returns {Promise<Object>} API response
 */
export async function syncUserToMySQL(firebaseUser, idToken) {
    if (!MYSQL_SYNC_ENABLED) {
        console.log('📝 MySQL sync disabled - skipping');
        return { success: true, skipped: true };
    }
    
    try {
        const userData = {
            uid: firebaseUser.uid,
            email: firebaseUser.email,
            displayName: firebaseUser.displayName || null,
            photoURL: firebaseUser.photoURL || null,
            emailVerified: firebaseUser.emailVerified,
            provider: firebaseUser.providerData[0]?.providerId === 'google.com' ? 'google' : 'password'
        };
        
        const response = await fetch(`${MYSQL_API_BASE_URL}/auth/sync-user.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                idToken: idToken,
                user: userData
            })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Sync failed');
        }
        
        console.log('✅ User synced to MySQL:', data);
        return data;
        
    } catch (error) {
        console.error('❌ MySQL sync error:', error);
        // Don't throw - app should work even if MySQL sync fails
        return { success: false, error: error.message };
    }
}

/**
 * Verify Firebase token with backend
 * @param {string} idToken - Firebase ID token
 * @returns {Promise<Object>} API response
 */
export async function verifyTokenWithBackend(idToken) {
    if (!MYSQL_SYNC_ENABLED) {
        return { success: true, skipped: true };
    }
    
    try {
        const response = await fetch(`${MYSQL_API_BASE_URL}/auth/verify-token.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ idToken })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Verification failed');
        }
        
        return data;
        
    } catch (error) {
        console.error('❌ Token verification error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Log user activity to MySQL
 * @param {string} firebaseUid - Firebase user ID
 * @param {string} action - Action name (login, logout, etc)
 * @param {Object} metadata - Additional metadata
 * @returns {Promise<Object>} API response
 */
export async function logActivity(firebaseUid, action, metadata = {}) {
    if (!MYSQL_SYNC_ENABLED) {
        return { success: true, skipped: true };
    }
    
    try {
        const response = await fetch(`${MYSQL_API_BASE_URL}/logs/activity-log.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                firebaseUid,
                action,
                metadata
            })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            console.warn('Activity log failed:', data.message);
        }
        
        return data;
        
    } catch (error) {
        console.error('❌ Activity log error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Update OAuth tokens
 * @param {string} firebaseUid - Firebase user ID
 * @param {Object} tokens - Token data
 * @returns {Promise<Object>} API response
 */
export async function updateTokens(firebaseUid, tokens) {
    if (!MYSQL_SYNC_ENABLED) {
        return { success: true, skipped: true };
    }
    
    try {
        const response = await fetch(`${MYSQL_API_BASE_URL}/auth/update-token.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                firebaseUid,
                accessToken: tokens.accessToken || null,
                refreshToken: tokens.refreshToken || null,
                expiresIn: tokens.expiresIn || 3600,
                scope: tokens.scope || null
            })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            console.warn('Token update failed:', data.message);
        }
        
        return data;
        
    } catch (error) {
        console.error('❌ Token update error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Test MySQL API connection
 * @returns {Promise<Object>} Connection status
 */
export async function testMySQLConnection() {
    try {
        const response = await fetch(`${MYSQL_API_BASE_URL}/test-connection.php`);
        const data = await response.json();
        
        console.log('🔗 MySQL Connection Test:', data);
        return data;
        
    } catch (error) {
        console.error('❌ MySQL connection test failed:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Enhanced login handler with MySQL sync
 * @param {Object} firebaseAuth - Firebase auth instance
 * @param {Object} user - Firebase user
 * @returns {Promise<void>}
 */
export async function handleLoginWithSync(firebaseAuth, user) {
    try {
        // Get Firebase ID token
        const idToken = await user.getIdToken();
        
        // Sync to MySQL
        const syncResult = await syncUserToMySQL(user, idToken);
        
        // Log activity
        await logActivity(user.uid, 'login', {
            provider: user.providerData[0]?.providerId,
            timestamp: new Date().toISOString()
        });
        
        return {
            success: true,
            user: user,
            mysqlSynced: syncResult.success
        };
        
    } catch (error) {
        console.error('Login handler error:', error);
        throw error;
    }
}

/**
 * Enhanced logout handler with activity log
 * @param {Object} firebaseAuth - Firebase auth instance
 * @returns {Promise<void>}
 */
export async function handleLogoutWithSync(firebaseAuth) {
    try {
        const user = firebaseAuth.currentUser;
        
        if (user) {
            // Log logout activity
            await logActivity(user.uid, 'logout', {
                timestamp: new Date().toISOString()
            });
        }
        
        // Sign out from Firebase
        await firebaseAuth.signOut();
        
    } catch (error) {
        console.error('Logout handler error:', error);
        throw error;
    }
}
