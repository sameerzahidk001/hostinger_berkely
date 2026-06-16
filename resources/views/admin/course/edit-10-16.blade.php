@extends('admin.layout.app')
@section('title', 'Edit Course')
@push('style')
<style>
    form > .row{
        margin-bottom: 16px;
    }
</style>
    <link href="{{ asset('admin/css/plugins/summernote/summernote.css') }}" rel="stylesheet">
    <link href="{{ asset('admin/css/plugins/summernote/summernote-bs3.css') }}" rel="stylesheet">
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $course->title }}</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ route('admin.home') }}">Home</a>
            </li>
            <li>
                <a>Course</a>
            </li>
            <li>
                <a>Edit</a>
            </li>
            <li class="active">
                <strong>{{ $course->title }}</strong>
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
                    <h5>Edit {{ $course->title }} Details</h5>
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
                    <form role="form" action="{{ route('course.update', $course->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-lg-6">
                                <label for="">Course Title</label>
                                <input class="form-control" placeholder="Course Title" type="text" name="title" value="{{ old('title', $course->title) }}">
                            </div>
                            <div class="col-lg-6">
                                <label class="mb-1">Slug</label>
                                <input type="text" class="form-control" name="slug" value="{{ old('slug', $course->slug) }}">
                            </div>
                            
                            <div class="col-lg-6">
                                <label class="mb-1">Thumbnail</label>
                                <input type="file" class="form-control" name="thumbnail" >
                                @if($course->thumbnail)
                                <a href="{{ asset($course->thumbnail) }}" target="_blank">Img</a>
                                @endif
                            </div>
                            <div class="col-lg-12" style="max-height:200px; overflow-y: scroll;">
                                <label class="mb-1">Category</label>
                                <ul class="todo-list m-t small-list ui-sortable">
                                    @foreach($categories as $key => $category)
                                        <li>
                                            <input type="checkbox" id="input-{{ $key }}" name="categories[]" value="{{ $category->id }}"
                                                {{ $course->categories->contains('id', $category->id) ? 'checked' : '' }}>
                                            <label class="m-l-xs" for="input-{{ $key }}"> {{ $category->name }}</label>
                                        </li>
                                    @endforeach
                                </ul>

                            </div>
                            <div class="col-lg-12">
                                <label class="mb-1">Registration Iframe</label>
                                <input type="text" class="form-control" name="reg_iframe" value="{{ old('reg_iframe', $course->reg_iframe) }}">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <label class="mb-1">Short Description</label>
                                <textarea name="short_description" class="form-control short_description text-editor" rows="2" value="{{old('short_description')}}">{{ $course->short_description }}</textarea>
                            </div>
                            <div class="col-lg-6">
                                <label class="mb-1">Detailed Description</label>
                                <textarea name="description" class="form-control description text-editor" rows="2" value="{{old('description')}}">{{ $course->description }}</textarea>
                            </div>
                        </div>
                        <div class="row ">
                            <div class="col-lg-12">
                                <p>OVERVIEW SECTION</p>
                                <hr style="margin:0;">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <label class="mb-1">Offered By</label>
                                <textarea name="offered_by[institute]" class="form-control  text-editor"  rows="2" value="{{old('offered_by[institute]')}}">{{ optional($course->offered_by)['institute'] }}</textarea>
                            </div>
                            <div class="col-lg-6">
                                <label class="mb-1">Head Office</label>
                                <textarea name="offered_by[head_office]" id="head_office" class="form-control text-editor" placeholder="Detailed Description" rows="2">{{ old('offered_by.head_office', optional($course->offered_by)['head_office']) }}</textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <label class="mb-1">Members</label>
                                <textarea name="offered_by[members]" class="form-control text-editor"  rows="2" value="{{old('offered_by[members]')}}">{{ optional($course->offered_by)['members'] }}</textarea>
                            </div>
                            <div class="col-lg-6">
                                <label class="mb-1">Founded In</label>
                                <textarea name="offered_by[founded_in]" class="form-control text-editor"  rows="2" value="{{old('offered_by[founded_in]')}}">{{ optional($course->offered_by)['founded_in'] }}</textarea>
                            </div>
                        </div>
                        <div class="row ">
                            <div class="col-lg-6">
                                <label class="mb-1">Vision & Mission</label>
                                <textarea class="form-control text-editor" id="vision_and_mission" name="vision_and_mission" value="{{old('vision_and_mission')}}">{{ $course->vision_and_mission }}</textarea>
                            </div>
                            <div class="col-lg-6">
                                <label class="mb-1">Overview Image</label>
                                @if( $course->overview_img )
                                    <br>
                                    <a href="{{ asset( $course->overview_img) }}" target="_blank">Img</a>
                                @endif
                                
                                <input type="file" class="form-control" id="overview_img" name="overview_img">
                            </div>
                        </div>
                        <div class="row ">
                            <div class="col-lg-6" style="margin:0;">
                                <p>BENIFITS SECTION</p>
                            </div>
                            <div class="col-lg-6" style="margin:0;text-align: right;">
                                <button type="button" class="btn-primary btn btn-xs" id="addRowBtn"><i class="fa fa-plus"></i> Add</button>
                            </div>
                            <div class="col-lg-12">
                                <hr style="margin:0;">
                            </div>
                        </div>
                        <div id="benefitsContainer">
                            @foreach($course->benifits ?? [] as $key => $benifit)
                            <div class="row benefitRow">
                                <div class="col-lg-6">
                                    <label class="mb-1">Title</label>
                                    <input type="text" name="benifits[{{$key}}][title]" class="form-control" value="{{ $benifit['title'] }}">
                                </div>
                                <div class="col-lg-6">
                                    <label class="mb-1">Description</label>
                                    <textarea style="position: relative;" class="form-control text-editor" id="description" name="benifits[{{$key}}][description]">{{ $benifit['description'] }}</textarea>
                                    <button style="position: absolute;right:16px;bottom:0" type="button" class="btn-danger btn btn-xs deleteRowBtn" ><i class="fa fa-trash"></i> Delete</button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <hr style="margin:12px 0;">
                            </div>
                            <div class="col-lg-12">
                                <label class="mb-1">Salary Description/overview</label>
                                <textarea class="form-control text-editor" id="salary" value="{{old('salary')}}" name="salary">{{ $course->salary }}</textarea>
                            </div>
                            <div class="col-lg-12">
                                <label class="mb-1">Career Path</label>
                                <textarea class="form-control text-editor" id="career_path" value="{{old('career_path')}}" name="career_path">{{ $course->career_path }}</textarea>
                            </div>
                        </div>
                        <div class="row ">
                            <div class="col-lg-12">
                                <label class="mb-1">Who is eligible to register for examination?</label>
                                <textarea class="form-control text-editor" id="eligibility"  name="eligibility" value="{{old('eligibility')}}">{{ $course->eligibility }}</textarea>
                            </div>
                        </div>
                        <div class="row " style="margin-block: 16px;">
                            <div class="col-lg-6" style="margin:0;">
                                <div style="display:flex; justify-content: space-between">
                                    <p>Who can do</p>
                                    <button type="button" class="btn-primary btn btn-xs" id="addColBtnInterestedToLearn"><i class="fa fa-plus"></i> Add</button>
                                </div>
                                <hr style="margin:0;">
                            </div>
                            <div class="col-lg-6" style="margin:0;">
                                <div style="display:flex; justify-content: space-between">
                                    <p>Designations to pursue</p>
                                    <button type="button" class="btn-primary btn btn-xs" id="addColBtnDesignationToPursue"><i class="fa fa-plus"></i> Add</button>
                                </div>
                                <hr style="margin:0;">
                            </div>
                        </div>
                        <div class="row" style="display: flex;gap: 12px;margin-bottom: 16px;">
                            <div id="InterestedToLearnContainer" style="width:50%;">
                                @foreach($course->who_can_do['interested_to_learn'] ?? [] as $index => $data)
                                    <div class="InterestedToLearnCol" style="position: relative; margin-bottom: 8px;">
                                        <label class="mb-1">Interested to learn</label>
                                        <input type="text" class="form-control" name="who_can_do[interested_to_learn][{{ $index }}]" value="{{ $data }}">
                                        <button type="button" class="btn btn-danger btn-xs" style="position: absolute; top: 30px; right: 5px;" onclick="removeInterestedToLearn(this)">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <div id="DesignationToPursueContainer" style="width:50%;">
                                @foreach(@$course->who_can_do['designation'] ?? [] as $index => $data)
                                <div class="DesignationToPursueCol" style="position: relative; margin-bottom: 8px;">
                                    <label class="mb-1">Designation</label>
                                    <input type="text" class="form-control" name="who_can_do[designation][{{ $index }}]" value="{{ $data }}">
                                    <button type="button" class="btn btn-danger btn-xs" style="position: absolute; top: 30px; right: 5px;" onclick="removeDesignationToPursue(this)">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="row" style="margin-block: 16px;">
                            <div class="col-lg-12" style="margin:0;">
                                <p>WHAT ARE THE EXAM INFORMATION?</p>
                                <hr style="margin:0;">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <label class="mb-1">Exam Dates</label>
                                <textarea class="form-control text-editor" id="exam_dates" placeholder="Add Exam Dates" name="exam_dates" value="{{old('exam_dates')}}">{{ $course->exam_dates }}</textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <label class="mb-1">Exam Registration Deadline</label>
                                <textarea class="form-control text-editor" id="exam_reg_deadline" placeholder="Add Registration Deadline" name="exam_reg_deadline" value="{{old('exam_reg_deadline')}}">{{ $course->exam_reg_deadline }}</textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <label class="mb-1">Passing Criteria</label>
                                <textarea class="form-control text-editor" id="exam_passing_criteria" placeholder="Add Exam Passing Criteria" name="exam_passing_criteria" value="{{old('exam_passing_criteria')}}">{{ $course->exam_passing_criteria }}</textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <label class="mb-1">Performance Standards Heading</label>
                                <input class="form-control" id="performance_standard_heading" placeholder="Add Performance Standards Heading" name="performance_standard_heading" value="{{ old('performance_standard_heading', $course->performance_standard_heading ) }}"/>
                            </div>
                            <div class="col-lg-12">
                                <label class="mb-1">Performance Standards Description</label>
                                <textarea class="form-control text-editor" id="performance_standard_description" placeholder="Add Performance Standards Description" name="performance_standard_description" value="{{old('performance_standard_description')}}">{{ $course->performance_standard_description }}</textarea>
                            </div>
                        </div>

                        <!-- exam location -->
                        <div class="row" style="margin-block: 16px;">
                            <div class="col-lg-12" style="margin:0;">
                                <div style="display:flex; justify-content: space-between">
                                    <p>Exam Locations</p>
                                    <button type="button" class="btn-primary btn btn-xs" id="addColBtnExamLocations">
                                        <i class="fa fa-plus"></i> Add
                                    </button>
                                </div>
                                <hr style="margin:0;">
                            </div>
                        </div>

                        <div id="ExamLocationsContainer" class="row">
                            @foreach($course->exam_location ?? [] as $index => $data)
                            <div class="col-lg-4 ExamLocationsCol" style="position: relative;">
                                <label class="mb-1">Exam Locations {{ $index + 1 }}</label>
                                <input type="text" class="form-control" name="exam_location[]" value="{{ $data}}">
                                <button type="button" class="btn btn-danger btn-xs" style="position: absolute; top: 30px; right: 15px;" onclick="removeExamLocation(this)">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>

                        <div class="row" id="ExamLocationParagraphTextarea">
                            <div class="col-lg-12">
                                <label class="mb-1">Exam Location</label>
                                <textarea class="form-control text-editor" id="exam_location_paragraph" value="{{old('exam_location_paragraph')}}" name="exam_location_paragraph">{{ $course->exam_location_paragraph }}</textarea>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-12"> 
                                <button class="btn btn-primary " type="submit"><i class="fa fa-check"></i>&nbsp;Update</button>
                                <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                                <!-- <button type="submit" class="btn btn-success btn-with-icon float-right"><i class="far fa-check-circle"></i> Submit</button>
                                <button type="reset" class="btn btn-danger btn-with-icon mr-2 float-right"><i class="typcn typcn-archive"></i> Reset</button> -->
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
$(document).ready(function () {

    // let index = 1;
    let index = $('.benefitRow').length;
    $('#addRowBtn').click(function () {
        let newRow = `<div class="row benefitRow" style="margin-top: 16px;">
            <div class="col-lg-6">
                <label class="mb-1">Title</label>
                <input type="text" name="benifits[${index}][title]" class="form-control" placeholder="Add Title to Benefit Section">
            </div>
            <div class="col-lg-6">
                <label class="mb-1">Description</label>
                <textarea style="position: relative;" class="form-control text-editor" id="description" placeholder="Add Description to Benefit Section Title" name="benifits[${index}][description]"></textarea>
                <button style="position: absolute;right:16px;bottom:0" type="button" class="btn-danger btn btn-xs deleteRowBtn"><i class="fa fa-trash"></i> Delete</button>
            </div>
        </div>`;
        $('#benefitsContainer').append(newRow);
        index++;
    });

    $(document).on('click', '.deleteRowBtn', function () {
        $(this).closest('.benefitRow').remove();
        // Update indexes
        index = 0;
        $('.benefitRow').each(function () {
            $(this).find('input[name^="benefits"]').attr('name', `benefits[${index}][title]`);
            $(this).find('textarea[name^="benefits"]').attr('name', `benefits[${index}][description]`);
            index++;
        });
    });

    //Exam Location
    $('#addColBtnExamLocations').click(function () {
        let newRowExam = `<div class="col-lg-4 ExamLocationsCol" style="position: relative;">
            <label class="mb-1">Exam Locations</label>
            <input type="text" class="form-control" name="exam_location[]" placeholder="Add Exam Locations">
            <button type="button" class="btn btn-danger btn-xs" style="position: absolute; top: 30px; right: 15px;" onclick="removeExamLocation(this)">
                <i class="fa fa-times"></i>
            </button>
        </div>`;
        $('#ExamLocationsContainer').append(newRowExam);
        indexExamLocation++;
    });

    window.removeExamLocation = function(button) {
        $(button).closest('.ExamLocationsCol').remove();
    };

    // interested to learn dynamic inputs starts
    // let index2 = 1;
    let index2 = $('.InterestedToLearnCol').length;
    $('#addColBtnInterestedToLearn').click(function () {
        let newRow2 = `<div class="InterestedToLearnCol" style="position: relative; margin-bottom: 8px;">
            <label class="mb-1">Interested to learn</label>
            <input type="text" class="form-control" name="who_can_do[interested_to_learn][${index2}]" placeholder="Interested to learn">
            <button type="button" class="btn btn-danger btn-xs" style="position: absolute; top: 30px; right: 5px;" onclick="removeInterestedToLearn(this)">
                <i class="fa fa-times"></i>
            </button>
        </div>`;
        $('#InterestedToLearnContainer').append(newRow2);
        index2++;
    });

    window.removeInterestedToLearn = function(button) {
        $(button).closest('.InterestedToLearnCol').remove();
    };
    // interested to learn dynamic inputs ENDS

    // Designation topursue dynamic inputs STARTS
    // let index3 = 1;
    let index3 = $('.DesignationToPursueCol').length;
    $('#addColBtnDesignationToPursue').click(function () {
        let newRow3 = `<div class="DesignationToPursueCol" style="position: relative; margin-bottom: 8px;">
            <label class="mb-1">Designation</label>
            <input type="text" class="form-control" name="who_can_do[designation][${index3}]" placeholder="Designations to pursue course">
            <button type="button" class="btn btn-danger btn-xs" style="position: absolute; top: 30px; right: 5px;" onclick="removeDesignationToPursue(this)">
                <i class="fa fa-times"></i>
            </button>
        </div>`;
        $('#DesignationToPursueContainer').append(newRow3);
        index3++;
    });

    window.removeDesignationToPursue = function(button) {
        $(button).closest('.DesignationToPursueCol').remove();
    };
    // Designation to pursue dynamic inputs ENDS

});
</script>
@endpush