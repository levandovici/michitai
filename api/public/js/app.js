/**
 * Multiplayer API Web Constructor - Main Application
 * Handles authentication, API communication, and UI management
 */

class MultiplayerAPIApp {
    constructor() {
        this.apiToken = localStorage.getItem('api_token');
        this.userInfo = JSON.parse(localStorage.getItem('user_info') || '{}');
        this.apiBaseUrl = '/api';
        this.currentGame = null;
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.checkAuthentication();
        this.loadUserInfo();
    }
    
    setupEventListeners() {
        // Authentication
        $('#login-form-element').on('submit', (e) => this.handleLogin(e));
        $('#register-form-element').on('submit', (e) => this.handleRegister(e));
        $('#show-register').on('click', () => this.showRegisterForm());
        $('#show-login').on('click', () => this.showLoginForm());
        $('#logout-btn').on('click', () => this.logout());
        
        // Game management
        $('#create-game-btn').on('click', () => this.createGame());
        
        // Subscription
        $('.subscribe-btn').on('click', (e) => this.handleSubscription(e));
        
        // UI
        $('#close-paypal-modal').on('click', () => this.closePayPalModal());
    }
    
    checkAuthentication() {
        if (this.apiToken) {
            this.showMainApp();
            this.loadUserGames();
        } else {
            this.showAuthSection();
        }
    }
    
    showAuthSection() {
        $('#auth-section').removeClass('hidden');
        $('#main-app').addClass('hidden');
        $('#user-info').addClass('hidden');
        $('#logout-btn').addClass('hidden');
    }
    
    showMainApp() {
        $('#auth-section').addClass('hidden');
        $('#main-app').removeClass('hidden');
        $('#user-info').removeClass('hidden');
        $('#logout-btn').removeClass('hidden');
    }
    
    showRegisterForm() {
        $('#login-form').addClass('hidden');
        $('#register-form').removeClass('hidden');
    }
    
    showLoginForm() {
        $('#register-form').addClass('hidden');
        $('#login-form').removeClass('hidden');
    }
    
