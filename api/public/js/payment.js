/**
 * Payment Management
 * Handles PayPal integration, Paynet fallback, and subscription management
 */

class PaymentManager {
    constructor() {
        this.paypalLoaded = false;
        this.currentPlan = null;
    }
    
    initializePayPal(planType) {
        this.currentPlan = planType;
        
        // Clear any existing PayPal buttons
        document.getElementById('paypal-button-container').innerHTML = '';
        
        if (typeof paypal === 'undefined') {
            this.showPaynetFallback(planType);
            return;
        }
        
        const planPrices = {
            'Standard': '50.00',
            'Pro': '200.00'
        };
        
        paypal.Buttons({
            style: {
                shape: 'rect',
                color: 'blue',
                layout: 'vertical',
                label: 'subscribe'
            },
            
            createSubscription: (data, actions) => {
                return this.createPayPalSubscription(planType, actions);
            },
            
            onApprove: (data, actions) => {
                return this.handlePayPalApproval(data, actions);
            },
            
            onCancel: (data) => {
                this.handlePayPalCancel(data);
            },
            
            onError: (err) => {
                this.handlePayPalError(err);
            }
            
        }).render('#paypal-button-container');
        
        // Add Paynet fallback button
        this.addPaynetFallbackButton(planType);
    }
    
    async createPayPalSubscription(planType, actions) {
        try {
            // Call our backend to create subscription
            const response = await window.app.apiCall('POST', '/subscribe', {
                plan_type: planType
            });
            
            if (response.payment_method === 'paypal' && response.subscription_id) {
                return response.subscription_id;
            } else {
                throw new Error('Failed to create PayPal subscription');
            }
            
        } catch (error) {
            console.error('PayPal subscription creation failed:', error);
            this.showPaynetFallback(planType);
            throw error;
        }
    }
    
    async handlePayPalApproval(data, actions) {
        try {
            window.app.showLoading(true);
            
            // Subscription is now active, refresh user info
            await window.app.loadUserInfo();
            
            window.app.showNotification(
                `Successfully subscribed to ${this.currentPlan} plan!`, 
                'success'
            );
            
            window.app.closePayPalModal();
            
        } catch (error) {
            window.app.showNotification(
                'Subscription activation failed: ' + error.message, 
                'error'
            );
        } finally {
            window.app.showLoading(false);
        }
    }
    
    handlePayPalCancel(data) {
        window.app.showNotification('Payment was cancelled', 'info');
        window.app.closePayPalModal();
    }
    
    handlePayPalError(err) {
        console.error('PayPal error:', err);
        window.app.showNotification(
            'PayPal payment failed. Trying alternative payment method...', 
            'warning'
        );
        
        // Show Paynet fallback
        this.showPaynetFallback(this.currentPlan);
    }
    
    addPaynetFallbackButton(planType) {
        const fallbackContainer = document.createElement('div');
        fallbackContainer.className = 'mt-4 text-center';
        fallbackContainer.innerHTML = `
            <div class="border-t pt-4">
                <p class="text-sm text-gray-600 mb-2">Alternative payment methods:</p>
                <button id="paynet-btn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 mr-2">
                    Pay with Paynet (Moldova)
                </button>
                <button id="maib-transfer-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    MAIB Bank Transfer
                </button>
            </div>
        `;
        
        document.getElementById('paypal-button-container').appendChild(fallbackContainer);
        
        // Add event listeners
        document.getElementById('paynet-btn').addEventListener('click', () => {
            this.initiatePaynetPayment(planType);
        });
        
        document.getElementById('maib-transfer-btn').addEventListener('click', () => {
            this.showMAIBTransferInfo(planType);
        });
    }
    
    showPaynetFallback(planType) {
        document.getElementById('paypal-button-container').innerHTML = `
            <div class="text-center">
                <p class="text-sm text-gray-600 mb-4">PayPal is not available. Please use an alternative payment method:</p>
                <button id="paynet-btn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 mr-2">
                    Pay with Paynet (Moldova)
                </button>
                <button id="maib-transfer-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    MAIB Bank Transfer
                </button>
            </div>
        `;
        
        // Add event listeners
        document.getElementById('paynet-btn').addEventListener('click', () => {
            this.initiatePaynetPayment(planType);
        });
        
        document.getElementById('maib-transfer-btn').addEventListener('click', () => {
            this.showMAIBTransferInfo(planType);
        });
    }
    
    async initiatePaynetPayment(planType) {
        try {
            window.app.showLoading(true);
            
            const response = await window.app.apiCall('POST', '/subscribe', {
                plan_type: planType
            });
            
            if (response.payment_method === 'paynet' && response.approval_url) {
                // Redirect to Paynet
                window.open(response.approval_url, '_blank');
                
                window.app.showNotification(
                    'Redirecting to Paynet payment page...', 
                    'info'
                );
                
                window.app.closePayPalModal();
            } else {
                throw new Error('Failed to create Paynet payment');
            }
            
        } catch (error) {
            window.app.showNotification(
                'Paynet payment failed: ' + error.message, 
                'error'
            );
        } finally {
            window.app.showLoading(false);
        }
    }
    
