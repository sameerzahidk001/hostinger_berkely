<div class="modal fade" id="payCurrencyModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">How would you like to pay?</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="payAmountBox" style="margin:0 0 16px;padding:12px 14px;background:#f8fafc;border:1px solid #e5e7eb;">
                    <div id="payItemName" style="font-weight:600;color:#111;margin-bottom:6px;"></div>
                    <div id="payAmountDue" style="font-size:20px;font-weight:700;color:#000435;"></div>
                    <div id="payAmountAlt" style="font-size:13px;color:#6b7280;margin-top:4px;"></div>
                </div>
                <button type="button" class="btn btn-primary btn-block" id="payUsdBtn">
                    I would like to pay in USD
                </button>
                <button type="button" class="btn btn-white btn-block" id="payAedBtn">
                    I would like to pay in AED
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
        var item = quote.item_name || 'Selected item';
        var currency = String(quote.package_currency || '').toUpperCase();
        var due = quote.amount_label || '';
        if (!due) {
            if (currency === 'USD') due = quote.usd_label || '';
            else if (currency === 'AED') due = quote.aed_label || '';
            else due = quote.usd_label || quote.aed_label || '';
        }
        $('#payItemName').text(item);
        $('#payAmountDue').text(due);
        $('#payAmountAlt').text('');
        if (quote.stripe_enabled) {
            $('#payUsdBtn').prop('disabled', false).text('I would like to pay in USD');
        } else {
            $('#payUsdBtn').prop('disabled', true).text('I would like to pay in USD — not available yet');
        }
        $('#payAedBtn').prop('disabled', false).text('I would like to pay in AED');
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
