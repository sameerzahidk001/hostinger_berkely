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

@if (session('fail'))
    <script>
        toastr.error(@json(session('fail')));
    </script>
@endif

@if (session('error'))
    <script>
        toastr.error(@json(session('error')));
    </script>
@endif

@if ($errors->any())
    <script>
        toastr.error(@json($errors->first() ?: 'Failed. Please try again.'));
    </script>
@endif