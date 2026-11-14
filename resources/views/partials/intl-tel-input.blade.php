@once
@push('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" media="print" onload="this.media='all'">
@endpush
@push('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js" defer></script>
<script>
    window.addEventListener('load', function () {
        document.querySelectorAll('#phone, #phone2').forEach(function (input) {
            if (!window.intlTelInput || input.getAttribute('data-intl-ready')) {
                return;
            }
            window.intlTelInput(input, {
                initialCountry: input.id === 'phone' ? 'ae' : 'gb',
                separateDialCode: true,
                utilsScript: '/intl-tel-input/js/utils.js?1716383386062'
            });
            input.setAttribute('data-intl-ready', '1');
        });
    });
</script>
@endpush
@endonce
