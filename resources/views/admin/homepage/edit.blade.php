@extends('admin.layout.app')
@section('title', 'HomePage')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@push('style')
    <style>
        .section-card {
            cursor: pointer;
            transition: 0.3s;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 5px;
            text-align: center;
            background-color: #f8f9fa;
        }

        .section-card:hover,
        .section-card.active {
            background-color: #1A73E8;
            color: white;
        }

        .section-content {
            display: none;
        }

        .section-content.active {
            display: block;
        }

        .section-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .position-relative{
            position: relative;
        }
        .position-absolute{
            position: absolute;
        }
        .top-0{
            top: 0;
        }
        .right-0{
            right: 0;
        }
        .img-fluid{
            width: 100%;
            height: auto;
        }
        .object-cover{
            object-fit: contain;
        }
        .vh-50{
            height: 50vh;
        }
    </style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col">
        <h2>Home Settings</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ route('admin.home') }}">Home</a>
            </li>
            <li class="active">
                <strong>Home Settings</strong>
            </li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                <div class="ibox-title">
                    <h5>Home Settings</h5>
                </div>

                <div class="ibox-content">
                    <div class="position-relative">
                        <img src="{{ asset('images/banners/' . ($banner->image ?? 'default-bg.png')) }}" class="img-fluid vh-50 object-cover" alt="" title="">
                        <div class="position-absolute top-0 right-0 mr-3 mt-3">
                            <!-- Button trigger modal -->
                            <button type="button" class="btn btn-light" data-toggle="modal"
                                data-target="#editBannerModal">
                                Edit Banner
                            </button>

                            <!-- Modal -->
                            <div class="modal fade" id="editBannerModal" tabindex="-1" role="dialog"
                                aria-labelledby="editBannerModalLabel">
                                <div class="modal-dialog" role="document">
                                    <form class="modal-content" method="post" action="{{ route('home.banner.update') }}" enctype="multipart/form-data">
                                        @csrf
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            <h4 class="modal-title" id="editBannerModalLabel">Edit Banner</h4>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label class="control-label" for="title">Title</label>
                                                <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $banner?->title ?? 'Welcome To Berkeley') }}">
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="description">Description</label>
                                                <textarea name="description" id="description" class="form-control" maxlength="100" aria-describedby="descriptionInstruction">{{ old('description', $banner?->description ?? '') }}</textarea>
                                                <span id="descriptionInstruction" class="help-block">Max. 100 character are allowed.</span>
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="url_text">URL Text</label>
                                                <input type="text" class="form-control" id="url_text" name="url_text" value="{{ old('url_text', $banner?->url_text ?? '') }}">
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="url">URL</label>
                                                <input type="url" class="form-control" id="url" name="url" value="{{ old('url', $banner?->url ?? '') }}">
                                            </div>
                                            <div class="form-group">
                                                <label class="control-label" for="image">Image</label>
                                                <input type="file" id="image" name="image" class="form-control">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default"
                                                data-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Save changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="section-container">
                        <div class="section-card active" data-target="#hero">Hero</div>
                        <div class="section-card" data-target="#about">About</div>
                        <div class="section-card" data-target="#education">Centre for Education</div>
                        <div class="section-card" data-target="#courses">Courses</div>
                        <div class="section-card" data-target="#women">Women Entrepreneurs</div>
                        <div class="section-card" data-target="#academies">Academies</div>
                        <div class="section-card" data-target="#success">Success Stories</div>
                    </div>
                    <form action="{{ route('homepage.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div id="hero" class="section-content active">
                            <h3>Hero Section</h3>
                            <label>Title:</label>
                            <input type="text" name="sections[hero_section][title]" class="form-control"
                                value="{{ $sections['hero_section']->title ?? '' }}" required>
                            <label>Description:</label>
                            <textarea name="sections[hero_section][description]"
                                class="form-control">{{ $sections['hero_section']->description ?? '' }}</textarea>
                            <label>Link:</label>
                            <input type="text" name="sections[hero_section][link]" class="form-control"
                                value="{{ $sections['hero_section']->link ?? '' }}">
                            <label>Image:</label>
                            <input type="file" name="sections[hero_section][image]" class="form-control">
                            @if(isset($sections['hero_section']->image))
                                <img src="{{ asset($sections['hero_section']->image) }}" width="200">
                            @endif
                        </div>
                        <div id="about" class="section-content">
                            <h3>About Us</h3>
                            <label>Title:</label>
                            <input type="text" name="sections[about_us][title]" class="form-control"
                                value="{{ $sections['about_us']->title ?? '' }}" required>
                            <label>Description:</label>
                            <textarea name="sections[about_us][description]"
                                class="form-control">{{ $sections['about_us']->description ?? '' }}</textarea>
                            <label>Link:</label>
                            <input type="text" name="sections[about_us][link]" class="form-control"
                                value="{{ $sections['about_us']->link ?? '' }}">
                            <label>Image:</label>
                            <input type="file" name="sections[about_us][image]" class="form-control">
                            @if(isset($sections['about_us']->image))
                                <img src="{{ asset($sections['about_us']->image) }}" width="200">
                            @endif
                        </div>
                        <div id="education" class="section-content">
                            {{-- Centre for Education --}}
                            <div class="form-group">
                                <h3>Centre for Education</h3>
                                <label>Title:</label>
                                <input type="text" name="sections[centre_for_education][title]" class="form-control"
                                    value="{{ $sections['centre_for_education']->title ?? '' }}" required>

                                <label>Description:</label>
                                <textarea name="sections[centre_for_education][description]"
                                    class="form-control">{{ $sections['centre_for_education']->description ?? '' }}</textarea>

                                <label>Link:</label>
                                <input type="text" name="sections[centre_for_education][link]" class="form-control"
                                    value="{{ $sections['centre_for_education']->link ?? '' }}">

                                <label>Image:</label>
                                <input type="file" name="sections[centre_for_education][image]" class="form-control">
                                @if(isset($sections['centre_for_education']->image))
                                    <img src="{{ asset($sections['centre_for_education']->image) }}" width="200">
                                @endif
                            </div>
                        </div>
                        <div id="courses" class="section-content">
                            {{-- Courses Section --}}
                            <h3>Courses</h3>
                            @foreach(['diplomas', 'graduate_courses', 'master_courses'] as $course)
                                <div class="form-group">
                                    <h4>{{ ucfirst(str_replace('_', ' ', $course)) }}</h4>
                                    <label>Title:</label>
                                    <input type="text" name="sections[{{ $course }}][title]" class="form-control"
                                        value="{{ $sections[$course]->title ?? '' }}" required>

                                    <label>Description:</label>
                                    <textarea name="sections[{{ $course }}][description]"
                                        class="form-control">{{ $sections[$course]->description ?? '' }}</textarea>

                                    <label>Link:</label>
                                    <input type="text" name="sections[{{ $course }}][link]" class="form-control"
                                        value="{{ $sections[$course]->link ?? '' }}">

                                    <label>Image:</label>
                                    <input type="file" name="sections[{{ $course }}][image]" class="form-control">
                                    @if(isset($sections[$course]->image))
                                        <img src="{{ asset($sections[$course]->image) }}" width="200">
                                    @endif
                                </div>
                                <hr>
                            @endforeach
                        </div>
                        <div id="women" class="section-content">
                            {{-- Women Entrepreneurs Support Centre --}}
                            <div class="form-group">
                                <h3>Women Entrepreneurs Support Centre</h3>
                                <label>Title:</label>
                                <input type="text" name="sections[women_entrepreneurs][title]" class="form-control"
                                    value="{{ $sections['women_entrepreneurs']->title ?? '' }}" required>

                                <label>Description:</label>
                                <textarea name="sections[women_entrepreneurs][description]"
                                    class="form-control">{{ $sections['women_entrepreneurs']->description ?? '' }}</textarea>

                                <label>Link:</label>
                                <input type="text" name="sections[women_entrepreneurs][link]" class="form-control"
                                    value="{{ $sections['women_entrepreneurs']->link ?? '' }}">

                                <label>Image:</label>
                                <input type="file" name="sections[women_entrepreneurs][image]" class="form-control">
                                @if(isset($sections['women_entrepreneurs']->image))
                                    <img src="{{ asset($sections['women_entrepreneurs']->image) }}" width="200">
                                @endif
                            </div>
                        </div>
                        <div id="academies" class="section-content">
                            {{-- Academies Section --}}
                            <h3>Academies</h3>
                            @foreach(['cfo_academy', 'ceo_academy', 'entrepreneurs_academy'] as $academy)
                                <div class="form-group">
                                    <h4>{{ ucfirst(str_replace('_', ' ', $academy)) }}</h4>
                                    <label>Title:</label>
                                    <input type="text" name="sections[{{ $academy }}][title]" class="form-control"
                                        value="{{ $sections[$academy]->title ?? '' }}" required>

                                    <label>Description:</label>
                                    <textarea name="sections[{{ $academy }}][description]"
                                        class="form-control">{{ $sections[$academy]->description ?? '' }}</textarea>

                                    <label>Link:</label>
                                    <input type="text" name="sections[{{ $academy }}][link]" class="form-control"
                                        value="{{ $sections[$academy]->link ?? '' }}">

                                    <label>Image:</label>
                                    <input type="file" name="sections[{{ $academy }}][image]" class="form-control">
                                    @if(isset($sections[$academy]->image))
                                        <img src="{{ asset($sections[$academy]->image) }}" width="200">
                                    @endif
                                </div>
                                <hr>
                            @endforeach
                        </div>
                        <div id="success" class="section-content">
                            {{-- Success Stories --}}
                            <div class="form-group">
                                <h3>Success Stories</h3>
                                <label>Title:</label>
                                <input type="text" name="sections[success_stories][title]" class="form-control"
                                    value="{{ $sections['success_stories']->title ?? '' }}" required>

                                <label>Description:</label>
                                <textarea name="sections[success_stories][description]"
                                    class="form-control">{{ $sections['success_stories']->description ?? '' }}</textarea>

                                <label>Link:</label>
                                <input type="text" name="sections[success_stories][link]" class="form-control"
                                    value="{{ $sections['success_stories']->link ?? '' }}">
                            </div>
                        </div>
                        <div class="text-center" style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary">Update Homepage</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".section-card").forEach(function (card) {
                card.addEventListener("click", function () {
                    document.querySelectorAll(".section-card").forEach(function (c) {
                        c.classList.remove("active");
                    });
                    card.classList.add("active");
                    document.querySelectorAll(".section-content").forEach(function (section) {
                        section.classList.remove("active");
                    });
                    document.querySelector(card.getAttribute("data-target")).classList.add("active");
                });
            });
        });
    </script>
@endsection
