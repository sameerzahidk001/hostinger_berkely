@extends('admin.layout.app')

@section('title', 'Menu Management')

@push('style')
    <link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Menu List</h5>
                    <div class="ibox-tools">
                        <button class="btn btn-primary btn-xs" data-toggle="modal" data-target="#addMenuModal">Add New
                            Menus</button>
                    </div>
                </div>

                <div class="ibox-content">
                    <table class="table table-striped table-bordered table-hover dataTables-example">
                        <thead>
                            <tr>
                                <th>Menu Group</th>
                                <th>Menu Name</th>
                                <th>Link</th>
                                <th>Order</th>
                                <th>Parent Menu</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($menus as $menu)
                                <tr>
                                    <td>{{ ucfirst($menu->menu_group) }}</td>
                                    <td>{{ $menu->name }}</td>
                                    <td>{{ $menu->link }}</td>
                                    <td>{{ $menu->menu_order }}</td>
                                    <td>{{ $menu->parent ? $menu->parent->name : 'None' }}</td>
                                    <td>
                                        <button class="btn btn-info btn-sm" data-toggle="modal"
                                            data-target="#editMenuModal-{{ $menu->id }}">Edit</button>
                                        <form id="delete-form-{{ $menu->id }}" action="{{ route('menu.destroy', $menu->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn-danger btn btn-xs" onclick="confirmDelete({{ $menu->id }})">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <!-- Edit Menu Modal for each menu -->
                                <!-- Edit Menu Modal for each menu -->
                                <div class="modal fade" id="editMenuModal-{{ $menu->id }}" tabindex="-1" role="dialog"
                                    aria-labelledby="editMenuModalLabel-{{ $menu->id }}">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <form action="{{ route('menu.update', $menu->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editMenuModalLabel-{{ $menu->id }}">Edit Menu
                                                    </h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <!-- Menu Group Section -->
                                                    <div class="form-group">
                                                        <label for="menu_group_{{ $menu->id }}">Menu Group</label>
                                                        <select name="menu_group" id="menu_group_{{ $menu->id }}"
                                                            class="form-control">
                                                            @php
                                                                $uniqueMenuGroups = $menus->pluck('menu_group')->unique();
                                                            @endphp
                                                            @foreach ($uniqueMenuGroups as $group)
                                                                <option value="{{ $group }}" {{ $menu->menu_group == $group ? 'selected' : '' }}>
                                                                    {{ ucfirst($group) }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <!-- Buttons to toggle sections -->
                                                    <div class="text-center mb-3">
                                                        <button type="button" class="btn btn-success btn-sm edit-menu-toggle"
                                                            data-target="#editExistingPages-{{ $menu->id }}">Edit Existing
                                                            Page</button>
                                                        <button type="button" class="btn btn-info btn-sm edit-menu-toggle"
                                                            data-target="#editCustomMenu-{{ $menu->id }}">Edit Custom
                                                            Menu</button>
                                                    </div>

                                                    <!-- Existing Pages Section -->
                                                    <div id="editExistingPages-{{ $menu->id }}"
                                                        class="edit-menu-section {{ $menu->page_id ? '' : 'hidden' }}">
                                                        <h6>Edit Menus from Existing Pages</h6>
                                                        <div class="form-group">
                                                            <label for="pages_{{ $menu->id }}">Select Page</label>
                                                            <select class="form-control" name="page_id"
                                                                id="pages_{{ $menu->id }}">
                                                                <option value="">None</option>
                                                                @foreach ($pages as $page)
                                                                    <option value="{{ $page->id }}"
                                                                        data-link="{{ $page->page_link }}" {{ $menu->page_id == $page->id ? 'selected' : '' }}>
                                                                        {{ $page->page_name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <!-- Custom Menu Section -->
                                                    <div id="editCustomMenu-{{ $menu->id }}"
                                                        class="edit-menu-section {{ !$menu->page_id ? '' : 'hidden' }}">
                                                        <h6>Edit Custom Menu</h6>
                                                        <div class="form-group">
                                                            <label for="custom_name_{{ $menu->id }}">Custom Menu Name</label>
                                                            <input type="text" name="custom_name"
                                                                id="custom_name_{{ $menu->id }}" class="form-control"
                                                                value="{{ $menu->name }}">
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="custom_url_{{ $menu->id }}">Custom URL</label>
                                                            <input type="text" name="custom_url" id="custom_url_{{ $menu->id }}"
                                                                class="form-control" value="{{ $menu->link }}">
                                                        </div>
                                                    </div>

                                                    <!-- Parent Menu Selection -->
                                                    <div class="form-group">
                                                        <label for="parent_id_{{ $menu->id }}">Parent Menu (Optional)</label>
                                                        <select name="parent_id" id="parent_id_{{ $menu->id }}"
                                                            class="form-control">
                                                            <option value="">None (Will act as its own parent)</option>
                                                            @foreach ($menus as $parent)
                                                                <option value="{{ $parent->id }}" {{ $menu->parent_id == $parent->id ? 'selected' : '' }}>
                                                                    {{ $parent->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="form-group">
                                                        <label for="menu_order_{{ $menu->id }}">Menu Order</label>
                                                        <input type="number" name="menu_order" id="menu_order_{{ $menu->id }}"
                                                            class="form-control" value="{{ $menu->menu_order }}">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Update Menu</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Menu Modal -->
    <div class="modal fade" id="addMenuModal" tabindex="-1" role="dialog" aria-labelledby="addMenuModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('menu.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addMenuModalLabel">Add New Menus</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Menu Group Section -->
                        <div class="form-group text-center">
                            <button type="button" class="btn btn-success btn-sm group-toggle"
                                data-target="#existingGroup">Use Existing Group</button>
                            <button type="button" class="btn btn-info btn-sm group-toggle" data-target="#newGroup">Create
                                New Group</button>
                        </div>

                        <!-- Existing Menu Group Section -->
                        <div id="existingGroup" class="group-section">
                            <div class="form-group">
                                <label for="menu_group_select">Select Existing Group</label>
                                @php
                                    $uniqueMenuGroups = $menus->pluck('menu_group')->unique();
                                @endphp
                                <select id="menu_group_select" class="form-control">
                                    <option value="">Select a Menu Group</option>
                                    @foreach ($uniqueMenuGroups as $group)
                                        <option value="{{ $group }}">{{ ucfirst($group) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- New Menu Group Section (Hidden by Default) -->
                        <div id="newGroup" class="group-section hidden">
                            <div class="form-group">
                                <label for="menu_group">New Menu Group</label>
                                <input type="text" name="menu_group" id="menu_group" class="form-control"
                                    placeholder="Enter new menu group">
                            </div>
                        </div>


                        <!-- Buttons to toggle sections -->
                        <div class="text-center mb-3">
                            <button type="button" class="btn btn-success btn-sm menu-toggle"
                                data-target="#existingPages">Add Existing Page</button>
                            <button type="button" class="btn btn-info btn-sm menu-toggle" data-target="#customMenu">Add
                                Custom Menu</button>
                        </div>

                        <!-- Existing Pages Section -->
                        <div id="existingPages" class="menu-section hidden">
                            <h6>Add Menus from Existing Pages</h6>
                            <div class="form-group">
                                <label for="pages">Select Page</label>
                                <select class="form-control" id="pages">
                                    <option value="">None</option>
                                    @foreach ($pages as $page)
                                        <option value="{{ $page->id }}" data-link="{{ $page->url }}">
                                            {{ $page->page_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="page_id" id="selected_page_id" value="">
                                <input type="hidden" name="page_link" id="selected_page_link" value="">
                            </div>


                            <div class="form-group">
                                <label for="page_name">Page Name</label>
                                <input type="text" class="form-control" name="page_name"
                                    placeholder="Page name will appear here">
                            </div>
                        </div>

                        <!-- Custom Menu Section -->
                        <div id="customMenu" class="menu-section hidden">
                            <h6>Add Custom Menu</h6>
                            <div class="form-group">
                                <label for="custom_name">Custom Menu Name</label>
                                <input type="text" name="custom_name" id="custom_name" class="form-control"
                                    placeholder="Enter custom menu name">
                            </div>
                            <div class="form-group">
                                <label for="custom_url">Custom URL</label>
                                <input type="text" name="custom_url" id="custom_url" class="form-control"
                                    placeholder="Enter custom URL">
                            </div>
                        </div>

                        <!-- Parent Menu Selection -->
                        <div class="form-group">
                            <label for="parent_id">Parent Menu (Optional)</label>
                            <select name="parent_id" id="parent_id" class="form-control">
                                <option value="">None (Will act as its own parent)</option>
                                @foreach ($menus as $menu)
                                    <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                                @endforeach
                            </select>
                        </div>


                        <div class="form-group">
                            <label for="menu_order">Menu Order</label>
                            <input type="number" name="menu_order" id="menu_order" class="form-control"
                                placeholder="Enter menu order">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Menu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>
    <!-- Page-Level Scripts -->
    <script>
        $(document).ready(function () {
            $('.dataTables-example').DataTable({
                pageLength: 10,
                searching: true,
                lengthChange: true,
                paging: true,
                info: false,
                ordering: true,
                responsive: true,
                dom: 'lftip'
            });
        });
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
        document.addEventListener('DOMContentLoaded', function () {
            // Handle menu section toggling
            document.querySelectorAll('.menu-toggle').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.querySelectorAll('.menu-section').forEach(function (section) {
                        section.classList.add('hidden');
                    });

                    const target = document.querySelector(this.getAttribute('data-target'));
                    target.classList.remove('hidden');
                });
            });

            // Handle group section toggling
            document.querySelectorAll('.group-toggle').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.querySelectorAll('.group-section').forEach(function (section) {
                        section.classList.add('hidden');
                    });

                    const target = document.querySelector(this.getAttribute('data-target'));
                    target.classList.remove('hidden');
                });
            });

            // Pre-fill the menu group input if an existing group is selected
            const menuGroupSelect = document.getElementById('menu_group_select');
            const menuGroupInput = document.getElementById('menu_group');

            menuGroupSelect.addEventListener('change', function () {
                if (this.value) {
                    menuGroupInput.value = this.value;
                } else {
                    menuGroupInput.value = '';
                }
            });

            // Handle existing page selection
            const pagesSelect = document.getElementById('pages');
            const pageNameInput = document.getElementById('page_name');
            const pageLinkInput = document.getElementById('page_link');

            pagesSelect.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                pageNameInput.value = selectedOption.getAttribute('data-name') || '';
                pageLinkInput.value = selectedOption.getAttribute('data-link') || '';
            });
        });
        document.addEventListener('DOMContentLoaded', function () {
            const pageDropdown = document.getElementById('pages');
            const hiddenPageId = document.getElementById('selected_page_id');
            const hiddenPageLink = document.getElementById('selected_page_link');

            pageDropdown.addEventListener('change', function () {
                hiddenPageId.value = this.value; // Update hidden input for page_id
                hiddenPageLink.value = this.selectedOptions[0].getAttribute('data-link'); // Update hidden input for page_link
            });
        });
    </script>
@endpush