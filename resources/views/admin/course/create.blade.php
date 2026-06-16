@extends('admin.layout.app')
@section('title', 'Add Course')
@push('style')
    <style>
        /* form > .row > [class^="col-"] {
                    margin-bottom: 16px;
                } */
        .mb {
            margin-bottom: 1.5rem;
        }
    </style>

@endpush
@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-10">
            <h2>Course</h2>
            <ol class="breadcrumb">
                <li>
                    <a href="index.html">Home</a>
                </li>
                <li>
                    <a>Course</a>
                </li>
                <li class="active">
                    <strong>Add New Course</strong>
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
                        <h5>Overview Section</h5>
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
                        <form role="form" action="{{ route('course.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <label for="">Course Title</label>
                                    <input class="form-control" placeholder="Course Title" type="text" name="title"
                                        value="{{old('title')}}">
                                    @error('title')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Short Description</label>
                                    <textarea class="form-control text-editor" id="short_description"
                                        placeholder="Add Course Short Description" name="short_description"
                                        value="{{old('short_description')}}"></textarea>
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Detailed Description</label>
                                    <textarea class="form-control text-editor" id="detailed_description"
                                        placeholder="Add Course Detailed Description" name="detailed_description"
                                        value="{{old('detailed_description')}}"></textarea>
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Offered By</label>
                                    <textarea class="form-control text-editor" id="offered_by"
                                        placeholder="Add Institute Description Offering Course" name="offered_by[institute]"
                                        value="{{old('offered_by[institute]')}}"></textarea>
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Head Office</label>
                                    <textarea class="form-control text-editor" id="head_office"
                                        placeholder="Add Institute Head Office Description" name="offered_by[head_office]"
                                        value="{{old('head_office')}}"></textarea>
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Members</label>
                                    <textarea class="form-control text-editor" id="members"
                                        placeholder="Add Institute Offering Course Description" name="offered_by[members]"
                                        value="{{old('offered_by[members]')}}"></textarea>
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Founded In</label>
                                    <textarea class="form-control text-editor" id="founded_in"
                                        placeholder="Add Institute Head Office Description" name="offered_by[founded_in]"
                                        value="{{old('offered_by[founded_in]')}}"></textarea>
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Vision & Mission</label>
                                    <textarea class="form-control text-editor" id="vision_and_mission"
                                        placeholder="Add Vision and Mision to Course" name="vision_and_mission"
                                        value="{{old('vision_and_mission')}}"></textarea>
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Overview Image</label>

                                    <!-- visible display (read-only) -->
                                    <div class="input-group">
                                        <input type="text" id="overview_img_display" class="form-control"
                                            placeholder="Choose or upload..." readonly
                                            onclick="MediaPicker.open({ idBase:'overview_img', multiple:false, accept:'image/*', fmType:'image' })">
                                        <span class="input-group-btn">
                                            <button class="btn btn-default" type="button"
                                                onclick="MediaPicker.open({ idBase:'overview_img', multiple:false, accept:'image/*', fmType:'image' })">
                                                Browse
                                            </button>
                                        </span>
                                    </div>

                                    <!-- hidden URL field (from File-Manager) -->
                                    <input type="hidden" id="overview_img_path" name="overview_img_path"
                                        value="{{ old('overview_img_path') }}">

                                    <!-- actual file input (kept for real uploads) -->
                                    <input type="file" id="overview_img_file" name="overview_img" accept="image/*"
                                        style="display:none">
                                </div>
                            </div>


                            {{-- <div class="row ">
                                <div class="col-lg-6" style="margin:0;">
                                    <p>BENIFITS SECTION</p>
                                </div>
                                <div class="col-lg-6" style="margin:0;text-align: right;">
                                    <button type="button" class="btn-primary btn btn-xs" id="addRowBtn"><i
                                            class="fa fa-plus"></i> Add</button>
                                </div>
                                <div class="col-lg-12">
                                    <hr style="margin:0;">
                                </div>
                            </div>

                            <div id="benefitsContainer">
                                <div class="row benefitRow">
                                    <div class="col-lg-6">
                                        <label class="mb-1">Title</label>
                                        <input type="text" name="benifits[0][title]" class="form-control"
                                            placeholder="Add Title to Benifit Section">
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="mb-1">Description</label>
                                        <textarea style="position: relative;" class="form-control text-editor"
                                            id="description" placeholder="Add Description to Benifit Section Title"
                                            name="benifits[0][description]"></textarea>
                                        <button style="position: absolute;right:16px;bottom:0" type="button"
                                            class="btn-danger btn btn-xs deleteRowBtn"><i class="fa fa-trash"></i>
                                            Delete</button>
                                    </div>

                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <hr style="margin:12px 0;">
                                </div>
                                <div class="col-lg-12">
                                    <label class="mb-1">Salary Description/overview</label>
                                    <textarea class="form-control text-editor" id="salary"
                                        placeholder="Add Description or details about salary on course completion"
                                        name="salary" value="{{old('salary')}}"></textarea>
                                </div>
                                <div class="col-lg-12">
                                    <label class="mb-1">Career Path</label>
                                    <textarea class="form-control text-editor" id="career_path"
                                        placeholder="Add Description or details about career path of course"
                                        name="career_path" value="{{old('career_path')}}"></textarea>
                                </div>

                            </div>
                            <div class="row ">
                                <div class="col-lg-12">
                                    <label class="mb-1">Who is eligible to register for examination?</label>
                                    <textarea class="form-control text-editor" id="eligibility"
                                        placeholder="Who is eligible to register for examination?" name="eligibility"
                                        value="{{old('eligibility')}}"></textarea>
                                </div>
                            </div>
                            <div class="row " style="margin-block: 16px;">
                                <div class="col-lg-6" style="margin:0;">
                                    <div style="display:flex; justify-content: space-between">
                                        <p>Who can do</p>
                                        <button type="button" class="btn-primary btn btn-xs"
                                            id="addColBtnInterestedToLearn"><i class="fa fa-plus"></i> Add</button>
                                    </div>
                                    <hr style="margin:0;">
                                </div>
                                <div class="col-lg-6" style="margin:0;">
                                    <div style="display:flex; justify-content: space-between">
                                        <p>Designations to pursue</p>
                                        <button type="button" class="btn-primary btn btn-xs"
                                            id="addColBtnDesignationToPursue"><i class="fa fa-plus"></i> Add</button>
                                    </div>
                                    <hr style="margin:0;">
                                </div>
                            </div>

                            <div class="row" style="display: flex;gap: 12px;margin-bottom: 16px;">
                                <div id="InterestedToLearnContainer" style="width:50%;">
                                    <div class="InterestedToLearnCol" style="position: relative; margin-bottom: 8px;">
                                        <label class="mb-1">Interested to learn</label>
                                        <input type="text" class="form-control" name="who_can_do[interested_to_learn][0]"
                                            placeholder="Interested to learn">
                                        <button type="button" class="btn btn-danger btn-xs"
                                            style="position: absolute; top: 30px; right: 5px;"
                                            onclick="removeInterestedToLearn(this)">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="DesignationToPursueContainer" style="width:50%;">
                                    <div class="DesignationToPursueCol" style="position: relative; margin-bottom: 8px;">
                                        <label class="mb-1">Designation</label>
                                        <input type="text" class="form-control" name="who_can_do[designation][0]"
                                            placeholder="Designations to pursue course">
                                        <button type="button" class="btn btn-danger btn-xs"
                                            style="position: absolute; top: 30px; right: 5px;"
                                            onclick="removeDesignationToPursue(this)">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6" style="margin:0;">
                                    <p>Course Structure 1</p>
                                </div>
                                <div class="col-lg-6" style="margin:0;text-align: right;">
                                    <button type="button" class="btn-primary btn btn-xs"
                                        id="courseStructureContainerAddBtn"><i class="fa fa-plus"></i> Part</button>
                                </div>
                                <div class="col-lg-12">
                                    <hr style="margin:0;">
                                </div>
                            </div>


                            <div id="courseStructureContainer">
                                <div class="courseStructureContainerRow" style="margin-bottom: 16px;">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label class="mb-1">Title/Part/Module</label>
                                            <input type="text" name="course[0][title]" class="form-control"
                                                placeholder="Add Title/Part to Course Structure i.e Part 1, Part 2">
                                        </div>
                                        <div class="col-lg-6">
                                            <label class="mb-1">Course Heading</label>
                                            <input type="text" name="course[0][heading]" class="form-control"
                                                placeholder="Add Course Heading i.e Financial Planning, Performance, and Analytics">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-6" style="margin-top:12px;">
                                            <p>Sub Headings</p>
                                        </div>
                                        <div class="col-lg-6" style="margin-top:12px;text-align: right;">
                                            <button type="button" class="btn-primary btn btn-xs addCourseSubHeading"
                                                data-part-index="0"><i class="fa fa-plus"></i> Sub Heading</button>
                                        </div>
                                        <div class="col-lg-12">
                                            <hr style="margin-bottom:16px;margin-top: 0;">
                                        </div>
                                    </div>

                                    <div class="courseSubHeadingContainer" data-part-index="0">
                                        <div class="row courseSubHeadingRow">
                                            <div class="col-lg-4">
                                                <label class="mb-1">Sub Heading 1</label>
                                                <input type="text" name="course[0][subheading][0][subheading]"
                                                    class="form-control"
                                                    placeholder="Add Course Sub Heading i.e 15% External Financial Reporting Decisions">
                                                <button type="button"
                                                    class="btn btn-danger btn-xs removeCourseSubHeadingRow"
                                                    style="position: absolute; top: 30px; right: 15px;">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="col-lg-8">
                                                <div class="courseSubHeadingUnitContainer">
                                                    <div class="row courseSubHeadingUnitRow">
                                                        <div class="col-lg-4">
                                                            <label class="mb-1">Unit 1</label>
                                                            <input type="text"
                                                                name="course[0][subheading][0][unit][0][title]"
                                                                class="form-control" placeholder="Add Unit Title">
                                                        </div>
                                                        <div class="col-lg-3">
                                                            <label class="mb-1">Video/Content</label>
                                                            <input type="file"
                                                                name="course[0][subheading][0][unit][0][video]"
                                                                class="form-control" placeholder="Add Unit Video/Content">
                                                        </div>
                                                        <div class="col-lg-4">
                                                            <label class="mb-1">Youtube</label>
                                                            <input type="url"
                                                                name="course[0][subheading][0][unit][0][youtube]"
                                                                class="form-control" placeholder="Add youtube video url">
                                                        </div>
                                                        <div class="col-lg-1" style="padding:0;">
                                                            <label class="mb-1">Unit</label>
                                                            <br>
                                                            <button type="button"
                                                                class="btn-primary btn btn-xs addUnitRow"><i
                                                                    class="fa fa-plus"></i></button>
                                                            <button type="button"
                                                                class="btn-danger btn btn-xs delUnitRow"><i
                                                                    class="fa fa-trash"></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row" style="margin-block: 16px;">
                                        <div class="col-lg-6" style="margin:0;">
                                            <p>EXAM FORMAT</p>
                                            <hr style="margin:0;">
                                        </div>
                                        <div class="col-lg-6" style="margin:0;">
                                            <p>EXAM DURATION</p>
                                            <hr style="margin:0;">
                                        </div>
                                    </div>

                                    <div class="row" style="margin-block: 16px;">
                                        <div class="col-lg-6" style="margin:0;">
                                            <label for="">Exam Format</label>
                                            <input type="text" name="course[0][exam_format]"
                                                value="{{old('course[0][exam_format]')}}"
                                                placeholder="Add Exam Format i.e 100 Multiple-Choice Questions and Two Essay Questions"
                                                class="form-control">
                                        </div>
                                        <div class="col-lg-6" style="margin:0;">
                                            <label for="">Exam Duration</label>
                                            <input type="text" name="course[0][exam_duration]"
                                                value="{{old('course[0][exam_duration]')}}"
                                                placeholder="Add Exam Duration i.e 4 hours ( 100 Multiple-Choice Questions (3 hours ) and Two Essay Questions (1 hour) )"
                                                class="form-control">
                                        </div>
                                    </div>

                                    <div class="row" style="margin-block: 16px;">
                                        <div class="col-lg-12" style="text-align: right;">
                                            <button type="button" class="btn btn-danger btn-xs deleteCoursePart"><i
                                                    class="fa fa-trash"></i> Remove Part</button>
                                        </div>
                                    </div>
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
                                    <textarea class="form-control text-editor" id="exam_dates" placeholder="Add Exam Dates"
                                        name="exam_dates" value="{{old('exam_dates')}}"></textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <label class="mb-1">Exam Registration Deadline</label>
                                    <textarea class="form-control text-editor" id="exam_reg_deadline"
                                        placeholder="Add Registration Deadline" name="exam_reg_deadline"
                                        value="{{old('exam_reg_deadline')}}"></textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <label class="mb-1">Passing Criteria</label>
                                    <textarea class="form-control text-editor" id="exam_passing_criteria"
                                        placeholder="Add Exam Passing Criteria" name="exam_passing_criteria"
                                        value="{{old('exam_passing_criteria')}}"></textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <label class="mb-1">Performance Standards Heading</label>
                                    <input class="form-control" id="performance_standard_heading"
                                        placeholder="Add Performance Standards Heading" name="performance_standard_heading"
                                        value="{{old('performance_standard_heading')}}"></textarea>
                                </div>
                                <div class="col-lg-12">
                                    <label class="mb-1">Performance Standards Description</label>
                                    <textarea class="form-control text-editor" id="performance_standard_description"
                                        placeholder="Add Performance Standards Description"
                                        name="performance_standard_description"
                                        value="{{old('performance_standard_description')}}"></textarea>
                                </div>
                            </div>

                            <!-- exam location -->
                            <div class="row" style="margin-block: 16px;">
                                <div class="col-lg-12" style="margin:0;">
                                    <div style="display:flex; justify-content: space-between">
                                        <p>Exam Locations</p>
                                        <div style="display:flex; justify-content: end; gap:6px">
                                            <button type="button" class="btn-primary btn btn-xs"
                                                id="ShowExamLocationTextarea">
                                                <i class="fa fa-plus"></i> Show Textarea
                                            </button>
                                            <button type="button" class="btn-primary btn btn-xs"
                                                id="addColBtnExamLocations">
                                                <i class="fa fa-plus"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                    <hr style="margin:0;">
                                </div>
                            </div>

                            <div id="ExamLocationsContainer" class="row">
                                <div class="col-lg-4 ExamLocationsCol" style="position: relative;">
                                    <label class="mb-1">Exam Locations</label>
                                    <input type="text" class="form-control" name="exam_location[]"
                                        placeholder="Add Exam Locations">
                                    <button type="button" class="btn btn-danger btn-xs"
                                        style="position: absolute; top: 30px; right: 15px;"
                                        onclick="removeExamLocation(this)">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="row" id="ExamLocationParagraphTextarea" style="display:none;">
                                <div class="col-lg-12">
                                    <label class="mb-1">Exam Location</label>
                                    <textarea class="form-control text-editor" id="exam_location_paragraph"
                                        placeholder="Add Exam locations" name="exam_location_paragraph"></textarea>
                                </div>
                            </div> --}}

                            <div class="row">
                                <div class="col-lg-12" style="margin-top: 16px; text-align:right;">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Add</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- GLOBAL: hidden file input used by whichever picker is active -->
    <input type="file" id="global_media_input" style="display:none" accept="image/*">

    <!-- Pick-source modal -->
    <div id="mediaPickSourceModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="mediaPickSourceModalLabel">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="mediaPickSourceModalLabel">Select Media</h4>
                </div>
                <div class="modal-body text-center">
                    <button type="button" class="btn btn-primary btn-block" onclick="MediaPicker.pickLocal()">Upload from
                        Computer</button>
                    <button type="button" class="btn btn-success btn-block" onclick="MediaPicker.openFileManager()">Choose
                        from File Manager</button>
                </div>
            </div>
        </div>
    </div>

    <!-- File-Manager modal -->
    <div id="mediaFileManagerModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="mediaFileManagerModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button class="close" type="button" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="mediaFileManagerModalLabel">Choose Media</h4>
                </div>
                <div class="modal-body" style="max-height: 65vh; overflow-y:auto;" id="mediaFileManagerBody">
                    <div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x"></i> Loading...</div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" onclick="MediaPicker.confirm()">Select</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script')

    <script>
        $(document).ready(function () {
            let index = 1;

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

            // interested to learn dynamic inputs starts
            let index2 = 1;

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

            window.removeInterestedToLearn = function (button) {
                $(button).closest('.InterestedToLearnCol').remove();
            };
            // interested to learn dynamic inputs ENDS

            // Designation topursue dynamic inputs STARTS
            let index3 = 1;

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

            window.removeDesignationToPursue = function (button) {
                $(button).closest('.DesignationToPursueCol').remove();
            };
            // Designation to pursue dynamic inputs ENDS

            //Exam Location
            let indexExamLocation = 2;

            $('#addColBtnExamLocations').click(function () {
                let newRowExam = `<div class="col-lg-4 ExamLocationsCol" style="position: relative;">
                                <label class="mb-1">Exam Locations ${indexExamLocation}</label>
                                <input type="text" class="form-control" name="exam_location[]" placeholder="Add Exam Locations">
                                <button type="button" class="btn btn-danger btn-xs" style="position: absolute; top: 30px; right: 15px;" onclick="removeExamLocation(this)">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>`;
                $('#ExamLocationsContainer').append(newRowExam);
                indexExamLocation++;
            });

            window.removeExamLocation = function (button) {
                $(button).closest('.ExamLocationsCol').remove();
            };

        });
    </script>
    <script>


        let coursePartCounter = 1;

        $('#courseStructureContainerAddBtn').click(function () {
            let currentPartIndex = coursePartCounter++;
            let newPart = `
                    <div class="courseStructureContainerRow" style="margin-bottom: 16px;">
                        <div class="row">
                            <div class="col-lg-12" style="margin-bottom:8px;">
                                <p>Course Structure ${currentPartIndex + 1}</p>
                                <hr style="margin:0;">
                            </div>

                            <div class="col-lg-6">
                                <label class="mb-1">Title/Part/Module</label>
                                <input type="text" name="course[${currentPartIndex}][title]" class="form-control" placeholder="Add Title/Part to Course Structure i.e Part ${currentPartIndex + 1}">
                            </div>
                            <div class="col-lg-6">
                                <label class="mb-1">Course Heading</label>
                                <input type="text" name="course[${currentPartIndex}][heading]" class="form-control" placeholder="Add Course Heading i.e Financial Planning, Performance, and Analytics">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6" style="margin:0;">
                                <p>Sub Headings</p>
                            </div>
                            <div class="col-lg-6" style="margin:0;text-align: right;">
                                <button type="button" class="btn-primary btn btn-xs addCourseSubHeading" data-part-index="${currentPartIndex}"><i class="fa fa-plus"></i> Sub Heading</button>
                            </div>
                            <div class="col-lg-12">
                                <hr style="margin-bottom:16px;margin-top: 0;">
                            </div>
                        </div>

                        <div class="courseSubHeadingContainer" data-part-index="${currentPartIndex}">
                            <div class="row courseSubHeadingRow" style="margin-top: 16px;">
                                <div class="col-lg-6">
                                    <label class="mb-1">Sub Heading 1</label>
                                    <input type="text" name="course[${currentPartIndex}][subheading][0][subheading]" class="form-control" placeholder="Add Course Sub Heading i.e 15% External Financial Reporting Decisions">
                                    <button type="button" class="btn btn-danger btn-xs removeCourseSubHeadingRow" style="position: absolute; top: 30px; right: 15px;">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                                <div class="col-lg-6">
                                    <div class="courseSubHeadingUnitContainer">
                                        <div class="row courseSubHeadingUnitRow">
                                            <div class="col-lg-5">
                                                <label class="mb-1">Unit 1</label>
                                                <input type="text" name="course[${currentPartIndex}][subheading][0][unit][0][title]" class="form-control" placeholder="Add Unit Title">
                                            </div>
                                            <div class="col-lg-5">
                                                <label class="mb-1">Video/Content</label>
                                                <input type="file" name="course[${currentPartIndex}][subheading][0][unit][0][video]" class="form-control" placeholder="Add Unit Video/Content">
                                            </div>
                                            <div class="col-lg-2">
                                                <label class="mb-1">Unit</label>
                                                <br>
                                                <button type="button" class="btn-primary btn btn-xs addUnitRow"><i class="fa fa-plus"></i></button>
                                                <button type="button" class="btn-danger btn btn-xs delUnitRow"><i class="fa fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="margin-block: 16px;">
                            <div class="col-lg-6" style="margin:0;">
                                <p>EXAM FORMAT</p>
                                <hr style="margin:0;">
                            </div>
                            <div class="col-lg-6" style="margin:0;">
                                <p>EXAM DURATION</p>
                                <hr style="margin:0;">
                            </div>
                        </div>

                        <div class="row" style="margin-block: 16px;">
                            <div class="col-lg-6" style="margin:0;">
                                <label for="">Exam Format</label>
                                <input type="text" name="course[${currentPartIndex}][exam_format]" placeholder="Add Exam Format i.e 100 Multiple-Choice Questions and Two Essay Questions" class="form-control">
                            </div>
                            <div class="col-lg-6" style="margin:0;">
                                <label for="">Exam Duration</label>
                                <input type="text" name="course[${currentPartIndex}][exam_duration]" placeholder="Add Exam Duration i.e 4 hours ( 100 Multiple-Choice Questions (3 hours ) and Two Essay Questions (1 hour) )" class="form-control">
                            </div>
                        </div>
                        <div class="row" style="margin-block: 16px;">
                            <div class="col-lg-12" style="text-align: right;">
                                <button type="button" class="btn btn-danger btn-xs deleteCoursePart"><i class="fa fa-trash"></i> Remove Part</button>
                            </div>
                        </div>
                    </div>
                `;
            $('#courseStructureContainer').append(newPart);
        });

        function updateIndexes() {
            // Update course parts
            $('#courseStructureContainer .courseStructureContainerRow').each(function (partIndex) {
                $(this).find('p').first().text(`Course Structure ${partIndex + 1}`);
                $(this).find('input[name^="course["]').each(function () {
                    let name = $(this).attr('name').replace(/\[course\]\[\d+\]/, `course[${partIndex}]`);
                    $(this).attr('name', name);
                });
                $(this).find('.addCourseSubHeading').attr('data-part-index', partIndex);
                $(this).find('.courseSubHeadingContainer').attr('data-part-index', partIndex);
            });

            // Update subheadings
            $('#courseStructureContainer .courseSubHeadingContainer').each(function () {
                let partIndex = $(this).data('part-index');
                $(this).find('.courseSubHeadingRow').each(function (subHeadingIndex) {
                    $(this).find('label').first().text(`Sub Heading ${subHeadingIndex + 1}`);
                    $(this).find('input[name^="course["]').each(function () {
                        let name = $(this).attr('name').replace(/\[subheading\]\[\d+\]/, `subheading[${subHeadingIndex}]`);
                        $(this).attr('name', name);
                    });
                });
            });

            // Update units
            $('#courseStructureContainer .courseSubHeadingUnitContainer').each(function () {
                $(this).find('.courseSubHeadingUnitRow').each(function (unitIndex) {
                    $(this).find('label').first().text(`Unit ${unitIndex + 1}`);
                    $(this).find('input[name^="course["]').each(function () {
                        let name = $(this).attr('name').replace(/\[unit\]\[\d+\]/, `unit[${unitIndex}]`);
                        $(this).attr('name', name);
                    });
                });
            });
        }

        // Handle delete button click event
        $(document).on('click', '.deleteCoursePart', function () {
            let $coursePart = $(this).closest('.courseStructureContainerRow');
            $coursePart.remove();
            updateIndexes();
        });

        // Handle add course subheading button click event
        $(document).on('click', '.addCourseSubHeading', function () {
            let partIndex = $(this).data('part-index');
            let partContainer = $(this).closest('.row').nextAll('.courseSubHeadingContainer[data-part-index="' + partIndex + '"]').first();
            let subHeadingCounter = partContainer.find('.courseSubHeadingRow').length;

            let newSubHeading = `
                    <div class="row courseSubHeadingRow" style="margin-top: 16px;">
                        <div class="col-lg-4">
                            <label class="mb-1">Sub Heading ${subHeadingCounter + 1}</label>
                            <input type="text" name="course[${partIndex}][subheading][${subHeadingCounter}][subheading]" class="form-control" placeholder="Add Course Sub Heading i.e 15% External Financial Reporting Decisions">
                            <button type="button" class="btn btn-danger btn-xs removeCourseSubHeadingRow" style="position: absolute; top: 30px; right: 15px;">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div class="col-lg-8">
                            <div class="courseSubHeadingUnitContainer">
                                <div class="row courseSubHeadingUnitRow">
                                    <div class="col-lg-4">
                                        <label class="mb-1">Unit 1</label>
                                        <input type="text" name="course[${partIndex}][subheading][${subHeadingCounter}][unit][0][title]" class="form-control" placeholder="Add Unit Title">
                                    </div>
                                    <div class="col-lg-3">
                                        <label class="mb-1">Video/Content</label>
                                        <input type="file" name="course[${partIndex}][subheading][${subHeadingCounter}][unit][0][video]" class="form-control" placeholder="Add Unit Video/Content">
                                    </div>
                                    <div class="col-lg-4">
                                        <label class="mb-1">Youtube Video URL</label>
                                        <input type="url" name="course[${partIndex}][subheading][${subHeadingCounter}][unit][0][youtube]" class="form-control" placeholder="Add youtube video url">
                                    </div>
                                    <div class="col-lg-1" style="padding:0;">
                                        <label class="mb-1">Unit</label>
                                        <br>
                                        <button type="button" class="btn-primary btn btn-xs addUnitRow"><i class="fa fa-plus"></i></button>
                                        <button type="button" class="btn-danger btn btn-xs delUnitRow"><i class="fa fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            partContainer.append(newSubHeading);
            updateIndexes();
        });

        // Handle add unit row button click event
        $(document).on('click', '.addUnitRow', function () {
            let subHeadingContainer = $(this).closest('.courseSubHeadingUnitContainer');
            let unitCounter = subHeadingContainer.find('.courseSubHeadingUnitRow').length;
            let subHeadingIndex = subHeadingContainer.closest('.courseSubHeadingRow').index();
            let partIndex = subHeadingContainer.closest('.courseSubHeadingContainer').data('part-index');

            let newUnitRow = `
                    <div class="row courseSubHeadingUnitRow" style="margin-top: 16px;">
                        <div class="col-lg-4">
                            <label class="mb-1">Unit ${unitCounter + 1}</label>
                            <input type="text" name="course[${partIndex}][subheading][${subHeadingIndex}][unit][${unitCounter}][title]" class="form-control" placeholder="Add Unit Title">
                        </div>
                        <div class="col-lg-3">
                            <label class="mb-1">Video/Content</label>
                            <input type="file" name="course[${partIndex}][subheading][${subHeadingIndex}][unit][${unitCounter}][video]" class="form-control" placeholder="Add Unit Video/Content">
                        </div>
                        <div class="col-lg-4">
                            <label class="mb-1">Youtube</label>
                            <input type="url" name="course[0][subheading][0][unit][0][youtube]" class="form-control" placeholder="Add youtube video url">
                        </div>
                        <div class="col-lg-1">
                            <label class="mb-1">Unit</label>
                            <br>
                            <button type="button" class="btn-danger btn btn-xs delUnitRow"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                `;
            subHeadingContainer.append(newUnitRow);
            updateIndexes();
        });

        // Handle delete unit row button click event
        $(document).on('click', '.delUnitRow', function () {
            let $unitRow = $(this).closest('.courseSubHeadingUnitRow');
            $unitRow.remove();
            updateIndexes();
        });

        // Handle remove subheading row button click event
        $(document).on('click', '.removeCourseSubHeadingRow', function () {
            let $subHeadingRow = $(this).closest('.courseSubHeadingRow');
            $subHeadingRow.remove();
            updateIndexes();
        });


    </script>

    <!-- exam location input format toggle -->
    <script>
        $(document).ready(function () {
            $('#ShowExamLocationTextarea').on('click', function () {
                var buttonText = $(this).text();

                if (buttonText === 'Show Textarea') {
                    // Hide the container and show the textarea
                    $('#ExamLocationsContainer').hide();
                    $('#ExamLocationParagraphTextarea').show();
                    // Change button text to "Show Input"
                    $(this).text('Show Input');
                } else {
                    // Show the container and hide the textarea
                    $('#ExamLocationsContainer').show();
                    $('#ExamLocationParagraphTextarea').hide();
                    // Change button text back to "Show Textarea"
                    $(this).text('Show Textarea');
                }
            });
        });
        window.MediaPicker = {
            active: null,                 // { idBase, multiple, accept, fmType, pathField, fileField, displayField }
            selectedUrls: [],

            open(cfg) {
                // cfg = {idBase, multiple=false, accept='image/*', fmType='image'}
                this.active = Object.assign({ multiple: false, accept: 'image/*', fmType: 'image' }, cfg || {});
                this.selectedUrls = [];

                // adjust the global hidden file input to accept wanted types
                const g = document.getElementById('global_media_input');
                g.accept = this.active.accept;
                g.multiple = !!this.active.multiple;

                // show choose-source modal
                $('#mediaPickSourceModal').modal('show');
            },

            pickLocal() {
                $('#mediaPickSourceModal').modal('hide');
                const g = document.getElementById('global_media_input');

                const onChange = (e) => {
                    const files = Array.from(e.target.files || []);
                    if (!files.length) return;

                    const fileField = document.getElementById(this.active.idBase + '_file');
                    const pathField = document.getElementById(this.active.idBase + '_path');
                    const display = document.getElementById(this.active.idBase + '_display');

                    // Prefer local upload → clear FM path
                    if (pathField) pathField.value = '';

                    // Use DataTransfer to assign files programmatically
                    const dt = new DataTransfer();
                    files.forEach(f => dt.items.add(f));
                    fileField.files = dt.files;

                    if (display) display.value = files.map(f => f.name).join(', ');

                    this.renderPreview(files.map(f => URL.createObjectURL(f)));

                    g.removeEventListener('change', onChange);
                    g.value = ''; // reset
                };

                g.addEventListener('change', onChange, { once: true });
                g.click();
            },

            openFileManager() {
                $('#mediaPickSourceModal').modal('hide');

                $('#mediaFileManagerModal').modal('show');
                $('#mediaFileManagerBody').html('<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x"></i> Loading...</div>');

                $('#mediaFileManagerBody').load("{{ route('file-manager.index') }}?type=" + encodeURIComponent(this.active.fmType), () => {
                    const syncUI = () => {
                        // highlight selected thumbnails
                        $('#mediaFileManagerBody .file-thumbnail').each((_, el) => {
                            const url = $(el).data('url');
                            $(el).toggleClass('selected', this.selectedUrls.includes(url));
                        });
                        // check the checkboxes
                        $('#mediaFileManagerBody .image-selector').each((_, cb) => {
                            const url = cb.value;
                            cb.checked = this.selectedUrls.includes(url);
                        });
                    };

                    // Thumbnail click
                    $('#mediaFileManagerBody').off('click.thumb')
                        .on('click.thumb', '.file-thumbnail', (ev) => {
                            const url = $(ev.currentTarget).data('url');
                            if (this.active.multiple) {
                                const i = this.selectedUrls.indexOf(url);
                                if (i >= 0) this.selectedUrls.splice(i, 1); else this.selectedUrls.push(url);
                            } else {
                                this.selectedUrls = [url];
                            }
                            syncUI();
                        });

                    // Checkbox change
                    $('#mediaFileManagerBody').off('change.cb')
                        .on('change.cb', '.image-selector', (ev) => {
                            const url = ev.currentTarget.value;
                            if (this.active.multiple) {
                                if (ev.currentTarget.checked) {
                                    if (!this.selectedUrls.includes(url)) this.selectedUrls.push(url);
                                } else {
                                    this.selectedUrls = this.selectedUrls.filter(u => u !== url);
                                }
                            } else {
                                this.selectedUrls = [url];
                                // uncheck other boxes
                                $('#mediaFileManagerBody .image-selector').not(ev.currentTarget).prop('checked', false);
                            }
                            syncUI();
                        });

                    // Initial sync (in case you want to preselect)
                    syncUI();
                });
            },

            confirm() {
                // put selection into *_path (string or CSV) and clear *_file
                const pathField = document.getElementById(this.active.idBase + '_path');
                const fileField = document.getElementById(this.active.idBase + '_file');
                const display = document.getElementById(this.active.idBase + '_display');

                if (pathField) pathField.value = this.active.multiple ? this.selectedUrls.join(',') : (this.selectedUrls[0] || '');
                if (fileField) fileField.value = ''; // clear any previous local selection
                if (display) display.value = pathField.value;

                this.renderPreview(this.selectedUrls);

                $('#mediaFileManagerModal').modal('hide');
            },

            renderPreview(urls) {
                const wrap = document.getElementById(this.active.idBase + '_preview');
                if (!wrap) return;
                wrap.innerHTML = '';
                (urls || []).forEach(u => {
                    const img = document.createElement('img');
                    img.src = u;
                    img.className = 'img img-bordered picker-preview';
                    img.style.maxHeight = '60px';
                    img.style.marginRight = '6px';
                    wrap.appendChild(img);
                });
            }
        };
    </script>
@endpush