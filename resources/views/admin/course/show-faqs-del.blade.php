@extends('admin.layout.app')
@section('title', 'FAQs')
@push('style')
<style>
    .ibox-title-cust{
        background-color: #ffffff;
        color: inherit;
        margin-bottom: 0;
        min-height: 48px;
        display: flex;
        justify-content: space-between;
    }

    form > .row > [class^="col-"] {
        margin-bottom: 16px;
    }
</style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Course</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            <li>
                <a>Courses</a>
            </li>
            <li>
                <a>{{ $course->title }}</a>
            </li>
            <li>
                <a>FAQs</a>
            </li>
            
        </ol>
    </div>
    <div class="col-lg-2">

    </div>
</div>

<div class="row">
    <div class="wrapper wrapper-content">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0px;">
                <div class="ibox-title">
                    <h5>Add FAQ's to {{ $course->title }}</h5>
                    <div class="ibox-tools">
                        
                        <a class="collapse-link">
                            <i class="fa fa-chevron-up"></i>
                        </a>

                        <a class="close-link">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                </div>
                
                <div class="ibox-content">
                    <form action="{{ route('course.store-faqs') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $course->id }}">
                        <div class="row">
                            <div class="col-lg-12">
                                <label class="mb-1">Question</label>
                                <textarea name="title" id="" rows="1" class="form-control" class placeholder="Add FAQ Question">{{ old('title', $page_seo->title) }}</textarea>
                            </div>
                            <div class="col-lg-12">
                                <label class="mb-1">Answer</label>
                                <textarea name="description" id="" rows="2" class="form-control text-editor" placeholder="Add FAQ Answer">{{ old('description', $page_seo->description) }}</textarea>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;text-align:right;"> 
                                <button class="btn btn-primary " type="submit"><i class="fa fa-check"></i>&nbsp;Add</button>
                                <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="wrapper wrapper-content">
        <div class="col-lg-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>{{ $course->title }} FAQs List</h5>
                    <div class="ibox-tools">
                        
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
                                <th>Sr#</th>
                                <th>Question</th>
                                <th>Answer</th>

                                <th style="width: 13%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($course->courseFaq as $index => $data)
                            <tr id="row_{{$data->id}}">
                                <td style="vertical-align: middle;">{{ ++$index }}</td>
                                <td>
                                    <textarea name="title" id="title_{{$data->id}}" rows="4" class="form-control">{{ $data->title }}</textarea>
                                </td>
                                <td>
                                    <textarea name="description" id="description_{{$data->id}}" rows="4" class="form-control">{{ $data->short_description }}</textarea>
                                    
                                </td>
                                <td style="vertical-align: middle;">
                                    <div class="btn-group">
                                        <button type="button" class="btn-primary btn btn-xs editFaqs" data-id="{{ $data->id }}" data-courseid="{{ $course->id }}" data-faqid="{{ $data->id }}"><i class="fa fa-edit"></i> Update</button>
                                        <button type="button" class="btn-danger btn btn-xs delete-btn" data-module="courseFaq" data-id="{{ $data->id }}"><i class="fa fa-trash"></i> Delete</button>
                                    </div>
                                </td>
                            </tr>
                            @empty 
                            <tr>
                                <td colspan="4">No FAQs found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')

<script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        document.querySelectorAll('.text-editor').forEach((editorElement) => {
            ClassicEditor.create(editorElement)
                .catch(error => {
                    console.error(error);
                });
        });
    });
</script>

<script>
    $('.delete-btn').click(function () {
        
        var module = $(this).data('module');
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
                url: "{{ route('delete.module') }}",
                method: "POST",
                data: { module: module, id: id },
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

    $(document).on('click', '.editFaqs', function() {
        var id = $(this).data('id');
        var courseId = $(this).data('courseid');
        var faqId = $(this).data('faqid');
        var title = $('#title_' + id).val();
        var description = $('#description_' + id).val();

        $.ajax({
            url: "{{ route('course.update-faq', ['id' => ':id']) }}".replace(':id', id),
            type: 'GET',
            data: {
                course_id: courseId,
                faq_id: faqId,
                title: title,
                description: description
            },
            success: function(response) {
                alert('FAQ updated successfully');
                // Optionally update the UI or provide feedback to the user
            },
            error: function(xhr) {
                alert('An error occurred while updating the FAQ');
                // Optionally handle the error
            }
        });
    });
</script>
@endpush