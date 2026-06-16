<script>
document.addEventListener('DOMContentLoaded', function () {
    function attachInputCounter(input) {
        const max = parseInt(input.getAttribute('data-maxlength') || input.getAttribute('maxlength') || '0', 10);
        if (!max) return;

        const counter = document.createElement('div');
        counter.className = 'char-count text-muted small text-right';
        counter.style.marginTop = '4px';
        input.parentNode.appendChild(counter);

        const update = () => {
            const length = (input.value || '').length;
            counter.textContent = length + ' / ' + max + ' characters';
            counter.style.color = length > max ? '#bc1904' : '#888';
        };

        input.addEventListener('input', update);
        update();
    }

    document.querySelectorAll('input[data-maxlength], textarea[data-maxlength], input[maxlength], textarea[maxlength]').forEach(attachInputCounter);

    document.querySelectorAll('.text-editor[data-maxlength]').forEach(function (el) {
        const max = parseInt(el.getAttribute('data-maxlength'), 10);
        if (!max) return;

        const counter = document.createElement('div');
        counter.className = 'char-count text-muted small text-right';
        counter.style.marginTop = '4px';
        el.parentNode.appendChild(counter);

        const update = () => {
            const text = el.value || el.textContent || '';
            const length = text.replace(/<[^>]*>/g, '').length;
            counter.textContent = length + ' / ' + max + ' characters';
            counter.style.color = length > max ? '#bc1904' : '#888';
        };

        el.addEventListener('input', update);
        setInterval(update, 1000);
        update();
    });
});
</script>
