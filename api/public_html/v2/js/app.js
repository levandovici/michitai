/**
 * Main Application JavaScript for Michitai API Constructor
 * Handles authentication, API calls, UI updates, and tab navigation
 */

class MichitaiApp {
    constructor() {
        this.apiToken = localStorage.getItem('api_token');
        this.baseUrl = '/api';
        this.currentUser = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupTabNavigation();
        this.checkAuthStatus();
        this.initializePuzzleConstructor();
    }

    setupTabNavigation() {
        // Tab switching functionality
        $('.tab-btn').on('click', (e) => {
            const tabId = $(e.target).attr('id').replace('tab-', '');
            this.switchTab(tabId);
        });

        // Hero button navigation
        $('#view-schema-btn').on('click', () => this.switchTab('schema'));
        $('#view-endpoints-btn').on('click', () => this.switchTab('endpoints'));
        $('#view-examples-btn').on('click', () => this.switchTab('examples'));
    }

    switchTab(tabName) {
        // Update active tab button
        $('.tab-btn').removeClass('active bg-blue-500 text-white').addClass('text-gray-600');
        $(`#tab-${tabName}`).removeClass('text-gray-600').addClass('active bg-blue-500 text-white');

        // Hide all tab content
        $('.tab-content').addClass('hidden');
        
        // Show selected tab content
        $(`#${tabName}-section`).removeClass('hidden');

        // Special handling for profile tab - requires authentication
        if (tabName === 'profile' && !this.apiToken) {
            this.showNotification('Please login to view your profile', 'warning');
            this.switchTab('overview');
            this.showModal('login-modal');
            return;
        }

        // Load tab-specific data
        this.loadTabData(tabName);
    }

    loadTabData(tabName) {
        switch (tabName) {
            case 'profile':
                this.loadUserProfile();
                break;
            case 'constructor':
                this.initializePuzzleConstructor();
                break;
            case 'examples':
                this.highlightCodeExamples();
                break;
        }
    }

    highlightCodeExamples() {
        // Trigger Prism.js syntax highlighting
        if (typeof Prism !== 'undefined') {
            Prism.highlightAll();
        }
    }

    setupEventListeners() {
        // Authentication
        $('#login-btn, #register-btn').on('click', (e) => {
            const modalId = e.target.id.replace('-btn', '-modal');
            this.showModal(modalId);
        });

        $('#login-form').on('submit', (e) => this.handleLogin(e));
        $('#register-form').on('submit', (e) => this.handleRegister(e));
        $('#logout-btn').on('click', () => this.handleLogout());

        // Modal controls
        $('.modal').on('click', (e) => {
            if (e.target === e.currentTarget) {
                this.hideModal(e.currentTarget.id);
            }
        });

        $('[id$="-cancel"]').on('click', (e) => {
            const modalId = e.target.id.replace('-cancel', '-modal');
            this.hideModal(modalId);
        });

        // Game and player management
        $('#create-game-btn').on('click', () => this.showModal('game-modal'));
        $('#game-form').on('submit', (e) => this.handleCreateGame(e));
        $('#create-player-btn').on('click', () => this.handleCreatePlayer());

        // Subscription management
        $('#subscription-btn').on('click', () => this.showModal('payment-modal'));
        $('#puzzle-constructor-btn').on('click', () => this.switchTab('constructor'));

        // Subscription plan selection
        $('.subscription-plan').on('click', (e) => {
            const planType = $(e.currentTarget).data('plan');
            this.handleSubscription(planType);
        });
    }

