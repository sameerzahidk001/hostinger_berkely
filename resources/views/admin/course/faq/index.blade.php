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
            <div class="ibox float-e-margins" style="margin-bottom:0;">
                <div class="ibox-title">
                    <h5>Label & Section Status</h5>
                    <div class="ibox-tools">
                        
                        <a class="collapse-link">
                            <i class="fa fa-chevron-down"></i>
                        </a>

                        <a class="close-link">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                </div>
                
                <div class="ibox-content" style="display:none;">
                    <form action="{{ route('course.faq-section-update', ['id' => $course->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                            <div class="row">

                                <div class="col-lg-12 mb">
                                    
                                    <label class="mb-1">Status</label>
                                    <select name="faq_section" class="form-control">
                                        <option value="1" {{ $course->faq_section == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ $course->faq_section == 0 ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                </div>

                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Section Heading Label (FAQ: Course name)</label>
                                    <input type="text"name="label[faq_heading]" value="{{ old('label[faq_heading]', $course->dynamicLabel?->faq_heading) }}" class="form-control" placeholder="add section heading lable">
                                </div>
                                
                                
                            </div>
                            
                        
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 0px;text-align:right;"> 
                                <button class="btn btn-primary " type="submit"><i class="fa fa-check"></i>&nbsp;Submit</button>
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
                                <textarea name="title" id="" rows="1" class="form-control" class placeholder="Add FAQ Question">{{ old('title') }}</textarea>
                            </div>
                            <div class="col-lg-12">
                                <label class="mb-1">Answer</label>
                                <textarea name="description" id="" rows="2" class="form-control text-editor" placeholder="Add FAQ Answer">{{ old('description') }}</textarea>
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
                                
                                <th style="width: 35%;">Question</th>
                                <th>Answer</th>

                                <th style="width: 18%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($course->courseFaq as $index => $data)
                            <tr id="row_{{$data->id}}">
                                
                                <td>
                                    <textarea name="title" id="title_{{$data->id}}" rows="4" class="form-control" readonly>{{ $data->title }}</textarea>
                                </td>
                                <td>
                                    <textarea name="description" id="description_{{$data->id}}" rows="4" class="form-control" readonly>{{ $data->short_description }}</textarea>
                                    
                                </td>
                                <td style="vertical-align: top;">
                                    <div class="btn-group">
                                        <a href="{{ route('course.edit-faq', ['course_id' => $course->id, 'faq_id' => $data->id]) }}" class="btn-primary btn btn-xs editFaqs"><i class="fa fa-edit"></i> Edit</a>
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

<!-- <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        document.querySelectorAll('.text-editor').forEach((editorElement) => {
            ClassicEditor.create(editorElement)
                .catch(error => {
                    console.error(error);
                });
        });
    });
</script> -->

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
</script>
@endpush