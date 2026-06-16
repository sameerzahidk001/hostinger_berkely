@if(!admin_can_delete())
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(function (form) {
        var method = form.querySelector('input[name="_method"]');
        if (!method || method.value.toUpperCase() !== 'DELETE') {
            return;
        }

        var button = form.querySelector('button.btn-danger, button[type="submit"].btn-danger');
        if (!button) {
            return;
        }

        button.disabled = true;
        button.title = 'Delete is disabled for your role';
        form.addEventListener('submit', function (event) {
            event.preventDefault();
        });
    });

    window.confirmDelete = function () {
        return false;
    };
});
</script>
@endif
