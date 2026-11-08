<div class="modal fade" id="payCurrencyModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">How would you like to pay?</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <div class="modal-body">
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

    function showPayCurrencyModal(quote) {
        quote = quote || {};
        if (quote.stripe_enabled) {
            $('#payUsdBtn').prop('disabled', false).text('Pay in USD (Stripe) — ' + (quote.usd_label || 'USD'));
        } else {
            $('#payUsdBtn').prop('disabled', true).text('Pay in USD (Stripe) — not available yet');
        }
        $('#payAedBtn').prop('disabled', false).text('Pay in AED — ' + (quote.aed_label || 'AED'));
        $('#payCurrencyModal').modal('show');
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
                restoreBtn();
                showPayCurrencyModal(quote);
            },
            error: function () {
                restoreBtn();
                showPayCurrencyModal({});
            }
        });
    });

    $('#payUsdBtn').on('click', function () {
        if ($(this).prop('disabled')) {
            return;
        }
        $('#payCurrencyModal').modal('hide');
        if ($activeBtn) {
            $activeBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Opening Stripe...');
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
