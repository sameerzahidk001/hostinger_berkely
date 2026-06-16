@extends('admin.layout.app')
@section('title', 'Subjects')

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Subject</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            <li>
                <a>Categories</a>
            </li>
            <li class="active">
                <strong>Add</strong>
            </li>
        </ol>
    </div>
    <div class="col-lg-2">

    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="wrapper wrapper-content">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Add Category</h5>
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
                    <form action="{{ route('subject.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="form-group"><label>Subject Title</label> 
                            <input class="form-control" placeholder="Subject Name" type="text" name="name" value="{{old('name')}}">
                        </div>
                        <div class="form-group"><label>Thumbnail</label> 
                            <input type="file" name="image" class="form-control" id="image" onchange="previewImage();" value="{{old('image')}}">
                        </div>
                        <div class="form-group"><label>Preview</label> 
                            <img id="imagePreview" style="max-width: 100px; max-height: 100px; display: none;">
                        </div>
                        <div class="form-group"><label>Short Description</label> 
                            <textarea name="short_description" class="form-control" placeholder="Short Description" rows="1" value="{{old('short_description')}}"></textarea>
                        </div>
                        <div class="form-group"><label>Description</label> 
                            <textarea name="description" class="form-control" placeholder="Detailed Description" rows="2" value="{{old('description')}}"></textarea>
                        </div>
                        <div style="padding-bottom: 24px;">
                            <button type="submit" class="btn btn-sm btn-primary  pull-right m-t-n-xs" style="margin-left: 6px;"><strong>Submit</strong></button>
                            <button  type="reset" class="btn btn-sm btn-danger pull-right m-t-n-xs"><strong>Reset</strong></button>
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