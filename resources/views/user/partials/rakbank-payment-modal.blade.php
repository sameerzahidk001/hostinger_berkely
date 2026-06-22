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

            /* RakBank Angular checkout: hide "Pay 2,700.00" label, show only "Pay" */
            #hco-embedded #pay-button,
            #hco-embedded button[id="pay-button"],
            [id^="hco-embedded-"] #pay-button,
            [id^="hco-embedded-"] button[id="pay-button"] {
                position: relative;
                color: transparent !important;
                font-size: 0 !important;
                line-height: 1.25;
            }

            #hco-embedded #pay-button i,
            #hco-embedded #pay-button svg,
            #hco-embedded #pay-button img,
            [id^="hco-embedded-"] #pay-button i,
            [id^="hco-embedded-"] #pay-button svg,
            [id^="hco-embedded-"] #pay-button img {
                font-size: 1rem !important;
                opacity: 1 !important;
                color: #6c757d !important;
                vertical-align: middle;
            }

            #hco-embedded #pay-button::after,
            [id^="hco-embedded-"] #pay-button::after {
                content: 'Pay';
                font-size: 1.125rem;
                font-weight: 600;
                color: #6c757d;
                display: inline-block;
                vertical-align: middle;
                margin-left: 4px;
            }

            #hco-embedded #pay-button:not(:disabled)::after,
            [id^="hco-embedded-"] #pay-button:not(:disabled)::after {
                color: #fff;
            }
        </style>
    @endpush

    @push('script')
        <script>
            window.__payButtonLabelFixStop = window.__payButtonLabelFixStop || null;

            function replacePayAmountInButton(btn) {
                if (!btn) {
                    return;
                }

                const id = (btn.id || '').toLowerCase();
                const label = (btn.textContent || btn.value || '').trim();

                if (id !== 'pay-button' && !/^pay\b/i.test(label) && !/pay\s*[\d,.]/i.test(label)) {
                    return;
                }

                if (btn.tagName === 'INPUT') {
                    btn.value = 'Pay';
                    return;
                }

                Array.from(btn.childNodes).forEach(function (node) {
                    if (node.nodeType !== Node.TEXT_NODE) {
                        return;
                    }

                    const text = node.textContent || '';
                    if (/pay/i.test(text) && /[\d,.]/.test(text)) {
                        node.textContent = text.replace(/pay\s*[\d,.A-Za-z\s]*/i, 'Pay');
                    }
                });

                if (/pay\s*[\d,.]/i.test(btn.textContent || '')) {
                    const icons = btn.querySelectorAll('svg, i, img, span.material-icons');
                    if (icons.length) {
                        Array.from(btn.childNodes).forEach(function (node) {
                            if (node.nodeType === Node.TEXT_NODE && /[\d,.]/.test(node.textContent || '')) {
                                node.textContent = '';
                            }
                        });
                    } else {
                        btn.textContent = 'Pay';
                    }
                }
            }

            function normalizeEmbeddedPayButtons(containerSelector) {
                const roots = [];

                if (containerSelector) {
                    const root = document.querySelector(containerSelector);
                    if (root) {
                        roots.push(root);
                    }
                } else {
                    roots.push(document);
                }

                const scrub = function () {
                    roots.forEach(function (root) {
                        const payBtn = root.querySelector ? root.querySelector('#pay-button') : null;
                        if (payBtn) {
                            replacePayAmountInButton(payBtn);
                        }

                        if (root.querySelectorAll) {
                            root.querySelectorAll('button, [role="button"], input[type="submit"]').forEach(replacePayAmountInButton);
                        }

                        if (root.querySelectorAll) {
                            root.querySelectorAll('iframe').forEach(function (frame) {
                                try {
                                    const doc = frame.contentDocument || frame.contentWindow?.document;
                                    if (!doc) {
                                        return;
                                    }

                                    const iframePay = doc.getElementById('pay-button');
                                    if (iframePay) {
                                        replacePayAmountInButton(iframePay);
                                    }

                                    doc.querySelectorAll('button, [role="button"], input[type="submit"]').forEach(replacePayAmountInButton);
                                } catch (e) {
                                    // Cross-origin iframe
                                }
                            });
                        }
                    });

                    const globalPay = document.getElementById('pay-button');
                    if (globalPay) {
                        replacePayAmountInButton(globalPay);
                    }
                };

                scrub();

                const observer = new MutationObserver(scrub);
                roots.forEach(function (root) {
                    observer.observe(root, {
                        childList: true,
                        subtree: true,
                        characterData: true,
                    });
                });

                const intervalId = window.setInterval(scrub, 250);

                return function stop() {
                    observer.disconnect();
                    window.clearInterval(intervalId);
                };
            }

            function schedulePayButtonCleanup(containerSelector) {
                if (typeof window.__payButtonLabelFixStop === 'function') {
                    window.__payButtonLabelFixStop();
                    window.__payButtonLabelFixStop = null;
                }

                const root = containerSelector ? document.querySelector(containerSelector) : document;
                const scrubOnce = function () {
                    const payBtn = (root && root.querySelector('#pay-button')) || document.getElementById('pay-button');
                    if (payBtn) {
                        replacePayAmountInButton(payBtn);
                    }
                };

                window.__payButtonLabelFixStop = normalizeEmbeddedPayButtons(containerSelector);

                [100, 300, 600, 1000, 2000, 4000, 8000].forEach(function (delay) {
                    window.setTimeout(scrubOnce, delay);
                });
            }

            function stopPayButtonCleanup() {
                if (typeof window.__payButtonLabelFixStop === 'function') {
                    window.__payButtonLabelFixStop();
                    window.__payButtonLabelFixStop = null;
                }
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
