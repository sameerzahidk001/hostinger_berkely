@extends('admin.layout.app')
@section('title', 'Edit Course Agenda')
@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-tags-input@1.3.5/dist/jquery.tagsinput.min.css">
    <style>
        .page-heading {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }

        .col {
            flex: 1;
        }

        .instruction {
            font-size: 14px;
            color: #555;
            margin-top: 5px;
        }

        #cc_tagsinput,
        #bcc_tagsinput {
            width: 100% !important;
            height: auto !important;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            min-height: auto !important;
        }

        #cc_addTag,
        #bcc_addTag {
            flex: 0 0 auto;
        }

        .tag,
        #cc_tag,
        #bcc_tag {
            margin: 0px !important;
            width: auto !important;
        }

        .tags_clear {
            display: none !important;
        }
    </style>
@endpush
@section('content')
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col">
            <h2>Training Calendar</h2>
            <ol class="breadcrumb">
                <li>
                    <a href="{{ route('admin.home') }}">Home</a>
                </li>
                <li>
                    <a href="{{ route('admin.course-agendas.index') }}">Training Calendar</a>
                </li>
                <li class="active">
                    <strong>Edit</strong>
                </li>
            </ol>
        </div>
    </div>

    <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                    <div class="ibox-title">
                        <h5>Edit</h5>
                    </div>

                    <div class="ibox-content">

                        <form role="form" action="{{ route('admin.course-agendas.update', $course_agenda->id) }}"
                            method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-lg-6" style="margin-bottom: 15px;">
                                    <label for="course">Course</label>
                                    <select name="course" id="course" class="form-control">
                                        <option value="">Select Course</option>
                                        @foreach ($courses as $course)
                                            <option value="{{ $course->id }}"
                                                {{ old('course', $course_agenda->course_id) == $course->id ? 'selected' : '' }}>
                                                {{ $course->title }}</option>
                                        @endforeach
                                    </select>
                                    @error('course')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-lg-6" style="margin-bottom: 15px;">
                                    <label for="subject">Subject</label>
                                    <input type="text" name="subject" id="subject" class="form-control"
                                        value="{{ old('subject', $course_agenda->subject) }}">
                                    @error('subject')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-lg-4" style="margin-bottom: 15px;">
                                    <label for="delivery_type">Delivery Type</label>
                                    <select name="delivery_type" id="delivery_type" class="form-control"
                                        value="{{ old('delivery_type') }}">
                                        <option value="">Virtual & Classroom</option>
                                        <option value="Virtual"
                                            {{ old('delivery_type', $course_agenda->delivery_type) == 'Virtual' ? 'selected' : '' }}>
                                            Virtual</option>
                                        <option value="In Person"
                                            {{ old('delivery_type', $course_agenda->delivery_type) == 'In Person' ? 'selected' : '' }}>
                                            In Person</option>
                                    </select>
                                    @error('delivery_type')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-lg-4" style="margin-bottom: 15px;">
                                    <label for="country">Country</label>
                                    <select name="country" id="country" class="form-control">
                                        <option value="0">International</option>
                                        @foreach ($countries as $country)
                                            <option value="{{ $country->id }}"
                                                {{ $country->id == old('city', $course_agenda->city) ? 'selected' : '' }}>
                                                {{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('country')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-lg-4" style="margin-bottom: 15px;">
                                    <label for="city">City</label>
                                    <input type="text" name="city" id="city" class="form-control"
                                        value="{{ old('city', $course_agenda->city) }}">
                                    @error('city')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-lg-6" style="margin-bottom: 15px;">
                                    <label for="from">From</label>
                                    <input type="date" name="from" id="from" class="form-control"
                                        value="{{ old('from', $course_agenda->from) }}">
                                    @error('from')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-lg-6" style="margin-bottom: 15px;">
                                    <label for="to">To</label>
                                    <input type="date" name="to" id="to" class="form-control"
                                        value="{{ old('to', $course_agenda->to) }}">
                                    @error('to')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-lg-12" style="margin-bottom: 15px;">
                                    <label for="to">Inquiry</label>
                                    <textarea name="inquiry" id="inquiry" rows="3" class="form-control">{{ old('to', $course_agenda->inquiry) }}</textarea>
                                    @error('inquiry')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Description</label>
                                    <textarea name="description" class="form-control text-editor" rows="2">{!! old('description', $course_agenda->description) !!}</textarea>
                                    @error('description')
                                        <span class="help-block text-danger">{{ $message }}</span>
                                    @enderror
                                </div>



                            </div>
                            <div class="row">
                                <div class="col-lg-12" style="margin-top: 16px;text-align:right;">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Submit</button>
                                    <a href="{{ route('admin.course-agendas.index') }}" class="btn btn-secondary"><i
                                            class="fa fa-arrow-left"></i>&nbsp;Cancel</a>
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