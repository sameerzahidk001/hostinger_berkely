@extends('admin.layout.app')
@section('title', 'Lecture Plan')
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
        <h2>Lecture Plan</h2>
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
                <a>Lecture Plan</a>
            </li>
            
        </ol>
    </div>
    <div class="col-lg-2">

    </div>
</div>

<!-- <div class="row">
    <div class="wrapper wrapper-content">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom:0;">
                <div class="ibox-title">
                    <h5>Lecture Plan Section Status  </h5>
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
                    <form action="{{ route('course.course-structure-overview', ['id' => $course->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row">
                            <div class="col-lg-12 mb">
                                
                                <label class="mb-1">Status</label>
                                <select name="lecture_plan_section" class="form-control" id="lecture_plan_{{ $course->id }}" onchange="updateLecturePlanStatus(this.value, {{ $course->id }})">
                                    <option value="1" {{ $course->lecture_plan_section == 1 ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ $course->lecture_plan_section == 0 ? 'selected' : '' }}>Disabled</option>
                                </select>
                            </div>
                            
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> -->

<div class="row">
    <div class="wrapper wrapper-content">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom:0;">
                <div class="ibox-title">
                    <h5>Lecture Plan Label & General Info</h5>
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
                    <form action="{{ route('course.course-structure-overview', ['id' => $course->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                            <div class="row">

                                <div class="col-lg-12 mb">
                                    
                                    <label class="mb-1">Status</label>
                                    <select name="lecture_plan_section" class="form-control">
                                        <option value="1" {{ $course->lecture_plan_section == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ $course->lecture_plan_section == 0 ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Section Heading Label (Lecture plan)</label>
                                    <input type="text"name="label[lecture_plan]" value="{{ old('label[lecture_plan]', $course->dynamicLabel?->lecture_plan) }}" class="form-control" placeholder="add section heading lable">
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Lecture Plan Overview</label>
                                    <textarea class="form-control text-editor" id="add-course-structure-overview" placeholder="Add course structure overview" name="course_structure_overview" row="4">{{ $course->course_structure_overview }}</textarea>
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
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Add Lecture Plan to {{ $course->title }} </h5>
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
                    <form action="{{ route('course.store-course-structure-part', ['id' => $course->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div id="courseStructureContainerStatic" class="mt-3">
                            <div class="courseStructureContainerStaticRow" style="margin-bottom: 16px;">
                                <div class="row">
                                    <div class="col-lg-6 mb">
                                        <label class="mb-1">Title/Part/Module</label>
                                        <input type="text" name="course[title]" class="form-control" placeholder="Add Title/Part to Course Structure i.e Part 1, Part 2">
                                    </div>
                                    <div class="col-lg-6 mb">
                                        <label class="mb-1">Part/Module Heading</label>
                                        <input type="text" name="course[heading]" class="form-control" placeholder="Add Course Heading i.e Financial Planning, Performance, and Analytics">
                                        @error('course.heading')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 mb">
                                    <label class="mb-1">Lecture Part/Module Overview</label>
                                    <textarea class="form-control text-editor" id="overview" placeholder="Add course part/module overview" name="course[overview]" row="4"></textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6" style="margin-top:12px;">
                                        <p>Sub Headings</p>
                                    </div>
                                    <div class="col-lg-6" style="margin-top:12px;text-align: right;">
                                        <button type="button" class="btn-primary btn btn-sm addCourseSubHeadingStatic" data-part-index="0"><i class="fa fa-plus"></i> Sub Heading</button>
                                    </div>
                                    <div class="col-lg-12">
                                        <hr style="margin-bottom:16px;margin-top: 0;">
                                    </div>
                                </div>

                                <div class="courseSubHeadingContainerStatic" data-part-index="0">
                                    <div class="row courseSubHeadingRowStatic">
                                        <div class="col-lg-4">
                                            <label class="mb-1">Sub Heading 1</label>
                                            <input type="text" name="course[subheading][0][subheading]" class="form-control" placeholder="Add Course Sub Heading i.e 15% External Financial Reporting Decisions">
                                            <button type="button" class="btn btn-danger btn-xs removeCourseSubHeadingRowStatic" style="position: absolute; top: 30px; right: 15px;">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="col-lg-8">
                                            <div class="courseSubHeadingUnitContainerStatic">
                                                <div class="row courseSubHeadingUnitRowStatic">
                                                    <div class="col-lg-4">
                                                        <label class="mb-1">Unit 1</label>
                                                        <input type="text" name="course[subheading][0][unit][0][title]" class="form-control" placeholder="Add Unit Title">
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <label class="mb-1">Thumbnail</label>
                                                        <input type="file" name="course[subheading][0][unit][0][thumbnail]" class="form-control" placeholder="Add Unit thumbnail">
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <label class="mb-1">Youtube URL</label>
                                                        <input type="url" name="course[subheading][0][unit][0][video]" class="form-control" placeholder="Add youtube video url">
                                                    </div>
                                                    <div class="col-lg-1" style="padding:0;">
                                                        <label class="mb-1">Unit</label>
                                                        <br>
                                                        <button type="button" class="btn-primary btn btn-xs addUnitRowStatic"><i class="fa fa-plus"></i></button>
                                                        <button type="button" class="btn-danger btn btn-xs delUnitRowStatic"><i class="fa fa-trash"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- <div class="row" style="margin-block: 16px;">
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
                                        <input type="text" name="course[exam_format]" placeholder="Add Exam Format i.e 100 Multiple-Choice Questions and Two Essay Questions" class="form-control">
                                    </div>
                                    <div class="col-lg-6" style="margin:0;">
                                        <label for="">Exam Duration</label>
                                        <input type="text" name="course[exam_duration]" placeholder="Add Exam Duration i.e 4 hours ( 100 Multiple-Choice Questions (3 hours ) and Two Essay Questions (1 hour) )" class="form-control">
                                    </div>
                                </div> -->
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 0px;text-align:right;"> 
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


@foreach($course->courseStructures as $index1 => $courseStructure)
<div class="row">
    <div class="wrapper wrapper-content">
        <div class="col-lg-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>{{ $course->title .' '. $courseStructure->title }}</h5>
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
                    <div class="courseStructureContainerRow" style="margin-bottom: 0px;">
                        <div class="row">
                            <div class="col-lg-6 mb">
                                <label class="mb-1">Title/Part/Module</label>
                                <input type="text" name="course[{{ $index1 }}][title]" class="form-control" value="{{ $courseStructure->title }}" readonly>
                            </div>
                            <div class="col-lg-6 mb">
                                <label class="mb-1">Course Heading</label>
                                <input type="text" name="course[{{ $index1 }}][heading]" class="form-control" value="{{ $courseStructure->heading }}" readonly>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12 mb">
                            <label class="mb-1">Lecture Part/Module Overview</label>
                            <textarea class="form-control" id="overview" placeholder="Add lecture part/module overview" name="course[{{ $index1 }}][overview]" row="4" readonly>{{ $courseStructure->overview }}</textarea>
                            </div>
                        </div>

                        <div class="courseSubHeadingContainer" data-part-index="0">
                            @foreach($courseStructure->subHeadings as $index2 => $subHeading)
                                <div class="row courseSubHeadingRow">
                                    <div class="col-lg-4">
                                        <label class="mb-1">Sub Heading</label>
                                        <input type="text" name="course[0][subheading][{{ $index2 }}][subheading]" class="form-control" value="{{ $subHeading->sub_heading }}" readonly>
                                        
                                    </div>
                                    <div class="col-lg-8">
                                        <div class="courseSubHeadingUnitContainer">
                                            @foreach($subHeading->subHeadingsUnits as $index3 => $subHeadingsUnit)
                                            <div class="row courseSubHeadingUnitRow">
                                                <div class="col-lg-4">
                                                    <label class="mb-1">Unit</label>
                                                    <input type="text" name="course[0][subheading][0][unit][0][title]" class="form-control" value="{{ $subHeadingsUnit->unit_title }}" readonly>
                                                </div>
                                                <div class="col-lg-4">
                                                    <label class="mb-1">Thumbnail</label> <br>
                                                    <img src="{{ asset( $subHeadingsUnit->thumbnail) }}" style="width:40px; height: 35px;" alt="">
                                                    <!-- <input type="file" name="course[0][subheading][0][unit][0][video]" class="form-control" placeholder="Add Unit Video/Content"> -->
                                                </div>
                                                <div class="col-lg-4">
                                                    <label class="mb-1">Youtube URL</label> 
                                                    @if($subHeadingsUnit->unit_video)
                                                    <br>
                                                    <a href="{{ $subHeadingsUnit->unit_video }}" target="_blank">Click to See Video</a>
                                                    <!-- <input type="url" name="course[0][subheading][0][unit][0][youtube]" class="form-control" value="{{ $subHeadingsUnit->youtube }}"> -->
                                                    @endif
                                                </div>
                                                
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- <div class="row" style="margin-block: 16px;">
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
                                <input type="text" name="course[0][exam_format]" value="{{ $courseStructure->exam_format }}" class="form-control" readonly>
                            </div>
                            <div class="col-lg-6" style="margin:0;">
                                <label for="">Exam Duration</label>
                                <input type="text" name="course[0][exam_duration]" value="{{ $courseStructure->exam_format }}" class="form-control" readonly>
                            </div>
                        </div> -->

                        <div class="row" style="margin-block: 16px;">
                            <div class="col-lg-12" style="text-align: right;">
                                <a href="{{ route('course.edit-course-structure-part', ['id' => $course->id, 'part_id' => $courseStructure->id]) }}" class="btn btn-primary btn-sm UpdateCoursePart"><i class="fa fa-edit"></i> Edit</a>
                                <a href="{{ route('course.del-course-structure', ['course_id' => $course->id, 'id' => $courseStructure->id]) }}" class="btn btn-danger btn-sm deleteCoursePart"><i class="fa fa-trash"></i> Remove</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach


@endsection

@push('script')


<script>
    
    $(document).ready(function() {
    // Handle add course subheading button click
    $('.addCourseSubHeadingStatic').on('click', function() {
        var partIndex = $(this).data('part-index');
        var subHeadingIndex = $('.courseSubHeadingContainerStatic[data-part-index="' + partIndex + '"] .courseSubHeadingRowStatic').length;
        var courseSubHeadingHtml = `
            <div class="row courseSubHeadingRowStatic">
                <div class="col-lg-4">
                    <label class="mb-1">Sub Heading ${subHeadingIndex + 1}</label>
                    <input type="text" name="course[subheading][${subHeadingIndex}][subheading]" class="form-control" placeholder="Add Course Sub Heading i.e 15% External Financial Reporting Decisions">
                    <button type="button" class="btn btn-danger btn-xs removeCourseSubHeadingRowStatic" style="position: absolute; top: 30px; right: 15px;">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="col-lg-8">
                    <div class="courseSubHeadingUnitContainerStatic">
                        <div class="row courseSubHeadingUnitRowStatic">
                            <div class="col-lg-4">
                                <label class="mb-1">Unit 1</label>
                                <input type="text" name="course[subheading][${subHeadingIndex}][unit][0][title]" class="form-control" placeholder="Add Unit Title" >
                            </div>
                            <div class="col-lg-3">
                                <label class="mb-1">Thumbnail</label>
                                <input type="file" name="course[subheading][${subHeadingIndex}][unit][0][thumbnail]" class="form-control" placeholder="Add video thumbnail">
                            </div>
                            <div class="col-lg-4">
                                <label class="mb-1">Youtube URL</label>
                                <input type="url" name="course[subheading][${subHeadingIndex}][unit][0][video]" class="form-control" placeholder="Add youtube video url">
                            </div>
                            <div class="col-lg-1" style="padding:0;">
                                <label class="mb-1">Unit</label>
                                <br>
                                <button type="button" class="btn-primary btn btn-xs addUnitRowStatic"><i class="fa fa-plus"></i></button>
                                <button type="button" class="btn-danger btn btn-xs delUnitRowStatic"><i class="fa fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('.courseSubHeadingContainerStatic[data-part-index="' + partIndex + '"]').append(courseSubHeadingHtml);
    });

    // Handle remove course subheading row
    $(document).on('click', '.removeCourseSubHeadingRowStatic', function() {
        $(this).closest('.courseSubHeadingRowStatic').remove();
    });

    // Handle add unit row button click
    $(document).on('click', '.addUnitRowStatic', function() {
        var subHeadingContainer = $(this).closest('.courseSubHeadingUnitContainerStatic');
        var unitIndex = subHeadingContainer.find('.courseSubHeadingUnitRowStatic').length;
        var subHeadingIndex = $(this).closest('.courseSubHeadingRowStatic').index();

        var unitRowHtml = `
            <div class="row courseSubHeadingUnitRowStatic">
                <div class="col-lg-4">
                    <label class="mb-1">Unit ${unitIndex + 1}</label>
                    <input type="text" name="course[subheading][${subHeadingIndex}][unit][${unitIndex}][title]" class="form-control" placeholder="Add Unit Title" >
                </div>
                <div class="col-lg-3">
                    <label class="mb-1">Thumbnail</label>
                    <input type="file" name="course[subheading][${subHeadingIndex}][unit][${unitIndex}][thumbnail]" class="form-control" placeholder="Add Unit Video/Content">
                </div>
                <div class="col-lg-4">
                    <label class="mb-1">Youtube URL</label>
                    <input type="url" name="course[subheading][${subHeadingIndex}][unit][${unitIndex}][video]" class="form-control" placeholder="Add youtube video url">
                </div>
                <div class="col-lg-1" style="padding:0;">
                    <label class="mb-1">Unit</label>
                    <br>
                    <button type="button" class="btn-danger btn btn-xs delUnitRowStatic"><i class="fa fa-trash"></i></button>
                </div>
            </div>
        `;
        subHeadingContainer.append(unitRowHtml);
    });

    // Handle delete unit row button click
    $(document).on('click', '.delUnitRowStatic', function() {
        $(this).closest('.courseSubHeadingUnitRowStatic').remove();
    });
});

</script>

<script>
    function updateLecturePlanStatus(status, courseId) {
        // AJAX call to update course status
        $.ajax({
            url: '/admin/course/' + courseId + '/update-lecture-plan',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}', // For Laravel CSRF protection
                status: status
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.success);  // Show success notification
                } else {
                    toastr.error('Unexpected response received.');
                }
            },
            error: function(xhr, status, error) {
                alert('Error updating course status.');
            }
        });
    }
</script>

@endpush