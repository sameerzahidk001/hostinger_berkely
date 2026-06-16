@extends('admin.layout.app')
@section('title', 'List Course')
@push('style')

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
                <strong>List</strong>
            </li>
        </ol>
    </div>
    <div class="col-lg-2">

    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                <div class="ibox-title">
                    <h5>List Course</h5>
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

                    <form role="form" action="{{ route('course.list-course-in-category') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-lg-12">
                                <label for="">Course</label>
                                <select name="course_id" class="form-control" id="">
                                    @foreach($courses as $key => $course)
                                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-12" style="max-height:400px; overflow-y: scroll;">
                                <ul class="todo-list m-t small-list ui-sortable">
                                    @foreach($categories as $key => $data)
                                        <li>
                                            <input type="checkbox" id="input-{{ $key }}" name="categories[]" value="{{ $data->id }}"
                                                {{ $course->categories->contains('id', $data->id) ? 'checked' : '' }}>
                                            <label class="m-l-xs" for="input-{{ $key }}"> {{ $data->name }}</label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            
                        </div>
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;text-align:right;"> 
                                <button class="btn btn-primary " type="submit"><i class="fa fa-check"></i>&nbsp;Submit</button>
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