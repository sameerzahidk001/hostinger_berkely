@extends('admin.layout.app')
@section('title', 'Add Course')
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
        <h2>Labels</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            <li>
                <a>Labels</a>
            </li>
            <li class="active">
                <strong>Add Labels</strong>
            </li>
        </ol>
    </div>
    <div class="col-lg-2">

    </div>
</div>

<div class="row">
    <div class="wrapper wrapper-content">
        <div class="col-lg-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Add Labels</h5>
                    <div class="ibox-tools">
                        <!-- <a data-toggle="modal" href="#AddSyllabusModal" data-item-id="">
                            <i class="fa fa-chevron-circle-right"></i>
                        </a> -->
                        <a class="collapse-link">
                            <i class="fa fa-chevron-up"></i>
                        </a>

                        <a class="close-link">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                </div>
                <div class="ibox-content">
                    <form role="form" action="{{ route('course-labels.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                        <div class="col-lg-12 mb">
                                <label for="">Courses</label>
                                <select name="course_id" id="" class="form-control">
                                    <option value="">--Select Course --</option>
                                    @foreach($courses as $key => $course)
                                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Overview Section Heading</label>
                                <input class="form-control" placeholder="add overview section heading" type="text" name="overview" value="{{old('overview')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Offered By SubHeading</label>
                                <input class="form-control" placeholder="add Offered By SubHeading" type="text" name="offered_by" value="{{old('offered_by')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Head Office SubHeading</label>
                                <input class="form-control" placeholder="add Head Office SubHeading" type="text" name="head_office" value="{{old('head_office')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Members SubHeading</label>
                                <input class="form-control" placeholder="add Members SubHeading" type="text" name="members" value="{{old('members')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Founded In SubHeading</label>
                                <input class="form-control" placeholder="add Founded In SubHeading" type="text" name="founded_in" value="{{old('founded_in')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Vision and Mission SubHeading</label>
                                <input class="form-control" placeholder="add Vission and Mission SubHeading" type="text" name="vission_mission" value="{{old('vission_mission')}}">
                            </div>
                            <div class="col-lg-12 mb">
                                <label for="">Eligibility Heading</label>
                                <input class="form-control" placeholder="add Eligibility Heading" type="text" name="eligibility" value="{{old('eligibility')}}">
                            </div>
                            <!-- <div class="col-lg-6 mb">
                                <label for="">Eligibity SubHeading</label>
                                <input class="form-control" placeholder="add Eligibility SubHeading" type="text" name="eligibity_sub_heading" value="{{old('eligibity_sub_heading')}}">
                            </div> -->
                            <div class="col-lg-6 mb">
                                <label for="">Who Can Do Heading</label>
                                <input class="form-control" placeholder="add Who Can Do Heading" type="text" name="who_can_do" value="{{old('who_can_do')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Who Can Do SubHeading 1</label>
                                <input class="form-control" placeholder="add Eligibility SubHeading 1" type="text" name="who_can_do_subh01" value="{{old('who_can_do_subh01')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Who Can Do SubHeading 2</label>
                                <input class="form-control" placeholder="add Eligibility SubHeading 2" type="text" name="who_can_do_subh02" value="{{old('who_can_do_subh02')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Who Can Do Section Image/Video</label>
                                <input class="form-control" placeholder="add Who Can Do Section Image" type="file" name="who_can_do_img" value="{{old('who_can_do_img')}}">
                            </div>
                            <div class="col-lg-12 mb">
                                <label for="">Course Structure Heading</label>
                                <input class="form-control" placeholder="add Course Structure Heading" type="text" name="course_structure" value="{{old('course_structure')}}">
                            </div>
                            
                            <div class="col-lg-12 mb">
                                <label for="">Lecture Plan Heading</label>
                                <input class="form-control" placeholder="add Lecture Plan Heading" type="text" name="lecture_plan" value="{{old('lecture_plan')}}">
                            </div>
                            <div class="col-lg-12 mb">
                                <label for="">Learning Methodology Heading</label>
                                <input class="form-control" placeholder="add Learning Methodology Heading" type="text" name="learning_methodology" value="{{old('learning_methodology')}}">
                            </div>
                            <div class="col-lg-12 mb">
                                <label class="mb-1">Learning Methodology Overview</label>
                                <textarea class="form-control text-editor" id="learning_methodology_overview" placeholder="Add Learning Methodology Overview" name="learning_methodology_overview" value="{{old('learning_methodology_overview')}}"></textarea>
                            </div>
                            <div class="col-lg-4 mb">
                                <label for="">Lectures Box Heading</label>
                                <input class="form-control" placeholder="add Lectures Heading" type="text" name="lectures" value="{{old('lectures')}}">
                            </div>
                            <div class="col-lg-4 mb">
                                <label for="">Lecture Box Description</label>
                                <input class="form-control" placeholder="add Lecture Box Description" type="text" name="lectures_des" value="{{old('lectures_des')}}">
                            </div>
                            <div class="col-lg-4 mb">
                                <label for="">Lecture Box Image/Video</label>
                                <input class="form-control" placeholder="add Lecture Box Image" type="file" name="lectures_img" value="{{old('lectures_img')}}">
                            </div>
                            <div class="col-lg-4 mb">
                                <label for="">Practice Session Box Heading</label>
                                <input class="form-control" placeholder="add Practice Session Heading" type="text" name="practice_session" value="{{old('practice_session')}}">
                            </div>
                            <div class="col-lg-4 mb">
                                <label for="">Practice Session Description</label>
                                <input class="form-control" placeholder="add Practice Session Description" type="text" name="practice_session_des" value="{{old('practice_session_des')}}">
                            </div>
                            <div class="col-lg-4 mb">
                                <label for="">Practice Session Image/Video</label>
                                <input class="form-control" placeholder="add Practice Session Image" type="file" name="practice_session_img" value="{{old('practice_session_img')}}">
                            </div>
                            <div class="col-lg-4 mb">
                                <label for="">Mock Examination Box Heading</label>
                                <input class="form-control" placeholder="add Mock Examination Box Heading" type="text" name="mock_examination" value="{{old('mock_examination')}}">
                            </div>
                            <div class="col-lg-4 mb">
                                <label for="">Mock Examination Box Description</label>
                                <input class="form-control" placeholder="add Mock Examination Box Description" type="text" name="mock_examination_description" value="{{old('mock_examination_description')}}">
                            </div>
                            <div class="col-lg-4 mb">
                                <label for="">Mock Examination Image</label>
                                <input class="form-control" placeholder="add Mock Examination Box Image" type="file" name="mock_examination_img" value="{{old('mock_examination_img')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Exam Information Heading</label>
                                <input class="form-control" placeholder="add Exam Information Heading" type="text" name="exam_information" value="{{old('exam_information')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Exam Format Duration SubHeading</label>
                                <input class="form-control" placeholder="add Exam Format Duration SubHeading" type="text" name="exam_format_duration" value="{{old('exam_format_duration')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Exam Format Duration SubHeading 01</label>
                                <input class="form-control" placeholder="add Exam Format Duration SubHeading 01" type="text" name="exam_format_duration_01" value="{{old('exam_format_duration_01')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Exam Format Duration SubHeading 02</label>
                                <input class="form-control" placeholder="add Exam Format Duration SubHeading 02" type="text" name="exam_format_duration_02" value="{{old('exam_format_duration_02')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Exam Format Duration SubHeading 03</label>
                                <input class="form-control" placeholder="add Exam Format Duration SubHeading 03" type="text" name="exam_format_duration_02" value="{{old('exam_format_duration_02')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Exam Dates SubHeading</label>
                                <input class="form-control" placeholder="add Exam Format Duration SubHeading 03" type="text" name="exam_format_duration_02" value="{{old('exam_format_duration_02')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Exam Information Section Img</label>
                                <input class="form-control" type="file" name="exam_information_section_img" value="{{old('exam_information_section_img')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Exam Dates SubHeading</label>
                                <input class="form-control" placeholder="add Exam Date SubHeading" type="text" name="exam_dates" value="{{old('exam_dates')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Exam Location SubHeading</label>
                                <input class="form-control" placeholder="add Exam Location SubHeading" type="text" name="exam_location" value="{{old('exam_location')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Passing Criteria SubHeading</label>
                                <input class="form-control" placeholder="add Passing Criteria SubHeading" type="text" name="passing_criteria" value="{{old('passing_criteria')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Fee Information Heading</label>
                                <input class="form-control" placeholder="add Fee Information Heading" type="text" name="fee_information" value="{{old('fee_information')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Learner Stories Heading</label>
                                <input class="form-control" placeholder="add Learner Stories Heading" type="text" name="learner_stories" value="{{old('learner_stories')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Career Path Heading</label>
                                <input class="form-control" placeholder="add Career Path Heading" type="text" name="career_path_heading" value="{{old('career_path_heading')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Career Path Section Img</label>
                                <input class="form-control" placeholder="Career Path Section Img" type="file" name="career_path_section_img" value="{{old('career_path_section_img')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">What You Earn Heading</label>
                                <input class="form-control" placeholder="add What You Earn Heading" type="text" name="what_you_earn" value="{{old('what_you_earn')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">What You Earn Description</label>
                                <input class="form-control" placeholder="add What You Earn Description" type="text" name="what_you_earn_des" value="{{old('what_you_earn_des')}}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">What You Earn Section Image</label>
                                <input class="form-control"  type="file" name="what_you_earn_img" value="{{old('what_you_earn_img')}}">
                            </div>
                            
                            
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;"> 
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
@endpush