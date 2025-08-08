/**
 * Profile Page JavaScript for Michitai API Constructor
 * Handles user dashboard, games management, logic constructor, and API explorer
 */

class ProfilePage {
    constructor() {
        this.sessionToken = localStorage.getItem('session_token');
        this.baseUrl = '/api';
        this.currentUser = null;
        this.currentTab = 'dashboard';
        this.init();
    }

    init() {
        this.checkAuthentication();
        this.setupEventListeners();
        this.loadUserData();
        this.initializePuzzleConstructor();
    }

    checkAuthentication() {
        // Simple check - if no session token, redirect to login
        if (!this.sessionToken) {
            window.location.href = 'login.html';
            return;
        }
    }

    setupEventListeners() {
        // Tab navigation
        $('.tab-btn').on('click', (e) => {
            const tabId = $(e.target).attr('id').replace('tab-', '');
            this.switchTab(tabId);
        });

        // Logout
        $('#logout-btn').on('click', () => this.handleLogout());

        // Quick actions
        $('#create-game-btn, #create-game-btn-2').on('click', () => this.showCreateGameModal());
        $('#upgrade-plan-btn').on('click', () => window.location.href = 'payments.html');

        // API Explorer
        $('#copy-token').on('click', () => this.copyApiToken());
        $('.api-test-btn').on('click', (e) => {
            const endpoint = $(e.target).data('endpoint');
            this.testApiEndpoint(endpoint);
        });

        // Constructor drag and drop
        this.initializeDragAndDrop();
    }

    switchTab(tabName) {
        // Update active tab button
        $('.tab-btn').removeClass('active bg-gradient-to-r from-blue-500 to-purple-600 text-white')
                     .addClass('text-white/70 hover:text-white');
        $(`#tab-${tabName}`).removeClass('text-white/70 hover:text-white')
                            .addClass('active bg-gradient-to-r from-blue-500 to-purple-600 text-white');

        // Hide all tab content
        $('.tab-content').addClass('hidden');
        
        // Show selected tab content
        $(`#${tabName}-section`).removeClass('hidden');

        this.currentTab = tabName;

        // Load tab-specific data
        this.loadTabData(tabName);
    }

    loadTabData(tabName) {
        switch (tabName) {
            case 'dashboard':
                this.loadDashboardData();
                break;
            case 'games':
                this.loadUserGames();
                break;
            case 'constructor':
                this.initializePuzzleConstructor();
                break;
            case 'api':
                this.loadApiExplorer();
                break;
        }
    }

    async loadUserData() {
        try {
            const user = await this.apiCall('/user');
            this.currentUser = user;
            
            // Update UI with user data
            $('#user-email').text(user.email);
            $('#user-plan').text(user.plan_type || 'Free Plan');
            
            // Load usage statistics
            await this.updateUserStats();
            
        } catch (error) {
            console.error('Failed to load user data:', error);
            this.showNotification('Failed to load user data', 'error');
        }
    }

    async updateUserStats() {
        try {
            const stats = await this.apiCall('/monitor/user');
            
            // Update storage usage
            const storagePercent = (stats.memory_used_mb / stats.memory_limit_mb) * 100;
            $('#storage-bar').css('width', `${Math.min(storagePercent, 100)}%`);
            $('#storage-text').text(`${stats.memory_used_mb.toFixed(1)} MB / ${stats.memory_limit_mb.toFixed(0)} MB`);
            
            // Update API calls
            const apiPercent = (stats.api_calls_today / stats.api_calls_limit) * 100;
            $('#api-bar').css('width', `${Math.min(apiPercent, 100)}%`);
            $('#api-text').text(stats.api_calls_today);
            
            // Update counts
            $('#games-count').text(stats.games_count || 0);
            $('#players-count').text(stats.players_count || 0);
            
        } catch (error) {
            console.error('Failed to update user stats:', error);
        }
    }

    async loadDashboardData() {
        try {
            // Load recent activity
            const activity = await this.apiCall('/monitor/activity');
            this.updateRecentActivity(activity);
            
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
        }
    }

