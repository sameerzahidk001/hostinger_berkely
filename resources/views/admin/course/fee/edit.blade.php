@extends('admin.layout.app')
@section('title', 'Edit Fee')

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Fee Package</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a>Courses</a></li>
            <li class="active"><strong>{{ $course->title }}</strong></li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="wrapper wrapper-content">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Edit Fee Package</h5>
                </div>
                <div class="ibox-content">
                    <form action="{{ route('course.update-fee', ['id' => $course_fee->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <div class="row">
                                <div class="col-lg-6">
                                    <label>Package Name</label>
                                    <input class="form-control" name="package_name" value="{{ old('package_name', $course_fee->package_name) }}" placeholder="Name" type="text">
                                    @error('package_name') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-lg-6">
                                    <label>Currency</label>
                                    <select name="currency" class="form-control">
                                        @foreach($currencies as $currency)
                                            <option value="{{ $currency->code }}" {{ old('currency', $course_fee->currency) == $currency->code ? 'selected' : '' }}>{{ $currency->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('currency') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-lg-4">
                                    <label>Price</label>
                                    <input class="form-control" name="price" value="{{ old('price', $course_fee->price) }}" placeholder="1000" type="number">
                                    @error('price') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-lg-4">
                                    <label>Discount Amount</label>
                                    <input class="form-control" name="discount_amount" value="{{ old('discount_amount', $course_fee->discount_amount) }}" placeholder="120" type="number">
                                    @error('discount_amount') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-lg-4">
                                    <label>Tax (%)</label>
                                    <input class="form-control" name="tax_percentage" value="{{ old('tax_percentage', $course_fee->tax_percentage) }}" placeholder="0–100" type="number">
                                    @error('tax_percentage') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-lg-12">
                                    <label>Short Description</label>
                                    <textarea class="form-control" name="short_description" placeholder="Write something...">{{ old('short_description', $course_fee->short_description) }}</textarea>
                                    @error('short_description') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-lg-6">
                                    <label>Key Point</label>
                                    <input class="form-control" name="key_point" value="{{ old('key_point', $course_fee->key_point) }}" placeholder="Most popular, Sell faster, etc." type="text">
                                    @error('key_point') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-lg-6">
                                    <label>Includes</label>
                                    <input class="form-control" name="package_includes" value="{{ old('package_includes', $course_fee->package_includes) }}" placeholder="e.g., Everything in Core & Premium" type="text">
                                    @error('package_includes') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-lg-6">
                                    <label>Priority</label>
                                    <select name="priority" class="form-control">
                                        @for ($i = 1; $i <= 20; $i++)
                                            <option value="{{ $i }}" {{ old('priority', $course_fee->priority) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                    @error('priority') <p class="text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-lg-6" style="height:100px; display: table;">
                                    <div style="display: table-cell; vertical-align: middle;">
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="Installments" value="1" {{ old('Installments', $course_fee->Installments) ? 'checked' : '' }}> Enable Installments
                                        </label>
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="showonwebsite" value="1" {{ old('showonwebsite', $course_fee->showonwebsite) ? 'checked' : '' }}> Show on Website
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12 feature-wrapper">
                                    <div class="form-group">
                                        <div style="overflow: hidden; margin-bottom: 5px;">
                                            <label style="float: left; margin-top: 6px;">Features (360° virtual tour)</label>
                                            <button type="button" id="add-feature" class="btn btn-primary btn-sm pull-right">
                                                <strong>Add Feature</strong>
                                            </button>
                                        </div>

                                        @forelse(old('package_feature', $course_fee->package_feature ?? []) as $feature)
                                            <div class="feature-input-wrapper input-group" style="margin-bottom: 10px;">
                                                <input type="text" name="package_feature[]" class="form-control" value="{{ $feature }}" placeholder="Add package feature">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-danger btn-sm remove-feature">Delete</button>
                                                </span>
                                            </div>
                                        @empty
                                            <div class="feature-input-wrapper input-group" style="margin-bottom: 10px;">
                                                <input type="text" name="package_feature[]" class="form-control" placeholder="Add package feature">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-danger btn-sm remove-feature">Delete</button>
                                                </span>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div style="padding-top: 25px;">
                                <button type="submit" class="btn btn-sm btn-primary pull-right"><strong>Update</strong></button>
                                <a href="{{ url()->previous() }}" class="btn btn-sm btn-default pull-right" style="margin-right: 6px;"><strong>Cancel</strong></a>
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
    $(document).ready(function () {
        $('#add-feature').on('click', function () {
            var newFeature = `
                <div class="feature-input-wrapper input-group" style="margin-bottom: 10px;">
                    <input type="text" name="package_feature[]" class="form-control" placeholder="Add package feature">
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-danger btn-sm remove-feature">Delete</button>
                    </span>
                </div>`;
            $('.feature-wrapper .form-group').append(newFeature);
        });

        $(document).on('click', '.remove-feature', function () {
            $(this).closest('.feature-input-wrapper').remove();
        });
    });
</script>
@endpush