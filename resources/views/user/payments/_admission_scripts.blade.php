<script>
    $(document).on('click', '.payNowBtn', function () {
        var $btn = $(this);
        var installmentId = $btn.data('installment-id');
        var originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Redirecting...');

        $.ajax({
            url: '{{ route("user.generate.noonCheckout") }}',
            method: 'POST',
            data: {
                installment_id: installmentId
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                if (res.success !== false && res.checkoutUrl) {
                    window.location.href = res.checkoutUrl;
                    return;
                }

                $btn.prop('disabled', false).html(originalHtml);
                alert(res.error || 'Payment session could not be started. Please try again.');
            },
            error: function (err) {
                $btn.prop('disabled', false).html(originalHtml);
                var message = 'Payment session could not be started. Please try again.';
                try {
                    var body = JSON.parse(err.responseText);
                    if (body.error) {
                        message = body.error;
                    }
                } catch (e) {}
                alert(message);
            }
        });
    });
</script>
