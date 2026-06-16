@extends('admin.layout.app')
@section('title', 'Edit Course Part')
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
        <h2>Course Structure</h2>
        <ol class="breadcrumb">
            <li>
                <a href="">Home</a>
            </li>
            <li>
                <a>Courses</a>
            </li>
            <li>
                <a>{{ $course->title }}</a>
            </li>
            <li class="active">
                <strong>Edit</strong>
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
                    <h5>{{ $course->title . ' ' . ($course->courseStructuresFirst[0]->title ?? 'No Title') }}</h5>
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
                <form action="{{ route('course.update-course-structure-part-first') }}" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="course_id" value="{{ $course->id }}">
                    <input type="hidden" name="course[course_structures_id]" value="{{ $course->courseStructuresFirst[0]->id }}">
                    @csrf
                    <div class="courseStructureContainerStatic" style="margin-bottom: 16px;">
                        <div class="courseStructureContainerStaticRow" style="margin-bottom: 16px;">
                            <div class="row">
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Title/Part/Module</label>
                                    <input type="text" name="course[title]" class="form-control" value="{{ $course->courseStructuresFirst[0]->title }}">
                                </div>
                                <div class="col-lg-6 mb">
                                    <label class="mb-1">Part/Module Heading</label>
                                    <input type="text" name="course[heading]" class="form-control" value="{{ $course->courseStructuresFirst[0]->heading }}">
                                    @error('course.heading')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Course Part/Module Overview</label>
                                    <textarea class="form-control text-editor" id="overview" value="{{ $course->courseStructuresFirst[0]->overview }}" name="course[overview]" row="4">{{ $course->courseStructuresFirst[0]->overview }}</textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6" style="margin-top:12px;">
                                    <p>Sub Headings</p>
                                </div>
                                <div class="col-lg-6" style="margin-top:12px;text-align: right;">
                                    <button type="button" class="btn-primary btn btn-xs addCourseSubHeadingStatic" data-part-index="0"><i class="fa fa-plus"></i> Sub Heading</button>
                                </div>
                                <div class="col-lg-12">
                                    <hr style="margin-bottom:16px;margin-top: 0;">
                                </div>
                            </div>

                            <div class="courseSubHeadingContainerStatic" data-part-index="0">
                                @foreach($course->courseStructuresFirst['0']->subHeadingsFirst as $index1 => $subHeading)
                                <div class="row courseSubHeadingRowStatic">
                                    <input type="hidden" name="course[subheading][{{ $index1 }}][id]" value="{{ $subHeading->id }}">
                                    <div class="col-lg-12 mb">
                                        <label class="mb-1">Sub Heading</label>
                                        <input type="text" name="course[subheading][{{ $index1 }}][subheading]" class="form-control" value="{{ $subHeading->sub_heading }}">
                                        <button type="button" class="btn btn-danger btn-xs removeCourseSubHeadingRowStatic" style="position: absolute; top: 30px; right: 15px;">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>

                                    

                                </div>
                                @endforeach
                            </div>
                            
                            <div class="row">
                                <div class="col-lg-12 mb">
                                    <label for="">Exam Format</label>
                                    <textarea type="text" name="course[exam_format]" class="form-control text-editor" value="{!! $course->courseStructuresFirst[0]->exam_format !!}" >{!! $course->courseStructuresFirst[0]->exam_format !!}</textarea>
                                </div>
                                <div class="col-lg-12 mb">
                                    <label for="">Exam Duration</label>
                                    <textarea type="text" name="course[exam_duration]" class="form-control text-editor" value="{!! $course->courseStructuresFirst[0]->exam_duration !!}" >{!! $course->courseStructuresFirst[0]->exam_duration !!}</textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12" style="margin-top: 16px;"> 
                                    <button class="btn btn-primary " type="submit"><i class="fa fa-check"></i>&nbsp;Update</button>
                                    <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                                </div>
                            </div>
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
<script>
    
    $(document).ready(function() {
    // Handle add course subheading button click
    $('.addCourseSubHeadingStatic').on('click', function() {
        var partIndex = $(this).data('part-index');
        var subHeadingIndex = $('.courseSubHeadingContainerStatic[data-part-index="' + partIndex + '"] .courseSubHeadingRowStatic').length;
        var courseSubHeadingHtml = `
            <div class="row courseSubHeadingRowStatic">
                <div class="col-lg-12">
                    <label class="mb-1">Sub Heading</label>
                    <input type="text" name="course[subheading][${subHeadingIndex}][subheading]" class="form-control" placeholder="Add Course Sub Heading i.e 15% External Financial Reporting Decisions">
                    <button type="button" class="btn btn-danger btn-xs removeCourseSubHeadingRowStatic" style="position: absolute; top: 30px; right: 15px;">
                        <i class="fa fa-times"></i>
                    </button>
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
    // $(document).on('click', '.addUnitRowStatic', function() {
    //     var subHeadingContainer = $(this).closest('.courseSubHeadingUnitContainerStatic');
    //     var unitIndex = subHeadingContainer.find('.courseSubHeadingUnitRowStatic').length;
    //     var subHeadingIndex = subHeadingContainer.closest('.courseSubHeadingRowStatic').index('.courseSubHeadingRowStatic');

    //     var unitRowHtml = `
    //         <div class="row courseSubHeadingUnitRowStatic">
    //             <div class="col-lg-4">
    //                 <label class="mb-1">Unit ${unitIndex + 1}</label>
    //                 <input type="text" name="course[subheading][${subHeadingIndex}][unit][${unitIndex}][title]" class="form-control" placeholder="Add Unit Title">
    //             </div>
    //             <div class="col-lg-3">
    //                 <label class="mb-1">Thumbnail</label>
    //                 <input type="file" name="course[subheading][${subHeadingIndex}][unit][${unitIndex}][thumbnail]" class="form-control" placeholder="Add thumbnail">
    //             </div>
    //             <div class="col-lg-4">
    //                 <label class="mb-1">Youtube Video URL</label>
    //                 <input type="url" name="course[subheading][${subHeadingIndex}][unit][${unitIndex}][video]" class="form-control" placeholder="Add video url">
    //             </div>
    //             <div class="col-lg-1" style="padding:0;">
    //                 <label class="mb-1">Unit</label>
    //                 <br>
    //                 <button type="button" class="btn-danger btn btn-xs delUnitRowStatic"><i class="fa fa-trash"></i></button>
    //             </div>
    //         </div>
    //     `;
    //     subHeadingContainer.append(unitRowHtml);
    // });

    // // Handle delete unit row button click
    // $(document).on('click', '.delUnitRowStatic', function() {
    //     $(this).closest('.courseSubHeadingUnitRowStatic').remove();
    // });
});


</script>
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