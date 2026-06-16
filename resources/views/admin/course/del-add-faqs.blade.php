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
                            <div class="col-lg-6">
                                <label class="mb-1">FAQ Title</label>
                                <textarea name="title" id="" rows="2" class="form-control" class placeholder="Add FAQ title"></textarea>
                            </div>
                            <div class="col-lg-6">
                                <label class="mb-1">Description</label>
                                <textarea name="description" id="" rows="2" class="form-control" placeholder="Add FAQ description"></textarea>
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

<!-- <div class="row">
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
                                <th>Faq</th>
                                <th>Description</th>

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
                                        <button type="button" class="btn-primary btn btn-xs editFaqs" data-id="{{ $data->id }}" data-courseid="{{ $course->id }}" data-faqid="{{ $data->id }}"><i class="fa fa-edit"></i> Edit</button>
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
</div> -->
@endsection

@push('script')

@endpush