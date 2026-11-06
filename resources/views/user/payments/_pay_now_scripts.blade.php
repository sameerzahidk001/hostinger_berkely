<div class="modal fade" id="payCurrencyModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">How would you like to pay?</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" id="payCurrencyHint">Choose USD (Stripe) or AED (existing methods).</p>
                <button type="button" class="btn btn-primary btn-block" id="payUsdBtn">
                    Pay in USD (Stripe)
                </button>
                <button type="button" class="btn btn-white btn-block" id="payAedBtn">
                    Pay in AED
                </button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var selectedId = null;
    var $activeBtn = null;
    var originalHtml = '';

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function restoreBtn() {
        if ($activeBtn) {
            $activeBtn.prop('disabled', false).html(originalHtml);
        }
    }

    function startNoon(installmentId) {
        $.ajax({
            url: '{{ route("user.generate.noonCheckout") }}',
            method: 'POST',
            data: { installment_id: installmentId },
            headers: { 'X-CSRF-TOKEN': csrf() },
            success: function (res) {
                if (res.success !== false && res.checkoutUrl) {
                    window.location.href = res.checkoutUrl;
                    return;
                }
                restoreBtn();
                alert(res.error || 'AED payment could not be started.');
            },
            error: function (err) {
                restoreBtn();
                var message = 'AED payment could not be started.';
                try {
                    var body = JSON.parse(err.responseText);
                    if (body.error) message = body.error;
                } catch (e) {}
                alert(message);
            }
        });
    }

    function startStripe(installmentId) {
        $.ajax({
            url: '{{ route("user.generate.stripeCheckout") }}',
            method: 'POST',
            data: { installment_id: installmentId },
            headers: { 'X-CSRF-TOKEN': csrf() },
            success: function (res) {
                if (res.success !== false && res.checkoutUrl) {
                    window.location.href = res.checkoutUrl;
                    return;
                }
                restoreBtn();
                alert(res.error || 'USD Stripe payment could not be started.');
            },
            error: function (err) {
                restoreBtn();
                var message = 'USD Stripe payment could not be started.';
                try {
                    var body = JSON.parse(err.responseText);
                    if (body.error) message = body.error;
                } catch (e) {}
                alert(message);
            }
        });
    }

    $(document).on('click', '.payNowBtn', function () {
        $activeBtn = $(this);
        selectedId = $activeBtn.data('installment-id');
        originalHtml = $activeBtn.html();
        $activeBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Please wait...');

        $.ajax({
            url: '{{ route("user.pay.quote") }}',
            method: 'POST',
            data: { installment_id: selectedId },
            headers: { 'X-CSRF-TOKEN': csrf() },
            success: function (res) {
                var quote = (res && res.quote) ? res.quote : {};
                if (!quote.stripe_enabled) {
                    startNoon(selectedId);
                    return;
                }
                $('#payUsdBtn').text('Pay in USD (Stripe) — ' + (quote.usd_label || 'USD'));
                $('#payAedBtn').text('Pay in AED — ' + (quote.aed_label || 'AED'));
                var hint = 'Amounts are locked for this payment session.';
                if (quote.rate) {
                    hint += ' Rate: 1 USD = ' + Number(quote.rate).toFixed(4) + ' AED.';
                }
                $('#payCurrencyHint').text(hint);
                restoreBtn();
                $('#payCurrencyModal').modal('show');
            },
            error: function () {
                startNoon(selectedId);
            }
        });
    });

    $('#payUsdBtn').on('click', function () {
        $('#payCurrencyModal').modal('hide');
        if ($activeBtn) {
            $activeBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Redirecting...');
        }
        startStripe(selectedId);
    });

    $('#payAedBtn').on('click', function () {
        $('#payCurrencyModal').modal('hide');
        if ($activeBtn) {
            $activeBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Redirecting...');
        }
        startNoon(selectedId);
    });
})();
</script>
