@if (session('sucess'))
    <script>
        toastr.success('{{ session('sucess') }}');
    </script>
@endif

@if (session('failed'))
    <script>
        toastr.error('{{ session('failed') }}');
    </script>
@endif

@if ($errors->any())
    <script>
        toastr.error('Failed. Please try again.');
    </script>
@endif