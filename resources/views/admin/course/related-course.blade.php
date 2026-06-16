@extends('admin.layout.app')
@section('title', 'Related Courses')
@push('style')
    <style>
        .mb{
            margin-bottom: 1.5rem;
        }
    </style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Courses</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            <li>
                <a>Courses</a>
            </li>
            <li class="active">
                <strong>Assign Related Courses</strong>
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
                    <h5>Related Courses Label & Section Status</h5>
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
                    <form action="{{ route('course.related-courses-section-update', ['id' => $course->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                            <div class="row">

                                <div class="col-lg-12 mb">
                                    
                                    <label class="mb-1">Status</label>
                                    <select name="related_courses_section" class="form-control">
                                        <option value="1" {{ $course->related_courses_section == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ $course->related_courses_section == 0 ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                </div>

                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Section Heading Label (Related courses)</label>
                                    <input type="text"name="label[related_courses]" value="{{ old('label[related_courses]', $course->dynamicLabel?->related_courses) }}" class="form-control" placeholder="add section heading lable">
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

<div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                <div class="ibox-title">
                    <h5>Assign Related Courses</h5>
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

                    <form role="form" action="{{ route('course.assign-course') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-lg-12">
                                <label for="">Course</label>
                                <input type="text" class="form-control" value="{{ $course->title }}">
                                <input type="hidden" name="course_id" class="form-control" value="{{ $course->id }}">
                            </div>
                            <div class="col-lg-12" style="max-height:250px; overflow-y: scroll;">
                                <ul class="todo-list m-t small-list ui-sortable">
                                    @foreach($related_courses as $key => $data)
                                        <li>
                                            <input type="checkbox" id="input-{{ $key }}" name="related_courses[]" value="{{ $data->id }}"
                                                {{ $course->relatedCourses->contains('id', $data->id) ? 'checked' : '' }}>
                                            <label class="m-l-xs" for="input-{{ $key }}"> {{ $data->title }}</label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            
                        </div>
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;text-align:right;"> 
                                <button class="btn btn-primary " type="submit"><i class="fa fa-check"></i>&nbsp;Update</button>
                                <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')


@endpush