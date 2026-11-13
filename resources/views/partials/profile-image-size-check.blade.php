<script>
    (function () {
        var defaultKb = {{ function_exists('lms_profile_image_max_kb') ? lms_profile_image_max_kb() : 300 }};
        window.lmsCheckProfileImageSize = function (input) {
            if (!input || !input.files || !input.files[0]) {
                return true;
            }
            var maxKb = parseInt(input.getAttribute('data-max-kb') || defaultKb, 10);
            if (isNaN(maxKb) || maxKb < 1) {
                maxKb = defaultKb;
            }
            var file = input.files[0];
            if (file.size > maxKb * 1024) {
                alert('Profile picture must be less than ' + maxKb + ' KB. This file is ' + Math.round(file.size / 1024) + ' KB.');
                input.value = '';
                return false;
            }
            return true;
        };
        document.addEventListener('change', function (event) {
            var target = event.target;
            if (!target || !target.matches || !target.matches('.lms-profile-image-input')) {
                return;
            }
            window.lmsCheckProfileImageSize(target);
        }, true);
    })();
</script>
