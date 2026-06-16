@extends('admin.layout.app')
@section('title', 'Categories')
@push('style')
<style>
  td{
    vertical-align: middle !important;
  }
</style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Categories</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            <li>
                <a>Categories</a>
            </li>
            <li class="active">
                <strong>All</strong>
            </li>
        </ol>
    </div>
    <div class="col-lg-2">
        
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="wrapper wrapper-content">
            <!-- <div class="col-lg-12"> -->
                <div class="ibox float-e-margins" style="margin-bottom: 0px;">
                    <div class="ibox-title">
                        <h5>Subjects</h5>
                        <div class="ibox-tools">
                            <a class="btn-primary btn btn-xs" href="{{ route('subject.create') }}">
                              <i class="fa fa-plus-square"></i> &nbsp;Add
                            </a>
                            <a class="collapse-link">
                                <i class="fa fa-chevron-up"></i>
                            </a>
                            
                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="ibox-content">

                        <table class="table">
                            <thead>
                            <tr>
                              <th>ID</th>
                              <th>Image</th>
                              <th>Name</th>
                              <th>Priority</th>
                              <!-- <th>Short Description</th> -->
                              <th>Description</th>
                              <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                              @forelse($subjects as $index => $data)
                                <tr>
                                  <th scope="row">{{ ++$index }}</th>
                                  <td>
                                    <a href="{{ asset($data->image) }}"  target="_blank">
                                      <img src="{{ asset($data->image) }}" alt="" class="rounded img-fluid" height="50">
                                    </a>
                                  </td>
                                  <td>{{ $data->name }}</td>
                                  <td>{{ $data->priority }}</td>
                                  <!-- <td>
                                    <textarea rows="1" class="form-control" readonly="">{{ $data->short_description }}</textarea>
                                  </td> -->
                                  <td>
                                    <textarea rows="1" class="form-control" readonly="">{{ $data->description }}</textarea>
                                  </td>
                                  <td>
                                      <div class="btn-group">
                                          <a href="{{ route('subject.edit', ['id' => $data->id]) }}" class="btn-primary btn btn-xs"><i class="fa fa-trash"></i> Edit</a>
                                          <button type="button" class="btn-danger btn btn-xs delete-btn" data-id="{{ $data->id }}"><i class="fa fa-trash"></i> Delete</button>
                                      </div>
                                  </td>
                                </tr>
                                @empty
                              @endforelse
                            </tbody>
                        </table>

                    </div>
                </div>
            <!-- </div> -->
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $('.delete-btn').click(function () {
        var id = $(this).data('id');
        var button = $(this);

        swal({
            title: "Are you sure?",
            text: "You want to Delete this Record?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            closeOnConfirm: false
        }, function () {
            // AJAX request
            $.ajax({
                url: "{{ route('subject.delete') }}",
                method: "POST",
                data: { id: id },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {

                    swal("Deleted!", "Your Record has been deleted.", "success");
                    button.closest('tr').fadeOut('slow', function() {
                        $(this).remove();
                    });
                    
                },
                error: function (xhr, status, error) {
                    // Handle error
                    swal("Error!", "Failed to delete the record.", "error");
                }
            });
        });
    });

</script>
@endpush