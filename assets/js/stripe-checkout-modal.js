(function (window) {
    'use strict';

    class StripeCheckoutModal {
        constructor(options) {
            this.options = Object.assign({
                modalId: 'stripeCheckoutModal',
                createPaymentUrl: '',
                publishableKey: '',
                csrfToken: '',
                returnUrl: window.location.href,
                appearance: {
                    theme: 'flat',
                    variables: {
                        colorPrimary: '#ee2d7a',
                        colorBackground: '#ffffff',
                        colorText: '#111827',
                        colorTextDisabled: '#667085',
                        colorDanger: '#dc2626',
                        colorTextSecondary: '#667085',
                        colorTextPlaceholder: '#98a2b3',
                        colorIconTab: '#667085',
                        colorIconTabSelected: '#ee2d7a',
                        colorIconCardError: '#dc2626',
                        colorIconCardCvc: '#667085',
                        fontFamily: 'Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                        fontSizeBase: '15px',
                        borderRadius: '8px',
                        spacingUnit: '4px'
                    },
                    rules: {
                        '.Block': {
                            backgroundColor: '#ffffff',
                            boxShadow: 'none'
                        },
                        '.BlockDivider': {
                            backgroundColor: '#eaecf0'
                        },
                        '.BlockLabel': {
                            color: '#344054',
                            fontWeight: '600'
                        },
                        '.Input': {
                            backgroundColor: '#ffffff',
                            border: '1px solid #d0d5dd',
                            boxShadow: 'none',
                            color: '#111827',
                            padding: '13px 12px'
                        },
                        '.Input--invalid': {
                            backgroundColor: '#ffffff',
                            border: '1px solid #dc2626',
                            color: '#111827'
                        },
                        '.Input:focus': {
                            border: '1px solid #ee2d7a',
                            boxShadow: '0 0 0 3px rgba(238, 45, 122, 0.13)'
                        },
                        '.Label': {
                            color: '#344054',
                            fontWeight: '600',
                            fontSize: '13px'
                        },
                        '.Tab': {
                            backgroundColor: '#ffffff',
                            border: '1px solid #d0d5dd',
                            color: '#344054',
                            boxShadow: 'none'
                        },
                        '.Tab:hover': {
                            borderColor: '#ee2d7a'
                        },
                        '.Tab--selected': {
                            backgroundColor: '#ffffff',
                            color: '#101828',
                            borderColor: '#ee2d7a',
                            boxShadow: '0 0 0 3px rgba(238, 45, 122, 0.10)'
                        },
                        '.Dropdown': {
                            backgroundColor: '#ffffff',
                            border: '1px solid #d0d5dd',
                            color: '#111827'
                        },
                        '.CheckboxInput': {
                            backgroundColor: '#ffffff',
                            border: '1px solid #d0d5dd'
                        },
                        '.CheckboxLabel': {
                            color: '#475467'
                        }
                    }
                }
            }, options || {});

            this.modalEl = document.getElementById(this.options.modalId);
            this.stripe = this.options.publishableKey && window.Stripe ? window.Stripe(this.options.publishableKey) : null;
            this.elements = null;
            this.paymentElement = null;
            this.clientSecret = '';
            this.paymentIntentId = '';
            this.pendingPayload = null;
            this.resolvePayment = null;
            this.rejectPayment = null;

            if (!this.modalEl) {
                throw new Error('Stripe checkout modal markup was not found.');
            }

            this.bsModal = window.bootstrap ? new window.bootstrap.Modal(this.modalEl) : null;
            this.payButton = this.modalEl.querySelector('[data-stripe-checkout-pay]');
            this.payText = this.modalEl.querySelector('[data-stripe-checkout-pay-text]');
            this.spinner = this.modalEl.querySelector('[data-stripe-checkout-spinner]');
            this.message = this.modalEl.querySelector('[data-stripe-checkout-message]');
            this.summary = this.modalEl.querySelector('[data-stripe-checkout-summary]');
            this.orderSummary = this.modalEl.querySelector('[data-stripe-checkout-order]');
            this.customerSummary = this.modalEl.querySelector('[data-stripe-checkout-customer]');
            this.amountSummary = this.modalEl.querySelector('[data-stripe-checkout-amount]');
            const csrfInput = this.modalEl.querySelector('[data-stripe-checkout-csrf]');
            this.csrfToken = this.options.csrfToken || (csrfInput ? csrfInput.value : '');

            this.payButton?.addEventListener('click', () => this.confirm());
            this.modalEl.addEventListener('hidden.bs.modal', () => {
                if (this.rejectPayment) {
                    this.rejectPayment(new Error('Payment was cancelled.'));
                }
                this.cleanupPromise();
            });
        }

        async open(payload) {
            if (!this.stripe) {
                throw new Error('Stripe is not available. Please contact support.');
            }

            this.pendingPayload = Object.assign({}, payload || {});
            this.setMessage('', 'info');
            this.setBusy(true, 'Preparing...');
            this.setSummary(this.pendingPayload);
            this.show();

            let intent;
            try {
                intent = await this.createPaymentIntent(this.pendingPayload);
            } catch (error) {
                this.setBusy(false, 'Pay now');
                this.setMessage(error.message || 'Unable to initialize Stripe payment.', 'error');
                this.hide();
                throw error;
            }
            if (intent.skip_payment) {
                this.hide();
                return {
                    payment_intent_id: intent.payment_intent_id || 'TEST_ZERO_AMOUNT',
                    status: 'succeeded',
                    skip_payment: true
                };
            }

            this.clientSecret = intent.client_secret;
            this.paymentIntentId = intent.payment_intent_id;
            this.mountPaymentElement(this.clientSecret);
            this.setBusy(false, 'Pay now');

            return new Promise((resolve, reject) => {
                this.resolvePayment = resolve;
                this.rejectPayment = reject;
            });
        }

        async createPaymentIntent(payload) {
            const response = await fetch(this.options.createPaymentUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify(payload)
            });

            const data = await this.readJsonResponse(response, 'Payment request failed.');
            if (!response.ok || !data.success || !data.data) {
                throw new Error(data.message || 'Unable to initialize Stripe payment.');
            }

            return data.data;
        }

        mountPaymentElement(clientSecret) {
            if (this.paymentElement) {
                this.paymentElement.unmount();
                this.paymentElement = null;
            }

            this.elements = this.stripe.elements({
                clientSecret: clientSecret,
                appearance: this.options.appearance
            });
            this.paymentElement = this.elements.create('payment', {
                layout: {
                    type: 'tabs',
                    defaultCollapsed: false
                },
                defaultValues: this.defaultValues()
            });
            this.paymentElement.mount(this.modalEl.querySelector('[data-stripe-payment-element]'));
        }

        async confirm() {
            if (!this.elements || !this.clientSecret) {
                this.setMessage('Payment is still loading. Please wait a moment.', 'error');
                return;
            }

            this.setBusy(true, 'Paying...');
            this.setMessage('', 'info');

            const result = await this.stripe.confirmPayment({
                elements: this.elements,
                confirmParams: {
                    return_url: this.options.returnUrl,
                    payment_method_data: {
                        billing_details: this.billingDetails()
                    }
                },
                redirect: 'if_required'
            });

            if (result.error) {
                this.setBusy(false, 'Pay now');
                this.setMessage(result.error.message || 'Payment failed.', 'error');
                return;
            }

            const paymentIntent = result.paymentIntent || {};
            if (paymentIntent.status !== 'succeeded') {
                this.setBusy(false, 'Pay now');
                this.setMessage('Payment was not completed. Please try again.', 'error');
                return;
            }

            const output = {
                payment_intent_id: paymentIntent.id || this.paymentIntentId,
                status: paymentIntent.status
            };
            const resolver = this.resolvePayment;
            this.cleanupPromise();
            this.hide();
            if (resolver) {
                resolver(output);
            }
        }

        billingDetails() {
            const billing = this.pendingPayload?.billing || {};
            return {
                name: [billing.first_name, billing.last_name].filter(Boolean).join(' ').trim() || undefined,
                email: billing.email || undefined,
                phone: billing.phone || undefined,
                address: {
                    line1: billing.address1 || undefined,
                    line2: billing.address2 || undefined,
                    city: billing.city || undefined,
                    state: billing.state || undefined,
                    postal_code: billing.zip || undefined,
                    country: billing.country || undefined
                }
            };
        }

        defaultValues() {
            return {
                billingDetails: this.billingDetails()
            };
        }

        setSummary(payload) {
            if (!this.summary) return;
            const amount = Number(payload.amount || 0);
            const currency = String(payload.currency || 'usd').toUpperCase();
            const orderId = payload.order_id ? 'Order ' + payload.order_id + ' - ' : '';
            const formattedAmount = new Intl.NumberFormat(undefined, {
                style: 'currency',
                currency: currency
            }).format(amount);
            this.summary.textContent = orderId + formattedAmount;

            if (this.orderSummary && this.customerSummary && this.amountSummary) {
                this.customerSummary.textContent = payload.customer_name || payload.email || 'Customer';
                this.amountSummary.textContent = formattedAmount;
                this.orderSummary.hidden = false;
            }
        }

        setMessage(text, type) {
            if (!this.message) return;
            this.message.textContent = text || '';
            this.message.hidden = !text;
            this.message.classList.toggle('stripe-checkout-modal__notice--error', type === 'error');
            this.message.classList.toggle('stripe-checkout-modal__notice--info', type !== 'error');
        }

        setBusy(isBusy, label) {
            if (this.payButton) this.payButton.disabled = isBusy;
            if (this.payText && label) this.payText.textContent = label;
            if (this.spinner) this.spinner.hidden = !isBusy;
        }

        show() {
            if (this.bsModal) {
                this.bsModal.show();
                return;
            }
            this.modalEl.style.display = 'block';
        }

        hide() {
            if (this.bsModal) {
                this.bsModal.hide();
                return;
            }
            this.modalEl.style.display = 'none';
        }

        cleanupPromise() {
            this.resolvePayment = null;
            this.rejectPayment = null;
        }

        async readJsonResponse(response, fallbackMessage) {
            const text = await response.text();
            if (!text.trim()) {
                throw new Error(fallbackMessage || 'Server returned an empty response.');
            }

            try {
                return JSON.parse(text);
            } catch (error) {
                const cleanText = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                throw new Error(cleanText || fallbackMessage || 'Server returned an invalid response.');
            }
        }
    }

    window.StripeCheckoutModal = StripeCheckoutModal;
})(window);
