@once
    @push('style')
        <style>
            .payment-summary-table td {
                padding: 5px 10px 5px 0;
                font-size: 13px;
                vertical-align: top;
            }

            .payment-summary-table td:first-child {
                color: #676a6c;
                white-space: nowrap;
                width: 38%;
            }

            .payment-summary-table td:last-child {
                font-weight: 500;
                color: #333;
            }

            .payment-summary-amount {
                border-top: 1px solid #e7eaec;
                margin-top: 16px;
                padding-top: 16px;
                text-align: center;
            }

            #hco-embedded iframe,
            [id^="hco-embedded-"] iframe {
                width: 100% !important;
                min-height: 360px;
            }
        </style>
    @endpush

    @push('script')
        <script>
            function normalizeEmbeddedPayButtons(containerSelector) {
                const root = document.querySelector(containerSelector);
                if (!root) {
                    return;
                }

                const scrub = function () {
                    root.querySelectorAll('button, [role="button"], input[type="submit"]').forEach(function (btn) {
                        const text = (btn.textContent || btn.value || '').trim();
                        if (/^pay\b/i.test(text)) {
                            if (btn.tagName === 'INPUT') {
                                btn.value = 'Pay';
                            } else {
                                btn.textContent = 'Pay';
                            }
                        }
                    });
                };

                scrub();
                const observer = new MutationObserver(scrub);
                observer.observe(root, { childList: true, subtree: true, characterData: true });
                setTimeout(function () {
                    observer.disconnect();
                }, 30000);
            }

            function renderPaymentModalSummary(targetSelector, res) {
                var amount = res.displayAmount || '';

                $(targetSelector).html(
                    '<div class="payment-summary-amount" style="border-top:none;margin-top:0;padding-top:0;">' +
                        '<div style="font-size:13px;color:#666;margin-bottom:4px;">Amount to pay</div>' +
                        '<div style="font-size:22px;font-weight:700;">' + amount + '</div>' +
                    '</div>'
                );
            }
        </script>
    @endpush
@endonce
