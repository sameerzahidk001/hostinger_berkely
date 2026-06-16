@extends('admin.layout.app')
@section('title', 'Subjects')
@push('style')
<style>
    .page-heading{
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
    }
    .col{
        flex: 1;
    }
</style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col">
        <h2>Roles & Permissions</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ route('admin.home') }}">Home</a>
            </li>
            <li>
                <a href="{{ route('admin.rolesPermissions.index') }}">Roles & Permissions</a>
            </li>
            <li class="active">
                <strong>Create</strong>
            </li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                <div class="ibox-title">
                    <h5>Create</h5>
                </div>

                <div class="ibox-content">

                    <form role="form" action="{{ route('admin.rolesPermissions.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-lg-12" style="margin-bottom: 15px;">
                                <label for="">Name</label>
                                <input class="form-control" placeholder="Add Role Name" type="text" name="name" value="{{ old('name') }}">
                                @error('name')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-12" style="margin-bottom: 15px;">
                                <label for="description">Description</label>
                                <textarea name="description" id="description" class="form-control" maxlength="100">{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-12" style="margin-bottom: 15px;">
                                <input class="form-check-input" type="checkbox" id="permissionAll">
                                <label for="permissionAll">Select All</label>
                                @error('permissions')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="col-lg-12 table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Module</th>
                                            <th>Create</th>
                                            <th>Read</th>
                                            <th>Update</th>
                                            <th>Delete</th>
                                            <th>List</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $groupedPermissions = $permissions->groupBy('module');
                                        @endphp
                                        @foreach($groupedPermissions as $module => $modulePermissions)
                                            <tr>
                                                <td><strong>{{ ucfirst($module) }}</strong></td>
                            
                                                @php
                                                    $actions = ['create', 'read', 'update', 'delete', 'list'];
                                                @endphp
                            
                                                @foreach($actions as $action)
                                                    <td>
                                                        @php
                                                            // Check if the permission exists for this module and action
                                                            $permission = $modulePermissions->firstWhere('name', $module . '-' . $action);
                                                        @endphp
                                                        
                                                        @if($permission)
                                                            <input type="checkbox" class="permission-checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                                {{ in_array($permission->id, old('permissions', $rolePermissions ?? [])) ? 'checked' : '' }}>
                                                        @else
                                                            <span>-</span> <!-- Placeholder for missing permissions -->
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;text-align:right;"> 
                                <button class="btn btn-primary " type="submit"><i class="fa fa-check"></i>&nbsp;Submit</button>
                                <a href="{{ route('admin.rolesPermissions.index') }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i>&nbsp;Cancel</a>
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
        // When "Select All" is checked, check all checkboxes
        $('#permissionAll').change(function () {
            $('.permission-checkbox').prop('checked', $(this).prop('checked'));
        });

        // If any checkbox is unchecked, uncheck "Select All"
        $('.permission-checkbox').change(function () {
            if ($('.permission-checkbox:checked').length === $('.permission-checkbox').length) {
                $('#permissionAll').prop('checked', true);
            } else {
                $('#permissionAll').prop('checked', false);
            }
        });
    });
</script>
@endpush