<?php
require_once __DIR__ . '/security.php';

function stripe_checkout_modal_render(array $options = []): void
{
    $modalId = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($options['id'] ?? 'stripeCheckoutModal'));
    if ($modalId === '') {
        $modalId = 'stripeCheckoutModal';
    }

    $title = (string) ($options['title'] ?? 'Secure card payment');
    $payButtonText = (string) ($options['pay_button_text'] ?? 'Pay now');
    $csrfToken = (string) ($options['csrf_token'] ?? csrf_token());
    ?>
    <div class="modal fade stripe-checkout-modal" id="<?php echo e($modalId); ?>" tabindex="-1" aria-labelledby="<?php echo e($modalId); ?>Label" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="stripe-checkout-modal__heading">
                        <span class="stripe-checkout-modal__brand">Stripe</span>
                        <h5 class="modal-title" id="<?php echo e($modalId); ?>Label"><?php echo e($title); ?></h5>
                        <p class="stripe-checkout-modal__subtitle mb-0" data-stripe-checkout-summary></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" data-stripe-checkout-close></button>
                </div>
                <div class="modal-body">
                    <div class="stripe-checkout-modal__order" data-stripe-checkout-order hidden>
                        <div>
                            <span class="stripe-checkout-modal__order-label">Paying as</span>
                            <strong data-stripe-checkout-customer></strong>
                        </div>
                        <div>
                            <span class="stripe-checkout-modal__order-label">Amount</span>
                            <strong data-stripe-checkout-amount></strong>
                        </div>
                    </div>
                    <div class="stripe-checkout-modal__notice" data-stripe-checkout-message hidden></div>
                    <div class="stripe-checkout-modal__element" data-stripe-payment-element></div>
                    <input type="hidden" data-stripe-checkout-csrf value="<?php echo e($csrfToken); ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-stripe-checkout-cancel>Cancel</button>
                    <button type="button" class="btn btn-primary stripe-checkout-modal__pay-btn" data-stripe-checkout-pay>
                        <span data-stripe-checkout-pay-text><?php echo e($payButtonText); ?></span>
                        <span class="spinner-border spinner-border-sm ms-2" data-stripe-checkout-spinner hidden></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
