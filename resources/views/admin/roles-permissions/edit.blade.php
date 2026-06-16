@extends('admin.layout.app')
@section('title', 'Edit Role')
@push('style')
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
                    <h5>Edit Role</h5>
                </div>
                <div class="ibox-content">
                    <form role="form" action="{{ route('admin.rolesPermissions.update', $role->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Role Name -->
                            <div class="col-lg-12" style="margin-bottom: 15px;">
                                <label for="">Name</label>
                                <input class="form-control" placeholder="Role Name" type="text" name="name" value="{{ old('name', $role->name) }}">
                                @error('name')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-lg-12" style="margin-bottom: 15px;">
                                <label for="description">Description</label>
                                <textarea name="description" id="description" class="form-control" maxlength="100">{{ old('description', $role->description) }}</textarea>
                                @error('description')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Select All Permissions -->
                            <div class="col-lg-12" style="margin-bottom: 15px;">
                                <input class="form-check-input" type="checkbox" id="permissionAll">
                                <label for="permissionAll">Select All</label>
                                @error('permissions')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Permissions Table -->
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
                                                                {{ in_array($permission->id, old('permissions', $rolePermissions ?? $role->permissions->pluck('id')->toArray())) ? 'checked' : '' }}>
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

                        <!-- Submit & Reset Buttons -->
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px; text-align:right;"> 
                                <button class="btn btn-primary" type="submit"><i class="fa fa-check"></i>&nbsp;Update</button>
                                <a href="{{ route('admin.rolesPermissions.index') }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i>&nbsp;Cancel</a>
                            </div>
                        </div>
                    </form>
                </div> <!-- End ibox-content -->
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
    $(document).ready(function () {
        // Check if all checkboxes are already selected
        function checkAllSelected() {
            $('#permissionAll').prop('checked', $('.permission-checkbox:checked').length === $('.permission-checkbox').length);
        }
        
        checkAllSelected(); // Check on page load

        // Select all permissions when "Select All" is checked
        $('#permissionAll').change(function () {
            $('.permission-checkbox').prop('checked', $(this).prop('checked'));
        });

        // If any checkbox is unchecked, uncheck "Select All"
        $('.permission-checkbox').change(function () {
            checkAllSelected();
        });
    });
</script>
@endpush