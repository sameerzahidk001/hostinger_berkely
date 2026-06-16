@extends('admin.layout.app')
@section('title', 'Course')
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
        <h2>{{ $course->title }}</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            <li>
                <a>Course</a>
            </li>
            <li class="active">
                <strong>Details</strong>
            </li>
        </ol>
    </div>
    <!-- <div class="col-lg-2">

    </div> -->
</div>

<div class="wrapper wrapper-content animated fadeInRight">
  <div class="row">
    <div class="col-lg-12">
        <div class="tabs-container">
            <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#tab-1" aria-expanded="false"> Overview</a></li>
                <li><a data-toggle="tab" href="#tab-2" aria-expanded="true">Objectives</a></li>
                <li><a data-toggle="tab" href="#tab-3" aria-expanded="true">Curriculum</a></li>
                <li><a data-toggle="tab" href="#tab-4" aria-expanded="true">Training Calender</a></li>
                <li><a data-toggle="tab" href="#tab-5" aria-expanded="true"> Testimonials</a></li>
                <li><a data-toggle="tab" href="#tab-7" aria-expanded="true"> Rewards</a></li>
                <li><a data-toggle="tab" href="#tab-6" aria-expanded="true"> FAQ's</a></li>
            </ul>
            <div class="tab-content">

                <div id="tab-1" class="tab-pane active">
                    <div class="panel-body">
                        <strong>{{ $course->title }}</strong>

                        <p>{!! $course->description !!}</p>

                        <p>{!! $course->awarded_by !!}</p>
                    </div>
                </div>

                <div id="tab-2" class="tab-pane ">
                    <div class="panel-body">
                        <div class="ibox-title-cust">
                            <strong>Objectives</strong>
                            <div class="ibox-tools">
                                <div class="btn-group">
                                    <a  data-toggle="modal" href="#AddobjectiveModal" data-item-id="{{ $course->id }}" class="btn-primary btn btn-xs"><i class="fa fa-plus-square"></i> Add</a>
                                </div>
                            </div>
                        </div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Sr#</th>
                                    <th>Description</th>
                                    <!-- <th>Status</th> -->

                                    <th style="width: 13%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($course->courseObjectivePoints as $index => $data)
                                <tr id="row_{{$data->id }}">
                                    <td>{{ ++$index }}</td>
                                    <td>{{ $data->description }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn-primary btn btn-xs EditObjectiveModal" data-toggle="modal" href="#EditobjectiveModal" data-module="courseObjectivePoints" data-id="{{ $data->id }}" data-description="{{ $data->description }}"><i class="fa fa-edit"></i> Edit</button>
                                            <button type="button" class="btn-danger btn btn-xs delete-btn" data-module="courseObjectivePoints" data-id="{{ $data->id }}"><i class="fa fa-trash"></i> Delete</button>
                                      </div>
                                    </td>
                                </tr>
                                @empty @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="tab-3" class="tab-pane ">
                    <div class="panel-body">
                        <div class="ibox-title-cust">
                            <strong>Curriculum</strong>
                            <div class="ibox-tools">
                                <div class="btn-group">
                                    <a  data-toggle="modal" href="#AddSyllabusModal" data-item-id="{{ $course->id }}" class="btn-primary btn btn-xs"><i class="fa fa-plus-square"></i> Add</a>
                                </div>
                            </div>
                        </div>

                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Subtitle</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Duration</th>
                                    
                                    <th style="width: 13%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($course->courseSyllabus as $index => $data)
                                <tr id="row_{{$data->id }}">
                                    <td>{{ $data->subtitle }}</td>
                                    <td>{{ $data->title }}</td>
                                    <td>{{ $data->description }}</td>
                                    <td>{{ $data->duration }} {{ $data->duration_unit }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="" class="btn-primary btn btn-xs"><i class="fa fa-edit"></i> Edit</a>
                                            <button type="button" class="btn-danger btn btn-xs delete-btn" data-module="courseSyllabus" data-id="{{ $data->id }}"><i class="fa fa-trash"></i> Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                @empty @endforelse
                            </tbody>
                        </table>
                        
                    </div>
                </div>

                <div id="tab-4" class="tab-pane ">
                    <div class="panel-body">
                        <div class="ibox-title-cust">
                            <strong>Training Calender</strong>
                            <div class="ibox-tools">
                                <div class="btn-group">
                                    <a  data-toggle="modal" href="#AddEnrollmentModal" data-item-id="{{ $course->id }}" class="btn-primary btn btn-xs"><i class="fa fa-plus-square"></i> Add</a>
                                </div>
                            </div>
                        </div>

                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Starting Date#</th>
                                    <th>Deadline</th>
                                    <th>Discount</th>
                                    <th>Brochure</th>

                                    <th style="width: 13%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($course->courseEnrollments as $index => $data)
                                <tr id="row_{{$data->id }}">
                                    <td>{{ $data->starting_date }}</td>
                                    <td>{{ $data->application_deadline }}</td>
                                    <td>{{ $data->discount }}</td>
                                    <td>
                                        <a href="{{ $data->brochure }}">{{ $data->brochure }}</a>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="" class="btn-primary btn btn-xs"><i class="fa fa-edit"></i> Edit</a>
                                            <button type="button" class="btn-danger btn btn-xs delete-btn" data-module="courseEnrollments" data-id="{{ $data->id }}"><i class="fa fa-trash"></i> Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                @empty @endforelse
                            </tbody>
                        </table>
                        
                    </div>
                </div>

                <div id="tab-5" class="tab-pane ">
                    <div class="panel-body">
                        <div class="ibox-title-cust">
                            <strong>Testimonials/Beneficiaries</strong>
                            <div class="ibox-tools">
                                <div class="btn-group">
                                    <a  data-toggle="modal" href="#AddBeneficiaryModal" data-item-id="{{ $course->id }}" class="btn-primary btn btn-xs"><i class="fa fa-plus-square"></i> Add</a>
                                </div>
                            </div>
                        </div>

                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th style="width: 13%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($course->courseBeneficiaries as $index => $data)
                                <tr id="row_{{$data->id }}">
                                    
                                    <td>{{ $data->title }}</td>
                                    <td>{{ $data->description }}</td>
                                    
                                    <td>
                                        <div class="btn-group">
                                            <a href="" class="btn-primary btn btn-xs"><i class="fa fa-edit"></i> Edit</a>
                                            <button type="button" class="btn-danger btn btn-xs delete-btn" data-module="courseBeneficiaries" data-id="{{ $data->id }}"><i class="fa fa-trash"></i> Delete</button>
                                      </div>
                                    </td>
                                </tr>
                                @empty @endforelse
                            </tbody>
                        </table>

                    </div>
                </div>

                <div id="tab-6" class="tab-pane ">
                    <div class="panel-body">
                        <div class="ibox-title-cust">
                            <strong>FAQ'S</strong>
                            <div class="ibox-tools">
                                <div class="btn-group">
                                    <a  data-toggle="modal" href="#AddFAQModal" data-item-id="{{ $course->id }}" class="btn-primary btn btn-xs"><i class="fa fa-plus-square"></i> Add</a>
                                </div>
                            </div>
                        </div>

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
                                <tr id="row_{{$data->id }}">
                                    <td>{{ ++$index }}</td>
                                    <td>{{ $data->title }}</td>
                                    <td>{{ $data->short_description }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn-primary btn btn-xs editFaqs" data-toggle="modal" href="#updateFaqsModal" data-module="courseFAQS" data-id="{{ $data->id }}" data-title="{{ $data->title }}" data-description="{{ $data->short_description }}"><i class="fa fa-edit"></i> Edit</button>
                                            <!-- <button type="button" class="btn-primary btn btn-xs updateFaqs"><i class="fa fa-edit"></i> Edit</button> -->
                                            <button type="button" class="btn-danger btn btn-xs delete-btn" data-module="courseFaq" data-id="{{ $data->id }}"><i class="fa fa-trash"></i> Delete</button>
                                      </div>
                                    </td>
                                </tr>
                                @empty @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="tab-7" class="tab-pane ">
                    <div class="panel-body">
                        <div class="ibox-title-cust">
                            <strong>Rewards</strong>
                            <div class="ibox-tools">
                                <div class="btn-group">
                                    <a  data-toggle="modal" href="#AddRewardModal" data-item-id="{{ $course->id }}" class="btn-primary btn btn-xs"><i class="fa fa-plus-square"></i> Add </a>
                                </div>
                            </div>
                        </div>

                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Condition</th>
                                    <!-- <th>Status</th> -->

                                    <th style="width: 13%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($course->courseRewards as $index => $data)
                                <tr id="row_{{$data->id }}">
                                    <td>
                                        <a href="{{ asset($data->image) }}" target="blank">
                                            <img src="{{ asset($data->image) }}" alt="" height="50">
                                        </a>
                                    </td>
                                    <td>{{ $data->title }}</td>
                                    <td>{{ $data->description }}</td>
                                    <td>{{ $data->conditon }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="" class="btn-primary btn btn-xs"><i class="fa fa-edit"></i> Edit</a>
                                            <button type="button" class="btn-danger btn btn-xs delete-btn" data-module="courseRewards" data-id="{{ $data->id }}"><i class="fa fa-trash"></i> Delete</button>
                                      </div>
                                    </td>
                                </tr>
                                @empty @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>


        </div>
    </div>
  </div>
</div>



<div id="AddobjectiveModal" class="modal fade in" aria-hidden="true" style="padding-right: 17px;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12">
                        <h3 class="m-t-none m-b">Add Objective</h3>

                        <form role="form" action="{{ route('course-obj.add')}}" method="POST">
                            @csrf
                            <input type="hidden" name="course_id" id="obje-inp-course-id" value="" />
                            <div class="form-group">
                                <label>Description</label>
                                <textarea type="text" name="description" placeholder="add description" row="3" class="form-control"></textarea>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-primary pull-right m-t-n-xs" type="submit"><strong>Submit</strong></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="EditobjectiveModal" class="modal fade in" aria-hidden="true" style="padding-right: 17px;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12">
                        <h3 class="m-t-none m-b">Edit Objective</h3>

                        <form role="form">
                            
                            <input type="hidden" name="id" id="objectiveId" value="" />
                            <input type="hidden" name="module" id="module" value="" />
                            <input type="hidden" name="module" id="course-id" value="{{ $course->id }}" />
                            <div class="form-group">
                                <label>Description</label>
                                <textarea class="form-control" id="EditobjectiveModal-description" rows="3"></textarea>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-primary pull-right m-t-n-xs update-Courseobjective" type="button"><strong>Update</strong></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="AddRewardModal" class="modal fade in" aria-hidden="true" style="padding-right: 17px;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12">
                        <h3 class="m-t-none m-b">Add Course Rewards</h3>

                        <form role="form" action="{{ route('course-earning.add')}}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="course_id" id="reward-modal-course-id" value="" />
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" placeholder="add title" class="form-control"/>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea type="text" name="description" placeholder="add description" row="3" class="form-control"></textarea>
                            </div>
                            <div class="form-group">
                                <label>How to Earn</label>
                                <textarea type="text" name="conditon" placeholder="how to earn text" row="3" class="form-control"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Reward/Certificate Image</label>
                                <div class="fallback">
                                    <input name="image" type="file" />
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-primary pull-right m-t-n-xs" type="submit"><strong>Submit</strong></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="AddFAQModal" class="modal fade in" aria-hidden="true" style="padding-right: 17px;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12">
                        <h3 class="m-t-none m-b">Add Course FAQs</h3>

                        <form role="form" action="{{ route('course-faq.add') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="course_id" id="faq-modal-course-id" value="" />
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" placeholder="add title" class="form-control"/>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea type="text" name="description" placeholder="add description" row="3" class="form-control"></textarea>
                            </div>
                           
                            <div>
                                <button class="btn btn-sm btn-primary pull-right m-t-n-xs" type="submit"><strong>Submit</strong></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="updateFaqsModal" class="modal fade in" aria-hidden="true" style="padding-right: 17px;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12">
                        <h3 class="m-t-none m-b">Update FAQs</h3>

                        <form role="form">
                            <input type="hidden" name="id" id="FAQId" value="" />
                            <input type="hidden" name="module" id="module" value="" />
                            <input type="hidden" name="module" id="course-id" value="{{ $course->id }}" />

                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" id="update-faq-title" placeholder="add title" class="form-control"/>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea type="text" name="description" id="update-faq-short-description" placeholder="add description" row="5" class="form-control"></textarea>
                            </div>
                           
                            <div>
                                <button class="btn btn-sm btn-primary pull-right m-t-n-xs update-CourseFAQ" type="submit"><strong>Update</strong></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="AddBeneficiaryModal" class="modal fade in" aria-hidden="true" style="padding-right: 17px;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12">
                        <h3 class="m-t-none m-b">Add Course Beneficiary</h3>

                        <form role="form" action="{{ route('course-beneficiary.add')}}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="course_id" id="Beneficiary-modal-course-id" value="" />
                            <div class="form-group">
                                <label>Title/Taq</label>
                                <input type="text" name="title" placeholder="add title" class="form-control"/>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea type="text" name="description" placeholder="add description" row="3" class="form-control"></textarea>
                            </div>
                           
                            <div>
                                <button class="btn btn-sm btn-primary pull-right m-t-n-xs" type="submit"><strong>Submit</strong></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="AddEnrollmentModal" class="modal fade in" aria-hidden="true" style="padding-right: 17px;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12">
                        <h3 class="m-t-none m-b">Add Course Enrollment</h3>

                        <form role="form" action="{{ route('course-enrollment.add')}}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="course_id" id="Enrollment-modal-course-id" value="" />
                            <div class="form-group">
                                <label>Starting Date</label>
                                <input type="date" name="starting_date" placeholder="add starting date" class="form-control"/>
                            </div>
                            <div class="form-group">
                                <label>Application Deadline</label>
                                <input type="date" name="deadline" placeholder="add application deadline" class="form-control"/>
                            </div>
                            <div class="form-group">
                                <label>Early Bird Discount</label>
                                <input type="number" name="early_bird_discount" placeholder="add early bird discount" class="form-control"/>
                            </div>
                            <div class="form-group">
                                <label>brochure</label>
                                <input type="file" name="brochure" placeholder="add application brochure" class="form-control"/>
                            </div>
                           
                            <div>
                                <button class="btn btn-sm btn-primary pull-right m-t-n-xs" type="submit"><strong>Submit</strong></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="AddSyllabusModal" class="modal fade in" aria-hidden="true" >
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <form role="form" action="{{ url('admin/course/add-course-syllabus')}}" method="POST" enctype="multipart/form-data">
                        <div class="col-sm-6 b-r">
                            <h3 class="m-t-none m-b">Add Course Syllabus</h3>

                            @csrf
                            <input type="hidden" name="course_id" id="SyllabusModalCourseIdInput" value="" />
                            <div class="form-group">
                                <label>Syllabus/Course/Module/Part Title</label>
                                <input type="text" name="subtitle" placeholder="add Syllabus/Course/Module/Part Title" class="form-control"/>
                            </div>
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" placeholder="add Syllabus title" class="form-control"/>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea type="text" name="description" placeholder="add Syllabus description" class="form-control" row="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Syllabus/Course/Module/Part Duration</label>
                                <input type="text" name="duration" placeholder="add Syllabus/Course/Module/Part duration i.e 5-6" class="form-control"/>
                            </div>
                            <div class="form-group">
                                <label>Syllabus/Course/Module/Part Duration Unit</label>
                                <select name="duration_unit" id="" class="form-control">
                                    <option value="hrs">Hours</option>
                                    <option value="week">Week</option>
                                    <option value="month">Month</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Featured Points</label>
                                <input type="text" class="form-control" name="featuredPoints[]" placeholder="add featured points">
                                <div id="exerciseInputContainer">
                                    <!-- Dynamic inputs will be added here -->
                                </div>
                                <button type="button" class="btn btn-info mt-3" id="addExerciseButton">Add More</button>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="highlights[0][title]" placeholder="higlight title" class="form-control"/>
                            </div>
                            <div class="form-group">
                                <label>Image/video</label>
                                <input type="file" name="highlights[0][h-image]" placeholder="add image or video" class="form-control"/>
                            </div>
                            <div id="highlights-section">
                            
                            </div>
                            <button type="button" id="addHighlightBtn" class="btn btn-primary">Add Highlight</button>
                        <div>

                        <div class="col-sm-12">
                            <div>
                                <button class="btn btn-sm btn-primary pull-right m-t-n-xs" type="submit"><strong>Submit</strong></button>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- <div class="row">
                    <div class="col-auto">
                        <div>
                            <button class="btn btn-sm btn-primary pull-right m-t-n-xs" type="submit"><strong>Submit</strong></button>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
  $(document).ready(function() {


    $('#AddobjectiveModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget); // Button that triggered the modal
      var itemId = button.data('item-id'); // Extract info from data-* attributes
      var modal = $(this);
      modal.find('#obje-inp-course-id').val(itemId);
    });

    $('#AddRewardModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget); // Button that triggered the modal
      var itemId = button.data('item-id'); // Extract info from data-* attributes
      var modal = $(this);
      modal.find('#reward-modal-course-id').val(itemId);
    });

    $('#AddFAQModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget); // Button that triggered the modal
      var itemId = button.data('item-id'); // Extract info from data-* attributes
      var modal = $(this);
      modal.find('#faq-modal-course-id').val(itemId);
    });

    $('#AddBeneficiaryModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget); // Button that triggered the modal
      var itemId = button.data('item-id'); // Extract info from data-* attributes
      var modal = $(this);
      modal.find('#Beneficiary-modal-course-id').val(itemId);
    });

    $('#AddEnrollmentModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget); // Button that triggered the modal
      var itemId = button.data('item-id'); // Extract info from data-* attributes
      var modal = $(this);
      modal.find('#Enrollment-modal-course-id').val(itemId);
    });

    $('#AddSyllabusModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget); // Button that triggered the modal
      var itemId = button.data('item-id'); // Extract info from data-* attributes
      var modal = $(this);
      modal.find('#SyllabusModalCourseIdInput').val(itemId);
    });

    $('#addHighlightBtn').click(function(e){
        e.preventDefault(); // Prevent form submission
        
        const index = $('#highlights-section').children().length + 1; // Get the current length and add 1 for the new index
        
        // Append a new set of inputs for title and image
        $('#highlights-section').append(`
            <div class="highlight my-3 p-3 border rounded">
                <div class="mb-3">
                    <label for="title-${index}" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title-${index}" name="highlights[${index}][title]">
                </div>
                <div class="mb-3">
                    <label for="image-${index}" class="form-label">Image</label>
                    <input type="file" class="form-control" id="image-${index}" name="highlights[${index}][h-image]">
                </div>
            </div>
        `);
    });


  });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('exerciseInputContainer');
        const addButton = document.getElementById('addExerciseButton');

        addButton.onclick = function() {
            const inputGroup = document.createElement('div');
            inputGroup.innerHTML = `
                <label>Featured Points</label>
                <input type="text" class="form-control" name="featuredPoints[]" placeholder="add featured points">
            `;
            container.appendChild(inputGroup);

            // Remove input group
            inputGroup.querySelector('.removeButton').addEventListener('click', function() {
                inputGroup.remove();
            });
        };
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