    updateRecentActivity(activities) {
        const container = $('#recent-activity');
        
        if (!activities || activities.length === 0) {
            container.html(`
                <div class="text-center py-8 text-white/50">
                    <i class="fas fa-clock text-2xl mb-2"></i>
                    <p>No recent activity</p>
                </div>
            `);
            return;
        }

        const activityHtml = activities.map(activity => `
            <div class="flex items-center space-x-4 p-3 rounded-xl bg-white/5">
                <div class="w-8 h-8 rounded-full bg-${this.getActivityColor(activity.type)} flex items-center justify-center">
                    <i class="fas fa-${this.getActivityIcon(activity.type)} text-white text-xs"></i>
                </div>
                <div class="flex-1">
                    <p class="text-white font-medium">${activity.title}</p>
                    <p class="text-white/70 text-sm">${activity.description}</p>
                </div>
                <span class="text-white/50 text-xs">${this.formatTimeAgo(activity.created_at)}</span>
            </div>
        `).join('');

        container.html(activityHtml);
    }

    getActivityColor(type) {
        const colors = {
            'game_created': 'blue-500',
            'player_added': 'green-500',
            'subscription': 'purple-500',
            'api_call': 'orange-500'
        };
        return colors[type] || 'gray-500';
    }

    getActivityIcon(type) {
        const icons = {
            'game_created': 'gamepad',
            'player_added': 'user-plus',
            'subscription': 'crown',
            'api_call': 'code'
        };
        return icons[type] || 'info';
    }

    formatTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = now - time;
        
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        return `${days}d ago`;
    }

    async loadUserGames() {
        try {
            const games = await this.apiCall('/game/list');
            this.updateGamesList(games);
            
        } catch (error) {
            console.error('Failed to load games:', error);
            this.showNotification('Failed to load games', 'error');
        }
    }

    updateGamesList(games) {
        const container = $('#games-list');
        
        if (!games || games.length === 0) {
            container.html(`
                <div class="text-center py-12">
                    <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-gamepad text-white/50 text-2xl"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-white mb-2">No games yet</h4>
                    <p class="text-white/70 mb-6">Create your first multiplayer game to get started</p>
                    <button class="btn-primary text-white px-6 py-3 rounded-xl font-semibold border-0" onclick="profilePage.showCreateGameModal()">
                        <i class="fas fa-plus mr-2"></i>Create Your First Game
                    </button>
                </div>
            `);
            return;
        }

        const gamesHtml = games.map(game => `
            <div class="glass-effect rounded-xl p-6 hover:bg-white/10 transition-colors">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h4 class="text-lg font-semibold text-white mb-2">${game.name}</h4>
                        <p class="text-white/70 text-sm mb-4">${game.description || 'No description'}</p>
                        <div class="flex items-center space-x-4 text-xs text-white/50">
                            <span><i class="fas fa-users mr-1"></i>${game.players_count || 0} players</span>
                            <span><i class="fas fa-memory mr-1"></i>${game.memory_usage || 0} MB</span>
                            <span class="flex items-center">
                                <div class="w-2 h-2 rounded-full ${game.is_active ? 'bg-green-400' : 'bg-gray-400'} mr-1"></div>
                                ${game.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button class="text-blue-400 hover:text-blue-300 transition-colors" onclick="profilePage.editGame(${game.game_id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="text-green-400 hover:text-green-300 transition-colors" onclick="profilePage.viewGame(${game.game_id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="text-red-400 hover:text-red-300 transition-colors" onclick="profilePage.deleteGame(${game.game_id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        container.html(gamesHtml);
    }

    initializePuzzleConstructor() {
        // Initialize drag-and-drop functionality
        if (typeof Sortable !== 'undefined') {
            const palette = document.getElementById('logic-palette');
            const constructorArea = document.getElementById('constructor-area');
            
            if (palette && constructorArea) {
                new Sortable(palette, {
                    group: { name: 'logic', pull: 'clone', put: false },
                    sort: false
                });
                
                new Sortable(constructorArea, {
                    group: 'logic',
                    onAdd: (evt) => {
                        this.handleBlockAdded(evt);
                    }
                });
            }
        }
    }

    handleBlockAdded(evt) {
        const blockType = $(evt.item).data('type');
        console.log('Block added:', blockType);
        
        // Add properties panel for the block
        this.showBlockProperties(blockType, evt.item);
    }

    showBlockProperties(blockType, element) {
        const propertiesPanel = $('#properties-panel');
        let propertiesHTML = '';

        switch (blockType) {
            case 'if':
                propertiesHTML = `
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-white">Condition</label>
                        <input type="text" class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-white text-sm" placeholder="player.level > 5">
                    </div>
                `;
                break;
            case 'for':
                propertiesHTML = `
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-white">Variable</label>
                        <input type="text" class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-white text-sm" placeholder="i">
                        <label class="block text-sm font-medium text-white">Start</label>
                        <input type="number" class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-white text-sm" placeholder="0">
                        <label class="block text-sm font-medium text-white">End</label>
                        <input type="number" class="w-full px-2 py-1 bg-white/10 border border-white/20 rounded text-white text-sm" placeholder="10">
                    </div>
                `;
                break;
        }

        propertiesPanel.html(propertiesHTML);
    }

    loadApiExplorer() {
        // Display API token
        $('#api-token').text(this.apiToken);
        
        // Add click handlers for API test buttons
        $('.api-test-btn').off('click').on('click', (e) => {
            const endpoint = $(e.target).closest('button').data('endpoint');
            this.testApiEndpoint(endpoint);
        });
    }

    async testApiEndpoint(endpoint) {
        try {
            this.showLoading('Testing API endpoint...');
            const response = await this.apiCall(endpoint);
            
            $('#api-response').text(JSON.stringify(response, null, 2));
            
        } catch (error) {
            $('#api-response').text(`Error: ${error.message}`);
        } finally {
            this.hideLoading();
        }
    }

    copyApiToken() {
        navigator.clipboard.writeText(this.apiToken).then(() => {
            this.showNotification('API token copied to clipboard', 'success');
        }).catch(() => {
            this.showNotification('Failed to copy API token', 'error');
        });
    }

    showCreateGameModal() {
        // This would show a modal for creating a new game
        this.showNotification('Create game modal would open here', 'info');
    }

    editGame(gameId) {
        this.showNotification(`Edit game ${gameId} - feature coming soon`, 'info');
    }

    viewGame(gameId) {
        this.showNotification(`View game ${gameId} - feature coming soon`, 'info');
    }

    deleteGame(gameId) {
        if (confirm('Are you sure you want to delete this game?')) {
            this.showNotification(`Delete game ${gameId} - feature coming soon`, 'info');
        }
    }

    async apiCall(endpoint, method = 'GET', data = null) {
        const headers = {
            'Content-Type': 'application/json',
            'X-API-Token': this.apiToken
        };

        try {
            const response = await fetch(`${this.baseUrl}${endpoint}`, {
                method,
                headers,
                body: data ? JSON.stringify(data) : null
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'API request failed');
            }

            return result;
        } catch (error) {
            if (error.message.includes('401') || error.message.includes('Unauthorized')) {
                // Token expired, redirect to login
                localStorage.removeItem('api_token');
                window.location.href = 'login.html';
                return;
            }
            throw error;
        }
    }

    handleLogout() {
        if (confirm('Are you sure you want to logout?')) {
            localStorage.removeItem('api_token');
            localStorage.removeItem('user_email');
            localStorage.removeItem('user_plan');
            localStorage.removeItem('remember_login');
            
            this.showNotification('Logged out successfully', 'success');
            
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 1000);
        }
    }

    showLoading(message = 'Loading...') {
        $('#loading-text').text(message);
        $('#loading-modal').removeClass('hidden').addClass('flex');
    }

    hideLoading() {
        $('#loading-modal').addClass('hidden').removeClass('flex');
    }

    showNotification(message, type = 'info') {
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };

        const notification = $(`
            <div class="notification ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg max-w-sm">
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info'} mr-2"></i>
                    ${message}
                </div>
            </div>
        `);

        $('#notifications').append(notification);

        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);
    }

    handleLogout() {
        // Clear ALL localStorage data to ensure complete logout
        localStorage.clear();
        
        // Also clear sessionStorage if any data is stored there
        sessionStorage.clear();
        
        // Clear any cookies that might be set
        document.cookie.split(";").forEach(function(c) { 
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
        });
        
        // Force immediate redirect without notification to prevent timing issues
        // Use location.replace to prevent back button issues
        window.location.replace('index.html');
    }
}

// Initialize profile page
let profilePage;
$(document).ready(() => {
    profilePage = new ProfilePage();
});
