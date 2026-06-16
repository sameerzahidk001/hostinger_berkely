@extends('admin.layout.app')
@section('title', 'Subjects')

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
                <a>{{ $fee_structure->title }}</a>
            </li>
            <li>
                <a>Edit Fee Structure Details</a>
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
                    <h5>{{ $fee_structure->title }} Fee Structure Details</h5>
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
                @php
                    $feePackagesCount = $fee_structure->feePackages->count();
                @endphp
                <div class="ibox-content">
                    <form role="form" action="{{ route('course.store-fee-features') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $fee_structure->id }}">
                        <table class="table table-bordered">
                            <tr style="border-bottom: 1px solid #e7eaec;">
                                @foreach($fee_structure->feePackages as $index => $data)
                                    @if($loop->first)
                                    <td style="vertical-align: middle;">
                                        <label for="">Heading</label>
                                        <input type="text" name="general_heading" class="form-control">
                                    </td>
                                    @endif
                                    <td style="vertical-align: middle;">
                                        <div class="ibox">
                                            <div class="ibox-content product-box">
                                                <div class="product-desc">
                                                    
                                                    <small class="product-name">{{ $data->package_name }}</small>
                                                    
                                                    <div class="small m-t-xs">
                                                        Price: {{ $data->price }}
                                                    </div>
                                                    @if($data->discount_percentage)
                                                    <div class="small m-t-xs">
                                                        Precentage: {{ $data->discount_percentage .'%' }}
                                                    </div>
                                                    @endif
                                                    <div class="small m-t-xs">
                                                        Discounted Price: {{ $data->discounted_price }}
                                                    </div>
                                                    @if($data->is_recommended == 1)
                                                    <label class="ckbox" style="display:inline;">
                                                        <input type="checkbox" checked><span> Recommended</span>
                                                    </label>
                                                    @endif
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">Course Access</label>
                                        </td>
                                    @endif
                                    <td>
                                        <input type="text" name="fee_features[{{$i}}][course_access]" class="form-control">
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">Flexible Payment options (0% interest)</label>
                                        </td>
                                    @endif
                                    <td>
                                        <input type="text" name="fee_features[{{$i}}][payment_option]" class="form-control">
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr style="background-color: #000435;">
                                <td colspan="{{ $feePackagesCount + 1 }}" style="color:white;">Pass Guarantee</td>
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">100% Pass Guarantee to get through your exam</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][pass_guarantee][0]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][pass_guarantee][0]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">$1,000 back + Back-On-Track plan</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][pass_guarantee][1]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][pass_guarantee][1]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr style="background-color: #000435;">
                                <td colspan="{{ $feePackagesCount + 1 }}" style="color:white;">Exam Day Ready Features</td>
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">Complete 4-part course including Disciplines matching AICPA Exam blueprint Includes New Disciplines</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][exam_day_ready_features][0]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][exam_day_ready_features][0]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">Exam Day Ready toolkit</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][exam_day_ready_features][1]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][exam_day_ready_features][1]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">Team of Expert Instructors</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][exam_day_ready_features][2]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][exam_day_ready_features][2]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">On-the-go Mobile app + Award-winning study game app</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][exam_day_ready_features][3]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][exam_day_ready_features][3]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">Unlimited Adapt2U Technology driven, custom personalized practice tests</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][exam_day_ready_features][4]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][exam_day_ready_features][4]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">Accessible Discipline Sections</label>
                                        </td>
                                    @endif
                                    <td>
                                        
                                        <input type="number" class="form-control" name="fee_features[{{ $i }}][exam_day_ready_features][5]">
                                        
                                        
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr style="background-color: #000435;">
                                <td colspan="{{ $feePackagesCount + 1 }}" style="color:white;">Unmatched Resources and Tools</td>
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">Best-in-class printed Textbooks</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][unmatched_resources_and_tools][0]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][unmatched_resources_and_tools][0]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">Success Coaching sessions</label>
                                        </td>
                                    @endif
                                    <td>
                                        
                                        <input type="hidden" name="fee_features[{{ $i }}][unmatched_resources_and_tools][1]" value="0">
                                        <input type="checkbox" name="fee_features[{{ $i }}][unmatched_resources_and_tools][1]" value="1">
                                        
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">1-on-1 Tutoring sessions</label>
                                        </td>
                                    @endif
                                    <td>
                                        
                                        <input type="hidden" name="fee_features[{{ $i }}][unmatched_resources_and_tools][2]" value="0">
                                        <input type="checkbox" name="fee_features[{{ $i }}][unmatched_resources_and_tools][2]" value="1">
                                        
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">LiveOnline Exam classes</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="checkbox">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">In-person Exam classes (limited locations)</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][unmatched_resources_and_tools][3]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][unmatched_resources_and_tools][3]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">Final Review capstone course</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][unmatched_resources_and_tools][4]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][unmatched_resources_and_tools][4]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">Printable Flashcards</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][unmatched_resources_and_tools][5]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][unmatched_resources_and_tools][5]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">1 year Becker CPE subscription</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][unmatched_resources_and_tools][6]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][unmatched_resources_and_tools][6]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">Deep Dive Workshops Bundle</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][unmatched_resources_and_tools][7]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][unmatched_resources_and_tools][7]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">ExamSolver Videos Bundle</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][unmatched_resources_and_tools][8]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][unmatched_resources_and_tools][8]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">Licensing Navigator sessions</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][unmatched_resources_and_tools][9]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][unmatched_resources_and_tools][9]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                            <tr>
                                @for($i = 0; $i < $feePackagesCount; $i++)
                                    @if($i == 0)
                                        <td>
                                            <label for="">Certificate Program</label>
                                        </td>
                                    @endif
                                    <td>
                                        <label class="ckbox" style="display:inline;">
                                            <input type="hidden" name="fee_features[{{ $i }}][unmatched_resources_and_tools][10]" value="0">
                                            <input type="checkbox" name="fee_features[{{ $i }}][unmatched_resources_and_tools][10]" value="1">
                                        </label>
                                    </td>
                                    
                                @endfor
                            </tr>
                        </table>

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

@endpush