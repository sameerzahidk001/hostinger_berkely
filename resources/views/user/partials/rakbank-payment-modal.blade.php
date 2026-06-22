    @push('style')
        <style>
            .payment-summary-amount {
                border-top: 1px solid #e7eaec;
                margin-top: 16px;
                padding-top: 16px;
                text-align: center;
            }

            .rakbank-checkout-shell {
                position: relative;
                overflow: visible;
            }

            #hco-embedded,
            [id^="hco-embedded-"] {
                position: relative;
            }

            #hco-embedded iframe,
            [id^="hco-embedded-"] iframe {
                width: 100% !important;
                min-height: 360px;
                display: block;
                border: 0;
            }

            /*
             * Covers RakBank "Pay 2,700.00" — absolute inside shell (not fixed; modal-safe).
             * pointer-events: none lets clicks reach the real gateway button.
             */
            .rakbank-pay-button-replica {
                position: absolute;
                z-index: 9999;
                display: none;
                align-items: center;
                justify-content: center;
                pointer-events: none;
                border: 0;
                border-radius: 4px;
                box-sizing: border-box;
                background-color: #e5e7eb;
                margin: 0;
                padding: 0;
            }

            .rakbank-pay-button-replica.is-visible {
                display: flex !important;
            }

            .rakbank-pay-button-replica.is-enabled {
                background-color: #337ab7;
            }

            .rakbank-pay-button-replica__label {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                font-size: 18px;
                font-weight: 600;
                color: #6b7280;
                line-height: 1;
                pointer-events: none;
                user-select: none;
            }

            .rakbank-pay-button-replica.is-enabled .rakbank-pay-button-replica__label {
                color: #fff;
            }

            /* Same-document injection fallback */
            #hco-embedded #pay-button,
            [id^="hco-embedded-"] #pay-button {
                color: transparent !important;
                font-size: 0 !important;
                line-height: 1.25;
            }

            #hco-embedded #pay-button::after,
            [id^="hco-embedded-"] #pay-button::after {
                content: 'Pay';
                font-size: 18px;
                font-weight: 600;
                color: #6b7280;
            }

            #hco-embedded #pay-button:not(:disabled)::after,
            [id^="hco-embedded-"] #pay-button:not(:disabled)::after {
                color: #fff;
            }
        </style>
    @endpush

    @push('script')
        <script>
            window.__payButtonReplicaStop = window.__payButtonReplicaStop || null;

            function findCheckoutIframe(container) {
                if (!container) {
                    return null;
                }

                var iframe = container.querySelector('iframe');
                if (iframe) {
                    return iframe;
                }

                var modal = container.closest('.modal');
                if (modal) {
                    iframe = modal.querySelector('iframe');
                    if (iframe) {
                        return iframe;
                    }
                }

                return document.querySelector('#paymentModal iframe, [id^="paymentModal"] iframe');
            }

            function findGatewayPayButton(container) {
                if (!container) {
                    return null;
                }

                var payBtn = container.querySelector('#pay-button');
                if (payBtn) {
                    return payBtn;
                }

                var frames = container.querySelectorAll('iframe');
                for (var i = 0; i < frames.length; i++) {
                    try {
                        var doc = frames[i].contentDocument || frames[i].contentWindow.document;
                        payBtn = doc && doc.getElementById('pay-button');
                        if (payBtn) {
                            return payBtn;
                        }
                    } catch (e) {}
                }

                var modal = container.closest('.modal');
                if (modal) {
                    frames = modal.querySelectorAll('iframe');
                    for (var j = 0; j < frames.length; j++) {
                        try {
                            var modalDoc = frames[j].contentDocument || frames[j].contentWindow.document;
                            payBtn = modalDoc && modalDoc.getElementById('pay-button');
                            if (payBtn) {
                                return payBtn;
                            }
                        } catch (e) {}
                    }
                }

                return Array.from(container.querySelectorAll('button, [role="button"], input[type="submit"]')).find(function (btn) {
                    return /pay/i.test((btn.textContent || btn.value || '').trim());
                }) || null;
            }

            function getPayReplicaForContainer(container) {
                var shell = container.closest('.rakbank-checkout-shell');
                if (!shell) {
                    return null;
                }

                var replica = shell.querySelector('.rakbank-pay-button-replica');
                return replica ? { shell: shell, replica: replica } : null;
            }

            function syncRakbankPayReplica(containerSelector) {
                var container = document.querySelector(containerSelector);
                var parts = getPayReplicaForContainer(container);
                if (!container || !parts) {
                    return;
                }

                var shell = parts.shell;
                var replica = parts.replica;
                var shellRect = shell.getBoundingClientRect();
                var payBtn = findGatewayPayButton(container);
                var top;
                var left;
                var width;
                var height;
                var enabled = false;

                if (payBtn) {
                    var btnRect = payBtn.getBoundingClientRect();
                    top = btnRect.top - shellRect.top;
                    left = btnRect.left - shellRect.left;
                    width = btnRect.width;
                    height = btnRect.height;
                    enabled = !payBtn.disabled;
                } else {
                    var iframe = findCheckoutIframe(container);
                    if (!iframe) {
                        replica.classList.remove('is-visible');
                        return;
                    }

                    var frameRect = iframe.getBoundingClientRect();
                    height = 52;
                    width = frameRect.width;
                    left = frameRect.left - shellRect.left;
                    top = frameRect.bottom - shellRect.top - height;
                }

                if (width < 20 || height < 20) {
                    replica.classList.remove('is-visible');
                    return;
                }

                replica.style.top = Math.round(top) + 'px';
                replica.style.left = Math.round(left) + 'px';
                replica.style.width = Math.round(width) + 'px';
                replica.style.height = Math.round(height) + 'px';
                replica.classList.toggle('is-enabled', enabled);
                replica.classList.add('is-visible');
            }

            function installPayButtonReplica(containerSelector) {
                var container = document.querySelector(containerSelector);
                if (!container) {
                    return function () {};
                }

                var sync = function () {
                    syncRakbankPayReplica(containerSelector);
                };

                sync();

                var observer = new MutationObserver(sync);
                observer.observe(container, { childList: true, subtree: true, attributes: true });

                var modal = container.closest('.modal');
                if (modal) {
                    observer.observe(modal, { childList: true, subtree: true, attributes: true });
                }

                var resizeObserver = null;
                if (typeof ResizeObserver !== 'undefined') {
                    resizeObserver = new ResizeObserver(sync);
                    resizeObserver.observe(container);
                    var shell = container.closest('.rakbank-checkout-shell');
                    if (shell) {
                        resizeObserver.observe(shell);
                    }
                }

                window.addEventListener('resize', sync);
                window.addEventListener('scroll', sync, true);

                var intervalId = window.setInterval(sync, 200);
                [50, 150, 300, 600, 1000, 2000, 4000, 8000, 12000].forEach(function (delay) {
                    window.setTimeout(sync, delay);
                });

                var bindIframeLoad = function () {
                    container.querySelectorAll('iframe').forEach(function (frame) {
                        if (frame.dataset.replicaBound === '1') {
                            return;
                        }
                        frame.dataset.replicaBound = '1';
                        frame.addEventListener('load', sync);
                    });
                    var iframe = findCheckoutIframe(container);
                    if (iframe && iframe.dataset.replicaBound !== '1') {
                        iframe.dataset.replicaBound = '1';
                        iframe.addEventListener('load', sync);
                    }
                };
                bindIframeLoad();

                return function stopPayButtonReplica() {
                    observer.disconnect();
                    if (resizeObserver) {
                        resizeObserver.disconnect();
                    }
                    window.removeEventListener('resize', sync);
                    window.removeEventListener('scroll', sync, true);
                    window.clearInterval(intervalId);
                    var parts = getPayReplicaForContainer(container);
                    if (parts && parts.replica) {
                        parts.replica.classList.remove('is-visible');
                    }
                };
            }

            function schedulePayButtonCleanup(containerSelector) {
                if (typeof window.__payButtonReplicaStop === 'function') {
                    window.__payButtonReplicaStop();
                }
                window.__payButtonReplicaStop = installPayButtonReplica(containerSelector);
            }

            function stopPayButtonCleanup() {
                if (typeof window.__payButtonReplicaStop === 'function') {
                    window.__payButtonReplicaStop();
                    window.__payButtonReplicaStop = null;
                }
            }

            function renderPaymentModalSummary(targetSelector, res) {
                var amount = res.displayAmount || '';
                var aedLine = res.displayAmountAed || '';
                var html =
                    '<div class="payment-summary-amount" style="border-top:none;margin-top:0;padding-top:0;">' +
                        '<div style="font-size:13px;color:#666;margin-bottom:4px;">Amount to pay</div>' +
                        '<div style="font-size:22px;font-weight:700;">' + amount + '</div>';

                if (aedLine) {
                    html += '<div style="font-size:14px;color:#888;margin-top:6px;">(' + aedLine + ')</div>';
                }

                html += '</div>';

                $(targetSelector).html(html);
            }
        </script>
    @endpush
