@extends('admin.layout.app')
@section('title', 'Edit Category')
@push('style')

@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-12">
        <h2>{{ $subject->name }}</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            <li>
                <a>Category</a>
            </li>
            <li class="active">
                <strong>Edit</strong>
            </li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="wrapper wrapper-content">
        <div class="col-lg-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Edit Category</h5>
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
                    <form action="{{ route('subject.update', ['id' => $subject->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                        <div class="row row-sm mg-t-20">
                            <div class="col-lg-6">
                                <label for="">Subject Name</label>
                                <input class="form-control" value="{{old('name', $subject->name)}}" type="text" name="name" value="{{old('name')}}">
                            </div>
                            <div class="col-lg-6">
                                <label for="">Priority</label>
                                <input class="form-control" value="{{old('name', $subject->priority)}}" type="text" name="priority" value="{{old('priority')}}">
                            </div>
                            <div class="col-lg-6">
                                <label for="">Image</label>
                                <input type="file" name="image" class="form-control" id="image" onchange="previewImage();" value="{{old('image', $subject->image )}}">
                            </div>
                            <div class="col-lg-6">
                                <label for="">Preview</label>
                                <img id="imagePreview" class="form-control" style="max-width: 150px; max-height: 150px;min-height: 150px;" src="{{ asset($subject->image) }}">
                            </div>
                            <div class="col-lg-6">
                                <label for="">Short Description</label>
                                <textarea name="short_description" class="form-control"  rows="2" >{{old('name', $subject->short_description)}}</textarea>
                            </div>
                            <div class="col-lg-6">
                                <label for="">Description</label>
                                <textarea name="description" class="form-control" rows="2" >{{old('name', $subject->description)}}</textarea>
                            </div>
                            <div class="col-lg-12 mt-3"> 
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
    function previewImage() {
        var file = document.getElementById("image").files;
        if (file.length > 0) {
            var fileReader = new FileReader();

            fileReader.onload = function(event) {
                document.getElementById("imagePreview").setAttribute("src", event.target.result);
                document.getElementById("imagePreview").style.display = "block";
            };

            fileReader.readAsDataURL(file[0]);
        }
    }
</script>
@endpush