/**
 * Payments Page JavaScript for Michitai API Constructor
 * Handles subscription management, billing history, and payment methods
 */

class PaymentsPage {
    constructor() {
        this.apiToken = localStorage.getItem('api_token');
        this.baseUrl = '/api';
        this.currentUser = null;
        this.selectedPlan = null;
        this.paypalButtonsInstance = null;
        this.init();
    }

    init() {
        this.checkAuthentication();
        this.setupEventListeners();
        this.loadUserData();
        this.loadBillingHistory();
        this.loadPaymentMethods();
    }

    checkAuthentication() {
        if (!this.apiToken) {
            // User not logged in, redirect to login
            window.location.href = 'login.html';
            return;
        }
    }

    setupEventListeners() {
        // Close PayPal modal
        $('#close-paypal-modal').on('click', () => this.closePayPalModal());

        // Add payment method
        $('#add-payment-method').on('click', () => this.showAddPaymentMethodModal());

        // Alternative payment options
        $('.btn-success').on('click', (e) => {
            const buttonText = $(e.target).text();
            if (buttonText.includes('Contact Support')) {
                this.contactSupport('paynet');
            } else if (buttonText.includes('Get Bank Details')) {
                this.showBankDetails();
            }
        });
    }

    async loadUserData() {
        try {
            const user = await this.apiCall('/user');
            this.currentUser = user;
            
            // Update UI with user data
            $('#user-email').text(user.email);
            $('#user-plan').text(user.plan_type || 'Free Plan');
            
            // Update current plan display
            this.updateCurrentPlanDisplay(user);
            
        } catch (error) {
            console.error('Failed to load user data:', error);
            this.showNotification('Failed to load user data', 'error');
        }
    }

    updateCurrentPlanDisplay(user) {
        const planNames = {
            'free': 'Free Plan',
            'standard': 'Standard Plan',
            'pro': 'Pro Plan'
        };

        const planPrices = {
            'free': '$0/month',
            'standard': '$50/month',
            'pro': '$200/month'
        };

        const planType = user.plan_type || 'free';
        
        $('#current-plan-name').text(planNames[planType] || 'Free Plan');
        $('#current-plan-price').text(planPrices[planType] || '$0/month');
        
        if (user.subscription_end_date && planType !== 'free') {
            const endDate = new Date(user.subscription_end_date);
            $('#next-billing').text(`Next billing: ${endDate.toLocaleDateString()}`);
        } else {
            $('#next-billing').text('No billing cycle');
        }

        // Update plan buttons
        this.updatePlanButtons(planType);
    }

    updatePlanButtons(currentPlan) {
        // Reset all buttons
        $('.plan-card button').removeClass('disabled').prop('disabled', false);
        $('.plan-card button').text(function() {
            const planCard = $(this).closest('.plan-card');
            if (planCard.find('h4').text() === 'Free') return 'Current Plan';
            if (planCard.find('h4').text() === 'Standard') return 'Upgrade to Standard';
            if (planCard.find('h4').text() === 'Pro') return 'Upgrade to Pro';
        });

        // Disable current plan button
        const planButtons = {
            'free': 0,
            'standard': 1,
            'pro': 2
        };

        const buttonIndex = planButtons[currentPlan];
        if (buttonIndex !== undefined) {
            const currentButton = $('.plan-card button').eq(buttonIndex);
            currentButton.text('Current Plan').addClass('disabled').prop('disabled', true);
        }
    }

    async subscribeToPlan(planType) {
        try {
            this.selectedPlan = planType;
            this.showLoading('Preparing subscription...');

            // Create subscription with backend
            const subscription = await this.apiCall('/subscribe', 'POST', {
                plan_type: planType
            });

            this.hideLoading();

            if (subscription.paypal_subscription_id) {
                // Show PayPal modal for payment
                this.showPayPalModal(subscription);
            } else {
                this.showNotification('Subscription created successfully', 'success');
                this.loadUserData(); // Refresh user data
            }

        } catch (error) {
            this.hideLoading();
            console.error('Failed to create subscription:', error);
            this.showNotification(error.message || 'Failed to create subscription', 'error');
        }
    }

