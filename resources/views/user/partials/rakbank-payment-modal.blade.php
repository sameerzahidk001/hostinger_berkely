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
            // Keep RakBank Hosted Checkout untouched — rewriting the Pay button
            // label breaks enable/click behavior on live.
            function schedulePayButtonCleanup() {}
            function stopPayButtonCleanup() {}
            function normalizeEmbeddedPayButtons() {}

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
