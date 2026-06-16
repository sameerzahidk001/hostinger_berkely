@extends('admin.layout.app')

@section('title', 'Edit Menu')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5>Edit Menu</h5>
            </div>
            <div class="ibox-content">
                <form role="form" action="{{ route('menu.update', $menu->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <!-- Menu Name -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Menu Name</label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $menu->name) }}" required>
                                @error('name')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Link -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="link">Link</label>
                                <input type="text" name="link" id="link" class="form-control" value="{{ old('link', $menu->link) }}" required>
                                @error('link')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Menu Order -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="menu_order">Menu Order</label>
                                <input type="number" name="menu_order" id="menu_order" class="form-control" value="{{ old('menu_order', $menu->menu_order) }}">
                                @error('menu_order')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="is_active">Status</label>
                                <select name="is_active" id="is_active" class="form-control">
                                    <option value="1" {{ old('is_active', $menu->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active', $menu->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('is_active')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="row">
                        <div class="col-lg-12" style="margin-top: 16px; text-align: right;">
                            <button class="btn btn-primary" type="submit"><i class="fa fa-check"></i>&nbsp;Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