    showMAIBTransferInfo(planType) {
        const planPrices = {
            'Standard': '$50',
            'Pro': '$200'
        };
        
        const modal = $(`
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">MAIB Bank Transfer Instructions</h3>
                        <div class="space-y-4">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="font-semibold text-blue-900 mb-2">Payment Details</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <strong>Plan:</strong> ${planType}
                                    </div>
                                    <div>
                                        <strong>Amount:</strong> ${planPrices[planType]} USD
                                    </div>
                                    <div>
                                        <strong>Bank:</strong> Moldova Agroindbank (MAIB)
                                    </div>
                                    <div>
                                        <strong>SWIFT Code:</strong> AGRNMD2X
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <h4 class="font-semibold text-yellow-900 mb-2">Transfer Instructions</h4>
                                <ol class="list-decimal list-inside space-y-2 text-sm">
                                    <li>Make a bank transfer to the MAIB account using SWIFT code AGRNMD2X</li>
                                    <li>Include your email address in the transfer reference</li>
                                    <li>Include "API-${planType}-" + your user ID in the reference</li>
                                    <li>Send transfer confirmation to support@yourdomain.com</li>
                                    <li>Your subscription will be activated within 24-48 hours</li>
                                </ol>
                            </div>
                            
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <h4 class="font-semibold text-green-900 mb-2">MPay Verification</h4>
                                <p class="text-sm">
                                    You can also use MPay (Moldova) for instant verification. 
                                    After making the transfer via MPay, your subscription will be activated automatically.
                                </p>
                            </div>
                            
                            <div class="text-center space-x-2">
                                <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 copy-details">
                                    Copy Transfer Details
                                </button>
                                <button class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 close-modal">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        modal.find('.close-modal').on('click', () => {
            modal.remove();
            window.app.closePayPalModal();
        });
        
        modal.find('.copy-details').on('click', () => {
            const details = `MAIB Bank Transfer Details:
Bank: Moldova Agroindbank (MAIB)
SWIFT Code: AGRNMD2X
Plan: ${planType}
Amount: ${planPrices[planType]} USD
Reference: API-${planType}-${window.app.userInfo.user_id || 'YOUR_USER_ID'}

Please include your email address in the transfer reference and send confirmation to support@yourdomain.com`;
            
            navigator.clipboard.writeText(details);
            window.app.showNotification('Transfer details copied to clipboard!', 'success');
        });
        
        modal.on('click', (e) => {
            if (e.target === modal[0]) {
                modal.remove();
                window.app.closePayPalModal();
            }
        });
    }
    
    async cancelSubscription() {
        if (!confirm('Are you sure you want to cancel your subscription? You will be downgraded to the Free plan.')) {
            return;
        }
        
        try {
            window.app.showLoading(true);
            
            const response = await window.app.apiCall('POST', '/subscription/cancel');
            
            window.app.showNotification(
                'Subscription cancelled successfully. You have been downgraded to the Free plan.', 
                'success'
            );
            
            // Refresh user info
            await window.app.loadUserInfo();
            
        } catch (error) {
            window.app.showNotification(
                'Failed to cancel subscription: ' + error.message, 
                'error'
            );
        } finally {
            window.app.showLoading(false);
        }
    }
    
    async updateSubscription(newPlanType) {
        try {
            window.app.showLoading(true);
            
            const response = await window.app.apiCall('POST', '/subscription/update', {
                plan_type: newPlanType
            });
            
            if (response.approval_url) {
                window.open(response.approval_url, '_blank');
                window.app.showNotification(
                    'Redirecting to payment page for plan update...', 
                    'info'
                );
            }
            
        } catch (error) {
            window.app.showNotification(
                'Failed to update subscription: ' + error.message, 
                'error'
            );
        } finally {
            window.app.showLoading(false);
        }
    }
    
    async getSubscriptionStatus() {
        try {
            const status = await window.app.apiCall('GET', '/subscription/status');
            return status;
        } catch (error) {
            console.error('Failed to get subscription status:', error);
            return null;
        }
    }
    
    formatCurrency(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }
    
    // Utility method to handle payment success from external redirects
    handlePaymentReturn(params) {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('payment_status');
        const planType = urlParams.get('plan_type');
        
        if (status === 'success' && planType) {
            window.app.showNotification(
                `Payment successful! Your ${planType} plan is now active.`, 
                'success'
            );
            
            // Refresh user info
            window.app.loadUserInfo();
            
            // Clean up URL
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (status === 'cancelled') {
            window.app.showNotification('Payment was cancelled', 'info');
        } else if (status === 'failed') {
            window.app.showNotification('Payment failed. Please try again.', 'error');
        }
    }
}

// Initialize payment manager when document is ready
$(document).ready(() => {
    window.paymentManager = new PaymentManager();
    
    // Handle payment return from external providers
    window.paymentManager.handlePaymentReturn();
    
    // Add subscription management buttons to the UI
    setTimeout(() => {
        const subscriptionSection = $('#subscription-section');
        if (subscriptionSection.length) {
            const managementButtons = $(`
                <div class="mt-4 text-center">
                    <button id="view-subscription-btn" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 mr-2">
                        View Subscription
                    </button>
                    <button id="cancel-subscription-btn" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                        Cancel Subscription
                    </button>
                </div>
            `);
            
            subscriptionSection.find('.bg-white').append(managementButtons);
            
            // Add event listeners
            $('#view-subscription-btn').on('click', async () => {
                const status = await window.paymentManager.getSubscriptionStatus();
                if (status) {
                    window.app.showNotification(
                        `Current plan: ${status.plan_type}, Status: ${status.status}`, 
                        'info'
                    );
                }
            });
            
            $('#cancel-subscription-btn').on('click', () => {
                window.paymentManager.cancelSubscription();
            });
        }
    }, 1000);
});