</script>
<script>
$(document).ready(function(){
    // edit objective points starts
    $('.EditObjectiveModal').click(function(){
        
        var description = $(this).data('description');
        $('#EditobjectiveModal-description').val(description);

        var module = $(this).data('module');
        $('#module').val(module);

        var objectiveId = $(this).data('id');
        $('#objectiveId').val(objectiveId);

    });

    $('.update-Courseobjective').on('click', function() {
        var objectiveId = $('#objectiveId').val();
        var module = $('#module').val();
        var description = $('#EditobjectiveModal-description').val();
        var courseId = $('#course-id').val();

        $.ajax({
            url: "{{ route('course.module-update') }}",
            method: 'POST',
            data: {
                id: objectiveId,
                module: module,
                description: description,
                courseId: courseId
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                // Handle success
                $('#EditobjectiveModal').modal('hide');
                swal("Updated!", response.message, "success");
            },
            error: function(error) {
                // Handle error
                swal("Deleted!", "Failed to update record!", "success");
            }
        });

    });
    // edit objective points ENDS

    // edit FAQS points starts
    $('.editFaqs').click(function(){
        
        var title = $(this).data('title');
        $('#update-faq-title').val(title);

        var description = $(this).data('description');
        $('#update-faq-short-description').val(description);

        var module = $(this).data('module');
        $('#module').val(module);

        var FAQId = $(this).data('id');
        $('#FAQId').val(FAQId);

    });

    $('.update-CourseFAQ').on('click', function(e) {
        e.preventDefault();
        var objectiveId = $('#FAQId').val();
        var module = $('#module').val();
        var title = $('#update-faq-title').val();
        var shortDescription = $('#update-faq-short-description').val();
        var courseId = $('#course-id').val();

        $.ajax({
            url: "{{ route('course.module-update-faq') }}",
            method: 'POST',
            data: {
                id: objectiveId,
                module: module,
                shortDescription: shortDescription,
                title: title,
                courseId: courseId
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                // Handle success
                $('#updateFaqsModal').modal('hide');
                swal("Updated!", response.message, "success");
            },
            error: function(error) {
                // Handle error
                swal("Deleted!", "Failed to update record!", "success");
            }
        });

    });

});

</script>
@endpush