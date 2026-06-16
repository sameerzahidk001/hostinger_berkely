@extends('admin.layout.app')
@section('title', 'Add Course')
@push('style')
<style>
    .package-card{
        border: 1px solid #e5e6e7;
        border-radius: 8px;
        padding:16px;
        /* max-width: 260px; */
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
                <a>Course</a>
            </li>
            <li>
                <a>{{ $course->title }}</a>
            </li>
            <li class="active">
                <strong>Add Fee Structure</strong>
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
                    <h5>{{ $course->title }}</h5>
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
                    <form role="form" action="{{ route('course.store-fee-str') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $course->id }}">
                        <!-- <label for="">Heading</label>
                        <textarea type="text" row="4" class="form-control"></textarea> -->
                        <div class="row">
                            <div class="col-lg-12">
                                <button id="add-package-btn" type="button" class="btn btn-xs btn-outline btn-primary" style="float:right;margin: 16px 0;">Add Package</button>
                            </div>
                        </div>

                        <div id="package-container" class="row">
                            <!-- Initial package card -->
                            <div class="col-sm-3">
                                <div class="package-card" data-index="0">
                                    <button type="button" class="btn btn-danger btn-sm delete-package-btn" style="position: absolute; top: 0px; right: 15px;"><i class="fa fa-trash"></i></button>
                                    <label for="">Package Name</label>
                                    <input type="text" class="form-control" name="package[0][name]">

                                    <label for="">Price</label>
                                    <input type="number" class="form-control" name="package[0][price]">

                                    <label for="">Discount Percentage</label>
                                    <input type="number" class="form-control" name="package[0][discount]">
                                    
                                    <label class="ckbox" style="display:inline;">
                                        <input type="checkbox" value="1" name="package[0][is_recommended]"><span> Most Popular</span>
                                    </label>
                                </div>
                            </div>
                        </div>


                        <div class="row" style="margin-top: 12px;">
                            <div class="col-lg-12"> 
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
<script>
    let packageIndex = 1;

    document.getElementById('add-package-btn').addEventListener('click', function() {
        // Generate new package card HTML
        let newPackageCard = `
            <div class="col-sm-3">
                <div class="package-card" data-index="${packageIndex}">
                    <button type="button" class="btn btn-danger btn-sm delete-package-btn" style="position: absolute; top: 0px; right: 15px;"><i class="fa fa-trash"></i></button>
                    <label for="">Package Name</label>
                    <input type="text" class="form-control" name="package[${packageIndex}][name]">

                    <label for="">Price</label>
                    <input type="number" class="form-control" name="package[${packageIndex}][price]">

                    <label for="">Discount Percentage</label>
                    <input type="number" class="form-control" name="package[${packageIndex}][discount]">
                    
                    <label class="ckbox" style="display:inline;">
                        <input type="checkbox" value="1" name="package[${packageIndex}][is_recommended]"><span> Most Popular</span>
                    </label>
                </div>
            </div>
        `;
        // Append the new package card to the container
        document.getElementById('package-container').insertAdjacentHTML('beforeend', newPackageCard);
        // Increment the index for the next package card
        packageIndex++;
    });

    // Event delegation to handle delete button click
    document.getElementById('package-container').addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('delete-package-btn')) {
            e.target.closest('.col-sm-3').remove();
        }
    });
</script>

@endpush