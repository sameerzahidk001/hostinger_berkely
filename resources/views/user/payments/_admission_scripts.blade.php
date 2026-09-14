<script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>

<script>
    (function () {
        let currentInstallmentId = null;
        let currentSettlingAmount = null;
        let currentOrderId = null;
        let checkoutScriptLoaded = false;

        function storePendingPaymentContext() {
            try {
                sessionStorage.setItem('rakbank_pending', JSON.stringify({
                    installment_id: currentInstallmentId,
                    amount: currentSettlingAmount,
                    order_id: currentOrderId,
                }));
            } catch (e) {}
        }

        function readPendingPaymentContext() {
            try {
                return JSON.parse(sessionStorage.getItem('rakbank_pending') || '{}');
            } catch (e) {
                return {};
            }
        }

        function clearPendingPaymentContext() {
            sessionStorage.removeItem('rakbank_pending');
        }

        function resetPaymentModal() {
            $('#payment-amount-display').empty();
            $('#hco-embedded').empty();
            $('#payment-error').hide().empty();
            $('#payment-loading').hide();
        }

        function loadCheckoutScript(callback) {
            if (typeof Checkout !== 'undefined') {
                callback();
                return;
            }

            if (checkoutScriptLoaded) {
                const waitForCheckout = setInterval(function () {
                    if (typeof Checkout !== 'undefined') {
                        clearInterval(waitForCheckout);
                        callback();
                    }
                }, 50);
                return;
            }

            checkoutScriptLoaded = true;
            const script = document.createElement('script');
            script.src = 'https://rakbankpay-nam.gateway.mastercard.com/static/checkout/checkout.min.js';
            script.setAttribute('data-error', 'errorCallback');
            script.setAttribute('data-cancel', 'cancelCallback');
            script.setAttribute('data-complete', 'completeCallback');
            script.onload = callback;
            script.onerror = function () {
                checkoutScriptLoaded = false;
                $('#payment-loading').hide();
            };
            document.head.appendChild(script);
        }

        window.errorCallback = function (error) {
            console.log(JSON.stringify(error));
        };
        window.cancelCallback = function () {
            console.log('Payment cancelled');
        };
        window.completeCallback = function () {
            const pending = readPendingPaymentContext();
            if (!pending.installment_id || !pending.amount) {
                return;
            }

            $.ajax({
                url: '{{ route("user.update.installment") }}',
                method: 'POST',
                data: {
                    amount: pending.amount,
                    installment_id: pending.installment_id,
                    order_id: pending.order_id || null,
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                    if (res.success == true) {
                        clearPendingPaymentContext();
                        $(".modal.show").modal('hide');
                        if (res.receipt_url) {
                            window.location.href = res.receipt_url;
                            return;
                        }
                        window.location.reload();
                    }
                },
                error: function () {
                    alert('Payment was taken but receipt could not be created automatically. Please refresh the page or contact support.');
                }
            });
        };

        function loadEmbeddedCheckout(sessionId) {
            loadCheckoutScript(function () {
                try {
                    Checkout.configure({
                        session: { id: sessionId },
                    });
                    $('#payment-loading').hide();
                    Checkout.showEmbeddedPage('#hco-embedded');
                } catch (error) {
                    $('#payment-loading').hide();
                    $('#payment-error').text('Unable to load payment form. Please try again.').show();
                }
            });
        }

        $(document).on('click', '.payNowBtn', function () {
            currentInstallmentId = $(this).data('installment-id');
            resetPaymentModal();
            $('#paymentModal').modal('show');
        });

        $('#paymentModal').on('shown.bs.modal', function () {
            resetPaymentModal();
            $('#payment-loading').show();

            $.ajax({
                url: '{{ route("user.generate.rakBankPaySession") }}',
                method: 'POST',
                data: {
                    installment_id: currentInstallmentId,
                    return_url: window.location.href
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                    $('#payment-loading').hide();
                    if (res.displayAmount && typeof renderPaymentModalSummary === 'function') {
                        renderPaymentModalSummary('#payment-amount-display', res);
                    }
                    currentSettlingAmount = res.settlingAmount || null;
                    currentOrderId = res.orderId || null;
                    storePendingPaymentContext();

                    if (res.success !== false && res.session && res.session.id) {
                        loadEmbeddedCheckout(res.session.id);
                    } else {
                        $('#payment-error').text(res.error || 'Payment session could not be started. Please try again.').show();
                    }
                },
                error: function (err) {
                    $('#payment-loading').hide();
                    var message = 'Payment session could not be started. Please try again.';
                    try {
                        var body = JSON.parse(err.responseText);
                        if (body.error) {
                            message = body.error;
                        }
                    } catch (e) {}
                    $('#payment-error').text(message).show();
                }
            });
        });

        $('#paymentModal').on('hidden.bs.modal', function () {
            resetPaymentModal();
        });
    })();
</script>

<script>
    $(document).ready(function () {
        $('.dataTables-example').DataTable({
            pageLength: 10,
            searching: true,
            lengthChange: true,
            paging: true,
            info: false,
            ordering: true,
            responsive: true,
            dom: '<"admin-dt-toolbar"<l><f>>rtip',
            order: [[2, 'desc']]
        });
    });
</script>