    showPayPalModal(subscription) {
        $('#paypal-modal').removeClass('hidden').addClass('flex');

        // Clear any existing PayPal buttons
        $('#paypal-button-container').empty();

        // Initialize PayPal buttons
        if (window.paypal) {
            this.paypalButtonsInstance = paypal.Buttons({
                createSubscription: (data, actions) => {
                    return subscription.paypal_subscription_id;
                },
                onApprove: async (data, actions) => {
                    try {
                        this.showLoading('Confirming subscription...');
                        
                        // Confirm subscription with backend
                        await this.apiCall('/subscription/confirm', 'POST', {
                            subscription_id: data.subscriptionID,
                            order_id: data.orderID
                        });

                        this.hideLoading();
                        this.closePayPalModal();
                        this.showNotification('Subscription activated successfully!', 'success');
                        
                        // Refresh page data
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);

                    } catch (error) {
                        this.hideLoading();
                        console.error('Failed to confirm subscription:', error);
                        this.showNotification('Failed to confirm subscription', 'error');
                    }
                },
                onError: (err) => {
                    console.error('PayPal error:', err);
                    this.showNotification('Payment failed. Please try again.', 'error');
                },
                onCancel: (data) => {
                    console.log('Payment cancelled:', data);
                    this.showNotification('Payment cancelled', 'warning');
                }
            });

            this.paypalButtonsInstance.render('#paypal-button-container');
        } else {
            this.showNotification('PayPal SDK not loaded. Please refresh the page.', 'error');
        }
    }

    closePayPalModal() {
        $('#paypal-modal').addClass('hidden').removeClass('flex');
        
        // Destroy PayPal buttons instance
        if (this.paypalButtonsInstance) {
            this.paypalButtonsInstance.close();
            this.paypalButtonsInstance = null;
        }
        
        $('#paypal-button-container').empty();
    }

    async loadBillingHistory() {
        try {
            const history = await this.apiCall('/billing/history');
            this.updateBillingHistory(history);
            
        } catch (error) {
            console.error('Failed to load billing history:', error);
            // Don't show error notification for billing history as it might not exist for free users
        }
    }

    updateBillingHistory(history) {
        const container = $('#billing-history');
        
        if (!history || history.length === 0) {
            container.html(`
                <div class="text-center py-8 text-white/50">
                    <i class="fas fa-receipt text-2xl mb-2"></i>
                    <p>No billing history available</p>
                </div>
            `);
            return;
        }

        const historyHtml = history.map(item => `
            <div class="billing-history-item flex items-center justify-between p-4 rounded-xl border border-white/10">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 rounded-xl bg-${this.getStatusColor(item.status)} flex items-center justify-center">
                        <i class="fas fa-${this.getStatusIcon(item.status)} text-white"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-white">${item.description}</div>
                        <div class="text-sm text-white/70">${new Date(item.created_at).toLocaleDateString()}</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-semibold text-white">$${item.amount}</div>
                    <div class="text-sm text-${this.getStatusColor(item.status)}-400 capitalize">${item.status}</div>
                </div>
                <div class="flex space-x-2">
                    <button class="text-blue-400 hover:text-blue-300 transition-colors" onclick="paymentsPage.downloadInvoice('${item.invoice_id}')">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
        `).join('');

        container.html(historyHtml);
    }

    getStatusColor(status) {
        const colors = {
            'completed': 'green-500',
            'pending': 'yellow-500',
            'failed': 'red-500',
            'cancelled': 'gray-500'
        };
        return colors[status] || 'gray-500';
    }

    getStatusIcon(status) {
        const icons = {
            'completed': 'check',
            'pending': 'clock',
            'failed': 'times',
            'cancelled': 'ban'
        };
        return icons[status] || 'question';
    }

    async loadPaymentMethods() {
        try {
            const methods = await this.apiCall('/payment-methods');
            this.updatePaymentMethods(methods);
            
        } catch (error) {
            console.error('Failed to load payment methods:', error);
            // Don't show error notification as payment methods might not exist
        }
    }

    updatePaymentMethods(methods) {
        const container = $('#payment-methods-list');
        
        if (!methods || methods.length === 0) {
            container.html(`
                <div class="text-center py-8 text-white/50">
                    <i class="fas fa-credit-card text-2xl mb-2"></i>
                    <p>No payment methods added</p>
                    <p class="text-sm">Add a payment method to upgrade your plan</p>
                </div>
            `);
            return;
        }

        const methodsHtml = methods.map(method => `
            <div class="flex items-center justify-between p-4 rounded-xl border border-white/10">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-500 flex items-center justify-center">
                        <i class="fas fa-credit-card text-white"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-white">${method.type} •••• ${method.last4}</div>
                        <div class="text-sm text-white/70">Expires ${method.exp_month}/${method.exp_year}</div>
                    </div>
                </div>
                <div class="flex space-x-2">
                    ${method.is_default ? '<span class="text-green-400 text-sm">Default</span>' : ''}
                    <button class="text-red-400 hover:text-red-300 transition-colors" onclick="paymentsPage.removePaymentMethod('${method.id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');

        container.html(methodsHtml);
    }

    showAddPaymentMethodModal() {
        this.showNotification('Add payment method feature coming soon', 'info');
    }

    removePaymentMethod(methodId) {
        if (confirm('Are you sure you want to remove this payment method?')) {
            this.showNotification('Remove payment method feature coming soon', 'info');
        }
    }

    downloadInvoice(invoiceId) {
        this.showNotification('Download invoice feature coming soon', 'info');
    }

    contactSupport(type) {
        const messages = {
            'paynet': 'To set up Paynet payments, please contact our support team with your account details.'
        };
        
        this.showNotification(messages[type] || 'Please contact support for assistance', 'info');
    }

    showBankDetails() {
        const bankDetails = `
            Bank: MAIB (Moldova Agroindbank)
            SWIFT: AGRNMD2X
            
            Please contact support for complete bank transfer details including account number and reference information.
        `;
        
        alert(bankDetails);
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
}

// Initialize payments page
let paymentsPage;
$(document).ready(() => {
    paymentsPage = new PaymentsPage();
});