    async handleLogin(e) {
        e.preventDefault();
        
        const email = $('#login-email').val();
        const password = $('#login-password').val();
        
        try {
            this.showLoading(true);
            
            const response = await this.apiCall('POST', '/login', {
                email: email,
                password: password
            });
            
            this.apiToken = response.api_token;
            this.userInfo = response;
            
            localStorage.setItem('api_token', this.apiToken);
            localStorage.setItem('user_info', JSON.stringify(this.userInfo));
            
            this.showNotification('Login successful!', 'success');
            this.showMainApp();
            this.loadUserInfo();
            this.loadUserGames();
            
        } catch (error) {
            this.showNotification(error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    async handleRegister(e) {
        e.preventDefault();
        
        const email = $('#register-email').val();
        const password = $('#register-password').val();
        const confirmPassword = $('#confirm-password').val();
        
        if (password !== confirmPassword) {
            this.showNotification('Passwords do not match', 'error');
            return;
        }
        
        try {
            this.showLoading(true);
            
            const response = await this.apiCall('POST', '/register', {
                email: email,
                password: password
            });
            
            this.apiToken = response.api_token;
            this.userInfo = response;
            
            localStorage.setItem('api_token', this.apiToken);
            localStorage.setItem('user_info', JSON.stringify(this.userInfo));
            
            this.showNotification('Account created successfully!', 'success');
            this.showMainApp();
            this.loadUserInfo();
            
        } catch (error) {
            this.showNotification(error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    logout() {
        this.apiToken = null;
        this.userInfo = {};
        localStorage.removeItem('api_token');
        localStorage.removeItem('user_info');
        
        this.showNotification('Logged out successfully', 'success');
        this.showAuthSection();
    }
    
    async loadUserInfo() {
        if (!this.apiToken) return;
        
        try {
            const monitorData = await this.apiCall('GET', '/monitor/user');
            
            $('#user-plan').text(monitorData.plan_type);
            $('#memory-usage').text(`${monitorData.memory_used_mb.toFixed(1)}MB / ${monitorData.memory_limit_mb}MB`);
            $('#api-usage').text(`${monitorData.api_calls_today} / ${monitorData.api_calls_limit}`);
            
            // Update subscription UI based on current plan
            this.updateSubscriptionUI(monitorData.plan_type);
            
            // Check for warnings
            if (monitorData.memory_usage_percent > 80) {
                this.showNotification(`Memory usage is at ${monitorData.memory_usage_percent.toFixed(1)}%`, 'warning');
            }
            
            if (monitorData.api_usage_percent > 80) {
                this.showNotification(`API usage is at ${monitorData.api_usage_percent.toFixed(1)}%`, 'warning');
            }
            
        } catch (error) {
            console.error('Failed to load user info:', error);
        }
    }
    
    updateSubscriptionUI(currentPlan) {
        $('.subscribe-btn').each(function() {
            const planType = $(this).data('plan');
            if (planType === currentPlan) {
                $(this).text('Current Plan').addClass('bg-gray-400 cursor-not-allowed').removeClass('bg-blue-600 bg-purple-600 hover:bg-blue-700 hover:bg-purple-700');
            } else {
                $(this).text('Subscribe').removeClass('bg-gray-400 cursor-not-allowed');
                if (planType === 'Standard') {
                    $(this).addClass('bg-blue-600 hover:bg-blue-700');
                } else if (planType === 'Pro') {
                    $(this).addClass('bg-purple-600 hover:bg-purple-700');
                }
            }
        });
    }
    
    async createGame() {
        const name = $('#game-name').val();
        const description = $('#game-description').val();
        
        if (!name) {
            this.showNotification('Game name is required', 'error');
            return;
        }
        
        try {
            this.showLoading(true);
            
            // Get puzzle logic from constructor
            const puzzleLogic = window.puzzleConstructor ? window.puzzleConstructor.getLogicStructure() : {};
            
            const response = await this.apiCall('POST', '/game/create', {
                name: name,
                description: description,
                json_structure: puzzleLogic,
                json_properties: {
                    created_with: 'web_constructor',
                    version: '1.0'
                }
            });
            
            this.showNotification('Game created successfully!', 'success');
            $('#game-name').val('');
            $('#game-description').val('');
            
            this.loadUserGames();
            
        } catch (error) {
            this.showNotification(error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    async loadUserGames() {
        if (!this.apiToken) return;
        
        try {
            const games = await this.apiCall('GET', '/game/list');
            this.displayGames(games);
        } catch (error) {
            console.error('Failed to load games:', error);
        }
    }
    
    displayGames(games) {
        const gamesContainer = $('#games-list');
        gamesContainer.empty();
        
        if (games.length === 0) {
            gamesContainer.html('<p class="text-gray-500">No games created yet. Create your first game above!</p>');
            return;
        }
        
        games.forEach(game => {
            const gameElement = $(`
                <div class="border rounded-lg p-4 bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-semibold text-lg">${this.escapeHtml(game.name)}</h4>
                            <p class="text-gray-600 text-sm">${this.escapeHtml(game.description || 'No description')}</p>
                            <p class="text-xs text-gray-500 mt-1">Created: ${new Date(game.created_at).toLocaleDateString()}</p>
                        </div>
                        <div class="flex space-x-2">
                            <button class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 view-game-btn" data-game-id="${game.game_id}">
                                View
                            </button>
                            <button class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 create-player-btn" data-game-id="${game.game_id}">
                                Create Player
                            </button>
                            <button class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 delete-game-btn" data-game-id="${game.game_id}">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            `);
            
            gamesContainer.append(gameElement);
        });
        
        // Add event listeners for game actions
        $('.view-game-btn').on('click', (e) => this.viewGame($(e.target).data('game-id')));
        $('.create-player-btn').on('click', (e) => this.createPlayer($(e.target).data('game-id')));
        $('.delete-game-btn').on('click', (e) => this.deleteGame($(e.target).data('game-id')));
    }
    
    async viewGame(gameId) {
        try {
            this.showLoading(true);
            const game = await this.apiCall('GET', `/game/get/${gameId}`);
            this.displayGameDetails(game);
        } catch (error) {
            this.showNotification(error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    displayGameDetails(game) {
        // Create a modal or expand the game view
        const modal = $(`
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-3/4 max-w-4xl shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">${this.escapeHtml(game.name)}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-medium mb-2">Game Structure</h4>
                                <pre class="bg-gray-100 p-3 rounded text-sm overflow-auto max-h-64">${JSON.stringify(game.json_structure, null, 2)}</pre>
                            </div>
                            <div>
                                <h4 class="font-medium mb-2">Game Properties</h4>
                                <pre class="bg-gray-100 p-3 rounded text-sm overflow-auto max-h-64">${JSON.stringify(game.json_properties, null, 2)}</pre>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <button class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 close-modal">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.find('.close-modal').on('click', () => modal.remove());
        modal.on('click', (e) => {
            if (e.target === modal[0]) modal.remove();
        });
    }
    
    async createPlayer(gameId) {
        try {
            this.showLoading(true);
            const player = await this.apiCall('POST', '/player/create', { game_id: gameId });
            
            // Show player credentials
            const modal = $(`
                <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div class="mt-3">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Player Created</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Player ID</label>
                                    <input type="text" value="${player.player_id}" readonly class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Password GUID</label>
                                    <input type="text" value="${player.password_guid}" readonly class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                                </div>
                                <p class="text-sm text-gray-600">Save these credentials! They are needed to authenticate the player.</p>
                            </div>
                            <div class="mt-4 text-center">
                                <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 copy-credentials">
                                    Copy Credentials
                                </button>
                                <button class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 ml-2 close-modal">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            modal.find('.close-modal').on('click', () => modal.remove());
            modal.find('.copy-credentials').on('click', () => {
                const credentials = `Player ID: ${player.player_id}\nPassword GUID: ${player.password_guid}`;
                navigator.clipboard.writeText(credentials);
                this.showNotification('Credentials copied to clipboard!', 'success');
            });
            
            this.showNotification('Player created successfully!', 'success');
            
        } catch (error) {
            this.showNotification(error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    async deleteGame(gameId) {
        if (!confirm('Are you sure you want to delete this game? This action cannot be undone.')) {
            return;
        }
        
        try {
            this.showLoading(true);
            await this.apiCall('POST', '/game/delete', { game_id: gameId });
            this.showNotification('Game deleted successfully!', 'success');
            this.loadUserGames();
        } catch (error) {
            this.showNotification(error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    async handleSubscription(e) {
        const planType = $(e.target).data('plan');
        const currentPlan = $('#user-plan').text();
        
        if (planType === currentPlan) {
            return;
        }
        
        $('#selected-plan').text(planType);
        $('#paypal-modal').removeClass('hidden');
        
        // Initialize PayPal payment
        window.paymentManager.initializePayPal(planType);
    }
    
    closePayPalModal() {
        $('#paypal-modal').addClass('hidden');
        $('#paypal-button-container').empty();
    }
    
    async apiCall(method, endpoint, data = null) {
        const url = this.apiBaseUrl + endpoint;
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        if (this.apiToken) {
            options.headers['X-API-Token'] = this.apiToken;
        }
        
        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        const responseData = await response.json();
        
        if (!response.ok) {
            throw new Error(responseData.error || 'API request failed');
        }
        
        return responseData;
    }
    
    showLoading(show) {
        if (show) {
            $('#loading-overlay').removeClass('hidden');
        } else {
            $('#loading-overlay').addClass('hidden');
        }
    }
    
    showNotification(message, type = 'info') {
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };
        
        const notification = $(`
            <div class="notification ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg mb-4">
                <div class="flex justify-between items-center">
                    <span>${this.escapeHtml(message)}</span>
                    <button class="ml-4 text-white hover:text-gray-200 close-notification">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `);
        
        $('#notification-container').append(notification);
        
        notification.find('.close-notification').on('click', () => notification.remove());
        
        // Auto-remove after 5 seconds
        setTimeout(() => notification.remove(), 5000);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize the application when the document is ready
$(document).ready(() => {
    window.app = new MultiplayerAPIApp();
});
