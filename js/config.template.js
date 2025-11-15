/**
 * API Configuration Template
 * 
 * SETUP INSTRUCTIONS:
 * 1. Copy this file to js/config.js
 * 2. Fill in your actual API keys
 * 3. NEVER commit js/config.js to Git (it's in .gitignore)
 */

const API_CONFIG = {
    // Groq API for reasoning AI (gpt-oss-120b)
    // Get key from: https://console.groq.com/keys
    GROQ_API_KEY: 'YOUR_GROQ_API_KEY_HERE', // Replace with: gsk_...
    
    // MegaLLM API (optional, for fallback)
    MEGALLM_API_KEY: 'mega-llm-cad3cf13be2c4d0a9e66de5e76fd3d84-1-dRy9k',
    
    // Firebase Config (already public, safe to commit)
    FIREBASE: {
        apiKey: "AIzaSyA8zc27rx6YIJoyoXyf7dugS-zCjazE6lU",
        authDomain: "victoria-908a3.firebaseapp.com",
        projectId: "victoria-908a3",
        storageBucket: "victoria-908a3.firebasestorage.app",
        messagingSenderId: "906906328836",
        appId: "1:906906328836:web:b050d66d1b178f03f4fa51",
        measurementId: "G-DGG9GE81Z7"
    }
};

// Export for use in other scripts
if (typeof window !== 'undefined') {
    window.API_CONFIG = API_CONFIG;
}
