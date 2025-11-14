/**
 * Firestore Utilities for Victoria AI
 * Quản lý user profiles và chat history
 */

import { getFirestore, doc, setDoc, getDoc, updateDoc, serverTimestamp, collection, addDoc, query, orderBy, limit, getDocs } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-firestore.js";

let db = null;

/**
 * Initialize Firestore
 * @param {Object} app - Firebase app instance
 */
export function initFirestore(app) {
    db = getFirestore(app);
    console.log('✅ Firestore initialized');
}

/**
 * Save or update user profile
 * @param {Object} user - Firebase Auth user object
 * @returns {Promise<Object>} Updated user data
 */
export async function saveUserProfile(user) {
    if (!db) throw new Error('Firestore not initialized');
    
    try {
        const userRef = doc(db, 'users', user.uid);
        const userSnap = await getDoc(userRef);
        
        const userData = {
            displayName: user.displayName || 'User',
            email: user.email,
            photoURL: user.photoURL || null,
            emailVerified: user.emailVerified,
            lastLogin: serverTimestamp(),
            authProvider: user.providerData[0]?.providerId || 'unknown'
        };
        
        if (!userSnap.exists()) {
            // First time - create profile
            userData.createdAt = serverTimestamp();
            userData.preferences = {
                theme: 'light',
                language: 'vi',
                notifications: true
            };
            await setDoc(userRef, userData);
            console.log('✅ User profile created');
        } else {
            // Update existing
            await updateDoc(userRef, userData);
            console.log('✅ User profile updated');
        }
        
        return userData;
    } catch (error) {
        console.error('❌ Error saving user profile:', error);
        throw error;
    }
}

/**
 * Get user profile from Firestore
 * @param {string} userId - User ID
 * @returns {Promise<Object|null>} User data or null
 */
export async function getUserProfile(userId) {
    if (!db) throw new Error('Firestore not initialized');
    
    try {
        const userRef = doc(db, 'users', userId);
        const userSnap = await getDoc(userRef);
        
        if (userSnap.exists()) {
            return { id: userSnap.id, ...userSnap.data() };
        }
        return null;
    } catch (error) {
        console.error('❌ Error getting user profile:', error);
        return null;
    }
}

/**
 * Update user preferences
 * @param {string} userId - User ID
 * @param {Object} preferences - Preferences object
 * @returns {Promise<void>}
 */
export async function updateUserPreferences(userId, preferences) {
    if (!db) throw new Error('Firestore not initialized');
    
    try {
        const userRef = doc(db, 'users', userId);
        await updateDoc(userRef, {
            preferences: preferences,
            updatedAt: serverTimestamp()
        });
        console.log('✅ Preferences updated');
    } catch (error) {
        console.error('❌ Error updating preferences:', error);
        throw error;
    }
}

/**
 * Save a chat message
 * @param {string} userId - User ID
 * @param {Object} message - Message object { role: 'user'|'ai', text: string }
 * @returns {Promise<string>} Message ID
 */
export async function saveChatMessage(userId, message) {
    if (!db) throw new Error('Firestore not initialized');
    
    try {
        const messagesRef = collection(db, 'users', userId, 'chatHistory');
        const docRef = await addDoc(messagesRef, {
            role: message.role, // 'user' or 'ai'
            text: message.text,
            timestamp: serverTimestamp(),
            metadata: message.metadata || {}
        });
        
        console.log('✅ Message saved:', docRef.id);
        return docRef.id;
    } catch (error) {
        console.error('❌ Error saving message:', error);
        throw error;
    }
}

/**
 * Get chat history for a user
 * @param {string} userId - User ID
 * @param {number} maxMessages - Maximum number of messages to retrieve (default: 50)
 * @returns {Promise<Array>} Array of messages
 */
export async function getChatHistory(userId, maxMessages = 50) {
    if (!db) throw new Error('Firestore not initialized');
    
    try {
        const messagesRef = collection(db, 'users', userId, 'chatHistory');
        const q = query(messagesRef, orderBy('timestamp', 'desc'), limit(maxMessages));
        const querySnapshot = await getDocs(q);
        
        const messages = [];
        querySnapshot.forEach((doc) => {
            messages.push({
                id: doc.id,
                ...doc.data()
            });
        });
        
        // Reverse to get chronological order (oldest first)
        return messages.reverse();
    } catch (error) {
        console.error('❌ Error getting chat history:', error);
        return [];
    }
}

/**
 * Clear chat history for a user
 * @param {string} userId - User ID
 * @returns {Promise<void>}
 */
export async function clearChatHistory(userId) {
    if (!db) throw new Error('Firestore not initialized');
    
    try {
        const messagesRef = collection(db, 'users', userId, 'chatHistory');
        const querySnapshot = await getDocs(messagesRef);
        
        const deletePromises = [];
        querySnapshot.forEach((doc) => {
            deletePromises.push(deleteDoc(doc.ref));
        });
        
        await Promise.all(deletePromises);
        console.log('✅ Chat history cleared');
    } catch (error) {
        console.error('❌ Error clearing chat history:', error);
        throw error;
    }
}

/**
 * Get database instance (for advanced operations)
 * @returns {Object} Firestore database instance
 */
export function getDatabase() {
    if (!db) throw new Error('Firestore not initialized');
    return db;
}
