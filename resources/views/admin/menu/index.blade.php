@extends('admin.layout.app')

@section('title', 'Menu Management')

@section('content')
    <!-- menu-edit-v4 -->
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
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @php
                        $submenuCount = $menus->filter(function ($m) { return $m->parent_id; })->count();
                        $childCounts = $menus->filter(function ($m) { return $m->parent_id; })->groupBy('parent_id')->map->count();
                    @endphp
                    <p class="text-muted">{{ $menus->count() }} items ({{ $submenuCount }} submenu{{ $submenuCount === 1 ? '' : 's' }}). Website dropdown items (for example School of Law, School of Banking) are the <strong>Submenu</strong> rows under their parent — for those schools that parent is <strong>Academies</strong>, not Online School.</p>
                    <div class="form-group">
                        <input type="search" id="lms-menu-filter" class="form-control" placeholder="Search name, parent, or link (try Academies or School of)">
                    </div>
                    <div class="table-responsive" style="overflow:visible;">
                    <table class="table table-striped table-bordered table-hover" id="lms-menu-list">
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
                            @forelse ($menus as $menu)
                                <tr class="{{ $menu->parent_id ? 'lms-menu-child' : 'lms-menu-parent' }}">
                                    <td>{{ ucfirst($menu->menu_group) }}</td>
                                    <td>
                                        @if($menu->parent_id)
                                            <span style="display:inline-block;padding-left:18px;">↳ {{ $menu->name }}</span>
                                            <span class="label label-default">Submenu of {{ optional($menu->parent)->name ?: 'parent' }}</span>
                                        @else
                                            <strong>{{ $menu->name }}</strong>
                                            @if($childCounts->get($menu->id))
                                                <span class="label label-primary">{{ $childCounts->get($menu->id) }} submenus</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>{{ $menu->link }}</td>
                                    <td>{{ $menu->menu_order }}</td>
                                    <td>{{ $menu->parent ? $menu->parent->name : 'None' }}</td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm js-open-menu-edit"
                                            data-modal="#editMenuModal-{{ $menu->id }}">Edit</button>
                                        <form id="delete-form-{{ $menu->id }}" action="{{ route('menu.destroy', $menu->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn-danger btn btn-xs" onclick="confirmDelete({{ $menu->id }})">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">No menu items yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach ($menus as $menu)
                                <div class="modal fade" id="editMenuModal-{{ $menu->id }}" tabindex="-1" role="dialog"
                                    aria-labelledby="editMenuModalLabel-{{ $menu->id }}" aria-hidden="true">
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

                                                    <p class="text-muted">Change the name or URL, then click <strong>Update Menu</strong>.</p>
                                                    <div class="form-group">
                                                        <label for="custom_name_{{ $menu->id }}">Menu Name</label>
                                                        <input type="text" name="custom_name"
                                                            id="custom_name_{{ $menu->id }}" class="form-control"
                                                            value="{{ $menu->name }}">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="custom_url_{{ $menu->id }}">URL</label>
                                                        <input type="text" name="custom_url" id="custom_url_{{ $menu->id }}"
                                                            class="form-control" value="{{ $menu->link }}">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="pages_{{ $menu->id }}">Or pick an existing page</label>
                                                        <select class="form-control js-edit-page"
                                                            id="pages_{{ $menu->id }}"
                                                            data-name="#custom_name_{{ $menu->id }}"
                                                            data-url="#custom_url_{{ $menu->id }}">
                                                            <option value="">Keep current name and URL</option>
                                                            @foreach ($pages as $page)
                                                                <option value="{{ $page->id }}"
                                                                    data-name="{{ $page->page_name }}"
                                                                    data-link="{{ url('/' . ltrim((string) ($page->url ?? $page->page_link ?? ''), '/')) }}">
                                                                    {{ $page->page_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <!-- Parent Menu Selection -->
                                                    <div class="form-group">
                                                        <label for="parent_id_{{ $menu->id }}">Parent Menu (Optional)</label>
                                                        <select name="parent_id" id="parent_id_{{ $menu->id }}"
                                                            class="form-control">
                                                            <option value="">None (Will act as its own parent)</option>
                                                            @foreach ($menus as $parent)
                                                                @if($parent->id !== $menu->id)
                                                                <option value="{{ $parent->id }}" {{ $menu->parent_id == $parent->id ? 'selected' : '' }}>
                                                                    {{ $parent->parent_id ? (optional($parent->parent)->name . ' → ' . $parent->name) : $parent->name }}
                                                                </option>
                                                                @endif
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
                            <button type="button" class="btn btn-success btn-sm js-show-section" data-section="#existingPages">Add Existing Page</button>
                            <button type="button" class="btn btn-info btn-sm js-show-section" data-section="#customMenu">Add Custom Menu</button>
                        </div>

                        <!-- Existing Pages Section -->
                        <div id="existingPages" class="menu-section hidden">
                            <h6>Add Menus from Existing Pages</h6>
                            <div class="form-group">
                                <label for="pages">Select Page</label>
                                <select class="form-control" id="pages">
                                    <option value="">None</option>
                                    @foreach ($pages as $page)
                                        <option value="{{ $page->id }}" data-link="{{ $page->url }}" data-name="{{ $page->page_name }}">
                                            {{ $page->page_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="page_id" id="selected_page_id" value="">
                                <input type="hidden" name="page_link" id="selected_page_link" value="">
                            </div>


                            <div class="form-group">
                                <label for="page_name">Page Name</label>
                                <input type="text" class="form-control" name="page_name" id="page_name"
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
                                    <option value="{{ $menu->id }}">
                                        {{ ucfirst($menu->menu_group) }} — {{ $menu->parent_id ? (optional($menu->parent)->name . ' → ' . $menu->name) : $menu->name }}
                                    </option>
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
    <!-- Page-Level Scripts -->
    <script>
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
            var filter = document.getElementById('lms-menu-filter');
            var table = document.getElementById('lms-menu-list');
            if (filter && table) {
                filter.addEventListener('input', function () {
                    var q = (this.value || '').toLowerCase();
                    table.querySelectorAll('tbody tr').forEach(function (row) {
                        row.style.display = !q || (row.textContent || '').toLowerCase().indexOf(q) !== -1 ? '' : 'none';
                    });
                });
            }
            document.querySelectorAll('.js-edit-page').forEach(function (sel) {
                sel.addEventListener('change', function () {
                    var opt = this.options[this.selectedIndex];
                    if (!opt || !this.value) return;
                    var nameEl = document.querySelector(this.getAttribute('data-name'));
                    var urlEl = document.querySelector(this.getAttribute('data-url'));
                    if (nameEl && opt.getAttribute('data-name')) nameEl.value = opt.getAttribute('data-name');
                    if (urlEl && opt.getAttribute('data-link')) urlEl.value = opt.getAttribute('data-link');
                });
            });
            if (window.jQuery) {
                $(document).on('click', '.js-open-menu-edit', function (e) {
                    e.preventDefault();
                    var sel = $(this).attr('data-modal');
                    var $m = $(sel);
                    if (!$m.length) return;
                    $m.attr('aria-hidden', 'false').removeAttr('inert');
                    $m.modal('show');
                });
                $(document).on('show.bs.modal shown.bs.modal', '.modal', function () {
                    this.setAttribute('aria-hidden', 'false');
                    this.removeAttribute('inert');
                });
                $(document).on('hide.bs.modal', '.modal', function () {
                    if (this.contains(document.activeElement)) {
                        document.activeElement.blur();
                    }
                });
            }
            document.addEventListener('click', function (e) {
                var button = e.target && e.target.closest ? e.target.closest('.js-show-section') : null;
                if (!button) return;
                e.preventDefault();
                e.stopPropagation();
                var sel = button.getAttribute('data-section');
                var target = sel ? document.querySelector(sel) : null;
                if (!target) return;
                target.classList.remove('hidden');
                if (typeof target.scrollIntoView === 'function') {
                    target.scrollIntoView({ block: 'nearest' });
                }
            });
            // Handle menu section toggling
            document.querySelectorAll('.menu-toggle').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.querySelectorAll('.menu-section').forEach(function (section) {
                        section.classList.add('hidden');
                    });

                    const target = document.querySelector(this.getAttribute('data-target') || this.getAttribute('data-section'));
                    if (target) target.classList.remove('hidden');
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
            const pageLinkInput = document.getElementById('selected_page_link');

            if (pagesSelect && pageNameInput) {
                pagesSelect.addEventListener('change', function () {
                    const selectedOption = this.options[this.selectedIndex];
                    pageNameInput.value = selectedOption.getAttribute('data-name') || '';
                    if (pageLinkInput) {
                        pageLinkInput.value = selectedOption.getAttribute('data-link') || '';
                    }
                });
            }
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