    async apiCall(endpoint, method = 'GET', data = null) {
        const headers = {
            'Content-Type': 'application/json'
        };

        if (this.apiToken) {
            headers['X-API-Token'] = this.apiToken;
        }

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
            this.showNotification(error.message, 'error');
            throw error;
        }
    }

    async checkAuthStatus() {
        if (this.apiToken) {
            try {
                const user = await this.apiCall('/user');
                this.currentUser = user;
                this.showAuthenticatedUI();
                this.updateUserStats();
            } catch (error) {
                this.handleLogout();
            }
        } else {
            this.showUnauthenticatedUI();
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        
        const email = $('#login-email').val();
        const password = $('#login-password').val();

        try {
            this.showLoading('Logging in...');
            const result = await this.apiCall('/login', 'POST', { email, password });
            
            this.apiToken = result.api_token;
            localStorage.setItem('api_token', this.apiToken);
            this.currentUser = result;
            
            this.hideModal('login-modal');
            this.hideLoading();
            this.showAuthenticatedUI();
            this.showNotification('Login successful!', 'success');
            this.updateUserStats();
        } catch (error) {
            this.hideLoading();
        }
    }

    async handleRegister(e) {
        e.preventDefault();
        
        const email = $('#register-email').val();
        const password = $('#register-password').val();

        try {
            this.showLoading('Creating account...');
            const result = await this.apiCall('/register', 'POST', { email, password });
            
            this.apiToken = result.api_token;
            localStorage.setItem('api_token', this.apiToken);
            this.currentUser = result;
            
            this.hideModal('register-modal');
            this.hideLoading();
            this.showAuthenticatedUI();
            this.showNotification('Account created successfully!', 'success');
            this.updateUserStats();
        } catch (error) {
            this.hideLoading();
        }
    }

    handleLogout() {
        this.apiToken = null;
        this.currentUser = null;
        localStorage.removeItem('api_token');
        this.showUnauthenticatedUI();
        this.showNotification('Logged out successfully', 'info');
        this.switchTab('overview');
    }

    async handleCreateGame(e) {
        e.preventDefault();
        
        const gameData = {
            name: $('#game-name').val(),
            description: $('#game-description').val(),
            json_structure: {
                logic: [],
                data_types: ['Integer', 'String', 'Boolean'],
                functions: ['Random', 'Power']
            },
            json_properties: {
                max_players: parseInt($('#game-max-players').val())
            }
        };

        try {
            this.showLoading('Creating game...');
            const result = await this.apiCall('/game/create', 'POST', gameData);
            
            this.hideModal('game-modal');
            this.hideLoading();
            this.showNotification('Game created successfully!', 'success');
            this.updateUserStats();
            this.loadUserGames();
        } catch (error) {
            this.hideLoading();
        }
    }

    async handleCreatePlayer() {
        if (!this.currentUser || !this.currentUser.games || this.currentUser.games.length === 0) {
            this.showNotification('Please create a game first', 'warning');
            return;
        }

        try {
            this.showLoading('Creating player...');
            const result = await this.apiCall('/player/create', 'POST', {
                game_id: this.currentUser.games[0].id
            });
            
            this.hideLoading();
            this.showNotification(`Player created! ID: ${result.player_id}`, 'success');
            this.updateUserStats();
        } catch (error) {
            this.hideLoading();
        }
    }

    async handleSubscription(planType) {
        try {
            this.showLoading('Creating subscription...');
            const result = await this.apiCall('/subscribe', 'POST', { plan_type: planType });
            
            if (result.approval_url) {
                window.open(result.approval_url, '_blank');
                this.showNotification('Redirecting to PayPal...', 'info');
            }
            
            this.hideLoading();
            this.hideModal('payment-modal');
        } catch (error) {
            this.hideLoading();
        }
    }

    async updateUserStats() {
        if (!this.apiToken) return;

        try {
            const stats = await this.apiCall('/monitor/user');
            
            // Update storage bar
            const storagePercent = (stats.memory_used_mb / stats.memory_limit_mb) * 100;
            $('#storage-bar').css('width', `${storagePercent}%`);
            $('#storage-text').text(`${stats.memory_used_mb.toFixed(1)} MB`);
            
            // Update API calls bar
            const apiPercent = (stats.api_calls_today / stats.api_calls_limit) * 100;
            $('#api-bar').css('width', `${apiPercent}%`);
            $('#api-text').text(stats.api_calls_today);
            
            // Update counts
            $('#games-count').text(stats.games_count || 0);
            $('#players-count').text(stats.players_count || 0);
            
        } catch (error) {
            console.error('Failed to update user stats:', error);
        }
    }

    async loadUserProfile() {
        if (!this.apiToken) return;

        try {
            const profile = await this.apiCall('/user');
            const games = await this.apiCall('/monitor/user');
            
            // Update profile information
            this.updateProfileUI(profile, games);
        } catch (error) {
            console.error('Failed to load user profile:', error);
        }
    }

    updateProfileUI(profile, stats) {
        // This would update the profile section with real user data
        // For now, the profile section shows mock data in the HTML
        console.log('Profile data:', profile, stats);
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

        // Code generation buttons
        $('#gen-js').on('click', () => this.generateCode('javascript'));
        $('#gen-php').on('click', () => this.generateCode('php'));
        $('#gen-csharp').on('click', () => this.generateCode('csharp'));
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
                        <label class="block text-sm font-medium">Condition</label>
                        <input type="text" class="w-full px-2 py-1 border rounded text-sm" placeholder="player.level > 5">
                    </div>
                `;
                break;
            case 'for':
                propertiesHTML = `
                    <div class="space-y-2">
                        <label class="block text-sm font-medium">Variable</label>
                        <input type="text" class="w-full px-2 py-1 border rounded text-sm" placeholder="i">
                        <label class="block text-sm font-medium">Start</label>
                        <input type="number" class="w-full px-2 py-1 border rounded text-sm" placeholder="0">
                        <label class="block text-sm font-medium">End</label>
                        <input type="number" class="w-full px-2 py-1 border rounded text-sm" placeholder="10">
                    </div>
                `;
                break;
            case 'function':
                propertiesHTML = `
                    <div class="space-y-2">
                        <label class="block text-sm font-medium">Function Name</label>
                        <select class="w-full px-2 py-1 border rounded text-sm">
                            <option>Random</option>
                            <option>Power</option>
                            <option>Sqrt</option>
                            <option>spawnEnemy</option>
                        </select>
                    </div>
                `;
                break;
        }

        propertiesPanel.html(propertiesHTML);
    }

    generateCode(language) {
        const codeArea = $('#generated-code');
        let code = '';

        switch (language) {
            case 'javascript':
                code = `// JavaScript Generated Code
if (player.level > 5) {
    difficulty = 'hard';
    for (let i = 0; i < 10; i++) {
        spawnEnemy();
    }
}`;
                break;
            case 'php':
                code = `<?php
// PHP Generated Code
if ($player['level'] > 5) {
    $difficulty = 'hard';
    for ($i = 0; $i < 10; $i++) {
        spawnEnemy();
    }
}
?>`;
                break;
            case 'csharp':
                code = `// C# Generated Code
if (player.Level > 5) {
    difficulty = "hard";
    for (int i = 0; i < 10; i++) {
        SpawnEnemy();
    }
}`;
                break;
        }

        codeArea.text(code);
        
        // Highlight syntax if Prism is available
        if (typeof Prism !== 'undefined') {
            codeArea.removeClass().addClass(`language-${language}`);
            Prism.highlightElement(codeArea[0]);
        }
    }

    showAuthenticatedUI() {
        $('#auth-buttons').addClass('hidden');
        $('#user-info').removeClass('hidden');
        
        if (this.currentUser) {
            $('#user-email').text(this.currentUser.email);
            $('#user-plan').text(this.currentUser.plan_type || 'Free');
        }
        
        this.switchTab('profile');
    }

    showUnauthenticatedUI() {
        $('#auth-buttons').removeClass('hidden');
        $('#user-info').addClass('hidden');
        $('#dashboard').addClass('hidden');
        this.switchTab('overview');
    }

    showModal(modalId) {
        $(`#${modalId}`).removeClass('hidden').addClass('flex');
    }

    hideModal(modalId) {
        $(`#${modalId}`).addClass('hidden').removeClass('flex');
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
            <div class="notification ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg">
                ${message}
            </div>
        `);

        $('#notifications').append(notification);

        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);
    }
}

// Initialize app when document is ready
$(document).ready(() => {
    window.app = new MichitaiApp();
});
