@extends('admin.layout.app')
@section('title', 'Fee')

@section('content')
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-10">
            <h2>Fee Package</h2>
            <ol class="breadcrumb">
                <li>
                    <a href="index.html">Home</a>
                </li>
                <li>
                    <a>Courses</a>
                </li>
                <li class="active">
                    <strong>{{ $course->title }}</strong>
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
                        <h5>Add Fee Package</h5>
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
                        <form action="{{ route('course.store-fee', ['id' => $course->id]) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-lg-6" style="margin-top:6px;">
                                        <label>Package Name</label>
                                        <input class="form-control" placeholder="Name" type="text" name="package_name"
                                            value="{{ old('package_name') }}">
                                        @error('package_name')
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6" style="margin-top:6px;">
                                        <label>Currency</label>
                                        <select name="currency" class="form-control">
                                            @foreach($currencies as $currency)
                                                <option value="{{ $currency->code }}" {{ old('currency') == $currency->code ? 'selected' : '' }}>{{$currency->name}}</option>
                                            @endforeach
                                        </select>
                                        @error('currency')
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4" style="margin-top:6px;">
                                        <label>Price</label>
                                        <input class="form-control" placeholder="1000" type="number" name="price"
                                            value="{{ old('price') }}">
                                        @error('price')
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4" style="margin-top:6px;">
                                        <label>Discount Amount</label>
                                        <input class="form-control" placeholder="120" type="number" name="discount_amount"
                                            value="{{ old('discount_amount') }}">
                                        @error('discount_amount')
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4" style="margin-top:6px;">
                                        <label>Tax (%)</label>
                                        <input class="form-control" placeholder="1-100" type="number"
                                            name="tax_percentage" value="{{ old('tax_percentage') }}">
                                        @error('tax_percentage')
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="col-lg-12" style="margin-top:6px;">
                                        <label>Short Description</label>
                                        <textarea class="form-control" name="short_description" id="short_description"
                                            placeholder="Write something here...">{{ old('tax_percentage') }}</textarea>
                                        @error('tax_percentage')
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6" style="margin-top:6px;">
                                        <label>Key Point (Most popular, Sell faster etc)</label>
                                        <input class="form-control" placeholder="Enter Key points seperated with ," type="text"
                                            name="key_point" value="{{ old('key_point') }}">
                                        @error('key_point')
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6" style="margin-top:6px;">
                                        <label>Includes (Everything in Core and Premium plus:)</label>
                                        <input class="form-control" placeholder="package includes" type="text"
                                            name="package_includes" value="{{ old('package_includes') }}">
                                        @error('package_includes')
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6" style="margin-top:6px;">
                                        <label>Priority</label>
                                        <select name="priority" class="form-control">
                                            <option value="1" {{ old('currency') == '1' ? 'selected' : '' }}>One</option>
                                            <option value="2" {{ old('currency') == '2' ? 'selected' : '' }}>Two</option>
                                            <option value="3" {{ old('currency') == '3' ? 'selected' : '' }}>Three</option>
                                            <option value="4" {{ old('currency') == '4' ? 'selected' : '' }}>Four</option>
                                            <option value="5" {{ old('currency') == '5' ? 'selected' : '' }}>Five</option>
                                            <option value="6" {{ old('currency') == '6' ? 'selected' : '' }}>Six</option>
                                            <option value="7" {{ old('currency') == '7' ? 'selected' : '' }}>Seven</option>
                                            <option value="8" {{ old('currency') == '8' ? 'selected' : '' }}>Eight</option>
                                            <option value="9" {{ old('currency') == '9' ? 'selected' : '' }}>Nine</option>
                                            <option value="10" {{ old('currency') == '10' ? 'selected' : '' }}>Ten</option>
                                            <option value="11" {{ old('currency') == '11' ? 'selected' : '' }}>Eleven</option>
                                            <option value="12" {{ old('currency') == '12' ? 'selected' : '' }}>Twelve</option>
                                            <option value="13" {{ old('currency') == '13' ? 'selected' : '' }}>Thirteen
                                            </option>
                                            <option value="14" {{ old('currency') == '14' ? 'selected' : '' }}>Fourteen
                                            </option>
                                            <option value="15" {{ old('currency') == '15' ? 'selected' : '' }}>Fifteen
                                            </option>
                                            <option value="16" {{ old('currency') == '16' ? 'selected' : '' }}>Sixteen
                                            </option>
                                            <option value="17" {{ old('currency') == '17' ? 'selected' : '' }}>Seventeen
                                            </option>
                                            <option value="18" {{ old('currency') == '18' ? 'selected' : '' }}>Eighteen
                                            </option>
                                            <option value="19" {{ old('currency') == '19' ? 'selected' : '' }}>Nineteen
                                            </option>
                                            <option value="20" {{ old('currency') == '20' ? 'selected' : '' }}>Twenty</option>
                                        </select>
                                        @error('priority')
                                            <p class="text-danger text-xs italic">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6" style="height:100px; display: table;">
                                        <div style="display: table-cell; vertical-align: middle;">
                                            <label class="checkbox-inline">
                                                <input type="checkbox" name="Installments" value="1"> Enable Installments
                                            </label>
                                            <label class="checkbox-inline">
                                                <input type="checkbox" name="showonwebsite" value="1"> Show on Website
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12 feature-wrapper">
                                        <div class="form-group">
                                            <div style="overflow: hidden; margin-bottom: 5px;">
                                                <label style="float: left; margin-top: 6px;">Features (360° virtual
                                                    tour)</label>
                                                <button type="button" id="add-feature"
                                                    class="btn btn-primary btn-sm pull-right">
                                                    <strong>Add Feature</strong>
                                                </button>
                                            </div>

                                            <div class="feature-input-wrapper input-group" style="margin-bottom: 10px;">
                                                <input type="text" name="package_feature[]" class="form-control"
                                                    placeholder="Add package feature">
                                                <span class="input-group-btn">
                                                    <button type="button"
                                                        class="btn btn-danger btn-sm remove-feature">Delete</button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div style="padding-top: 25px;">
                                    <button type="submit" class="btn btn-sm btn-primary pull-right m-t-n-xs"
                                        style="margin-left: 6px;"><strong>Submit</strong></button>
                                    <button type="reset"
                                        class="btn btn-sm btn-danger pull-right m-t-n-xs"><strong>Reset</strong></button>
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
                // Define the new input field with a delete button
                var newFeature = `
                    <div class="feature-input-wrapper input-group" style="margin-bottom: 10px;">
                        <input type="text" name="package_feature[]" class="form-control"
                            placeholder="Add package feature">
                        <span class="input-group-btn">
                            <button type="button"
                                class="btn btn-danger btn-sm remove-feature">Delete</button>
                        </span>
                    </div>`;

                // Append the new input field with delete button after the existing ones
                $('.feature-input-wrapper:last').after(newFeature);
            });

            // Event delegation to handle removal of dynamically added inputs
            $(document).on('click', '.remove-feature', function () {
                $(this).closest('.feature-input-wrapper').remove();
            });
        });
    </script>
@endpush