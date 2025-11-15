/**
 * Victoria AI - Search Tracker
 * Track mọi search và interaction của student (như Google Analytics)
 * Để giảng viên có thể monitor progress
 */

class SearchTracker {
    constructor(userId) {
        this.userId = userId;
        this.sessionId = this.generateSessionId();
        this.currentSearch = null;
        this.currentPaper = null;
        this.timeStart = null;
        this.apiBase = 'https://bkuteam.site/php/api/tracking';
    }
    
    /**
     * Generate unique session ID
     */
    generateSessionId() {
        return `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }
    
    /**
     * Log search query
     * @param {string} query - Search query
     * @param {Array} results - Search results
     * @param {string} type - 'papers', 'projects', 'mentors'
     */
    async logSearch(query, results, type = 'papers') {
        try {
            const data = {
                user_id: this.userId,
                query: query,
                search_type: type,
                results_count: results.length,
                session_id: this.sessionId,
                timestamp: new Date().toISOString()
            };
            
            this.currentSearch = {
                ...data,
                searchId: null
            };
            
            const response = await fetch(`${this.apiBase}/log-search.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            if (result.success) {
                this.currentSearch.searchId = result.data.search_id;
                console.log('✅ Search logged:', query);
            }
            
        } catch (error) {
            console.error('❌ Log search error:', error);
        }
    }
    
    /**
     * Log khi user click vào result (paper/project)
     * @param {string} itemId - Paper ID hoặc Project ID
     * @param {string} itemType - 'paper' hoặc 'project'
     */
    async logClick(itemId, itemType) {
        try {
            if (!this.currentSearch) return;
            
            await fetch(`${this.apiBase}/log-click.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: this.userId,
                    search_id: this.currentSearch.searchId,
                    item_id: itemId,
                    item_type: itemType,
                    search_query: this.currentSearch.query
                })
            });
            
            console.log('✅ Click logged:', itemId);
            
        } catch (error) {
            console.error('❌ Log click error:', error);
        }
    }
    
    /**
     * Log paper interaction (view, save, cite)
     * @param {Object} paper - Paper data
     * @param {string} interactionType - 'view', 'save', 'cite', 'download'
     */
    async logPaperInteraction(paper, interactionType) {
        try {
            await fetch(`${this.apiBase}/log-paper-interaction.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: this.userId,
                    paper_id: paper.id,
                    paper_title: paper.title,
                    paper_authors: paper.authors,
                    paper_year: paper.year,
                    paper_source: paper.source,
                    interaction_type: interactionType,
                    search_query: this.currentSearch?.query
                })
            });
            
            console.log(`✅ Paper ${interactionType} logged:`, paper.title);
            
        } catch (error) {
            console.error('❌ Log interaction error:', error);
        }
    }
    
    /**
     * Start tracking time spent on paper
     * @param {string} paperId
     */
    startTimeTracking(paperId, paperTitle) {
        this.stopTimeTracking(); // Stop previous tracking
        
        this.timeStart = Date.now();
        this.currentPaper = {
            id: paperId,
            title: paperTitle
        };
        
        console.log('⏱️ Time tracking started:', paperTitle);
    }
    
    /**
     * Stop tracking và gửi time spent
     */
    async stopTimeTracking() {
        if (this.timeStart && this.currentPaper) {
            const timeSpent = Math.floor((Date.now() - this.timeStart) / 1000);
            
            if (timeSpent > 5) { // Chỉ log nếu > 5s (không phải click nhầm)
                try {
                    await fetch(`${this.apiBase}/update-time-spent.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_id: this.userId,
                            paper_id: this.currentPaper.id,
                            time_spent: timeSpent
                        })
                    });
                    
                    console.log(`⏱️ Time logged: ${timeSpent}s for "${this.currentPaper.title}"`);
                    
                } catch (error) {
                    console.error('❌ Update time error:', error);
                }
            }
            
            this.timeStart = null;
            this.currentPaper = null;
        }
    }
    
    /**
     * Log team activity (cho project team)
     * @param {number} projectId
     * @param {string} activityType
     * @param {Object} activityData
     */
    async logTeamActivity(projectId, activityType, activityData) {
        try {
            await fetch(`${this.apiBase}/log-team-activity.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    project_id: projectId,
                    user_id: this.userId,
                    activity_type: activityType,
                    activity_title: activityData.title,
                    activity_data: activityData,
                    is_public: true
                })
            });
            
            console.log('✅ Team activity logged');
            
        } catch (error) {
            console.error('❌ Log team activity error:', error);
        }
    }
    
    /**
     * Get activity summary for user
     * @param {number} days - Last N days
     * @returns {Promise<Object>} Summary stats
     */
    async getActivitySummary(days = 7) {
        try {
            const response = await fetch(`${this.apiBase}/get-summary.php?user_id=${this.userId}&days=${days}`);
            return await response.json();
        } catch (error) {
            console.error('❌ Get summary error:', error);
            return null;
        }
    }
}

/**
 * Auto-track page visibility (stop timing khi switch tab)
 */
function setupAutoTracking(tracker) {
    // Stop tracking khi user rời trang
    window.addEventListener('beforeunload', () => {
        tracker.stopTimeTracking();
    });
    
    // Stop tracking khi switch tab
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            tracker.stopTimeTracking();
        }
    });
    
    // Heartbeat: Gửi tracking data mỗi 30s
    setInterval(() => {
        if (tracker.timeStart) {
            // Update time spent periodically (nếu đọc paper lâu)
            tracker.stopTimeTracking();
            // Có thể restart nếu vẫn đang xem paper
        }
    }, 30000);
}

// Export
export { SearchTracker, setupAutoTracking };
export default SearchTracker;

console.log('📊 Search Tracker loaded');

