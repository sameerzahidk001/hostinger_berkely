@once
    @push('style')
        <style>
            .payment-embed-wrap {
                position: relative;
                min-height: 360px;
            }

            .payment-embed-wrap iframe {
                width: 100% !important;
                min-height: 360px;
            }

            .payment-btn-overlay {
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0;
                height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                background: #dcdcdc;
                color: #4a4a4a;
                font-size: 16px;
                font-weight: 600;
                pointer-events: none;
                border-radius: 4px;
                z-index: 2;
            }
        </style>
    @endpush

    @push('script')
        <script>
            function applyPayButtonOverlay(embeddedSelector, payButtonLabel) {
                if (!payButtonLabel) {
                    return;
                }

                var $embedded = $(embeddedSelector);
                var $wrap = $embedded.closest('.payment-embed-wrap');

                if (!$wrap.length) {
                    $embedded.wrap('<div class="payment-embed-wrap"></div>');
                    $wrap = $embedded.parent();
                }

                $wrap.find('.payment-btn-overlay').remove();
                $wrap.append(
                    '<div class="payment-btn-overlay">' +
                        '<i class="fa fa-lock"></i> ' + payButtonLabel +
                    '</div>'
                );
            }

            function showRakBankEmbeddedCheckout(embeddedSelector, sessionId, payButtonLabel) {
                try {
                    Checkout.configure({
                        session: { id: sessionId },
                    });
                    Checkout.showEmbeddedPage(embeddedSelector);
                    setTimeout(function () {
                        applyPayButtonOverlay(embeddedSelector, payButtonLabel);
                    }, 700);
                    setTimeout(function () {
                        applyPayButtonOverlay(embeddedSelector, payButtonLabel);
                    }, 1500);
                } catch (error) {
                    throw error;
                }
            }
        </script>
    @endpush
@endonce
