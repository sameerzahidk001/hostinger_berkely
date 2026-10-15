@extends('admin.layout.app')
@section('title', 'Fee Update in Page')

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Fee Update in Page — {{ $page->page_name }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('pages.index') }}">Pages</a></li>
            <li><a href="{{ route('pages.edit', $page->id) }}">Edit Page</a></li>
            <li class="active"><strong>Fees</strong></li>
        </ol>
    </div>
    <div class="col-lg-2" style="padding-top:28px;text-align:right;">
        <a href="{{ route('pages.edit', $page->id) }}" class="btn btn-default btn-sm">Back to Page</a>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="alert alert-info">
        Build fees as <strong>Service → Category → Fee Package</strong> on this CMS page (no Course required).
        Visible packages render on the public page automatically.
    </div>

    <div class="ibox">
        <div class="ibox-title"><h5>Public heading</h5></div>
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.pages.fees.heading', $page->id) }}" class="form-inline">
                @csrf
                <input type="text" name="fee_heading" class="form-control m-r-sm" style="min-width:320px;"
                       value="{{ old('fee_heading', $page->fee_heading ?? 'Fee Update in Page') }}"
                       placeholder="FEE UPDATE IN PAGE">
                <button class="btn btn-primary" type="submit">Save Heading</button>
            </form>
        </div>
    </div>

    <div class="ibox">
        <div class="ibox-title"><h5>Add Service</h5></div>
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.pages.fees.services.store', $page->id) }}" class="form-inline">
                @csrf
                <input type="text" name="name" class="form-control m-r-sm" placeholder="Service name (e.g. Student Visa)" required>
                <input type="number" name="sort_order" class="form-control m-r-sm" placeholder="Order" value="0" style="width:90px;">
                <select name="is_visible" class="form-control m-r-sm">
                    <option value="1">Show</option>
                    <option value="0">Hide</option>
                </select>
                <button class="btn btn-primary" type="submit">Add Service</button>
            </form>
        </div>
    </div>

    @forelse($services as $service)
        <div class="ibox">
            <div class="ibox-title" style="display:flex;justify-content:space-between;align-items:center;">
                <h5 style="margin:0;">Service: {{ $service->name }} @unless($service->is_visible)<span class="label label-default">Hidden</span>@endunless</h5>
                <div>
                    <form action="{{ route('admin.pages.fees.services.duplicate', $service->id) }}" method="POST" style="display:inline;">@csrf
                        <button class="btn btn-info btn-xs" type="submit">Duplicate Service</button>
                    </form>
                    <form action="{{ route('admin.pages.fees.services.destroy', $service->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this service and all nested fees?');">@csrf @method('DELETE')
                        <button class="btn btn-danger btn-xs" type="submit">Delete</button>
                    </form>
                </div>
            </div>
            <div class="ibox-content">
                <form method="POST" action="{{ route('admin.pages.fees.services.update', $service->id) }}" class="form-inline m-b-md">
                    @csrf
                    <input type="text" name="name" class="form-control m-r-sm" value="{{ $service->name }}" required>
                    <input type="number" name="sort_order" class="form-control m-r-sm" value="{{ $service->sort_order }}" style="width:90px;">
                    <select name="is_visible" class="form-control m-r-sm">
                        <option value="1" @selected($service->is_visible)>Show</option>
                        <option value="0" @selected(!$service->is_visible)>Hide</option>
                    </select>
                    <button class="btn btn-default btn-sm" type="submit">Update Service</button>
                </form>

                <form method="POST" action="{{ route('admin.pages.fees.categories.store', $service->id) }}" class="form-inline m-b-md">
                    @csrf
                    <input type="text" name="name" class="form-control m-r-sm" placeholder="Category name" required>
                    <input type="number" name="sort_order" class="form-control m-r-sm" value="0" style="width:90px;">
                    <select name="is_visible" class="form-control m-r-sm">
                        <option value="1">Show</option>
                        <option value="0">Hide</option>
                    </select>
                    <button class="btn btn-primary btn-sm" type="submit">Add Category</button>
                </form>

                @foreach($service->categories as $category)
                    <div style="border:1px solid #e7eaec;padding:12px;margin-bottom:12px;border-radius:4px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                            <strong>Category: {{ $category->name }} @unless($category->is_visible)<span class="label label-default">Hidden</span>@endunless</strong>
                            <div>
                                <form action="{{ route('admin.pages.fees.categories.duplicate', $category->id) }}" method="POST" style="display:inline;">@csrf
                                    <button class="btn btn-info btn-xs" type="submit">Duplicate Category</button>
                                </form>
                                <form action="{{ route('admin.pages.fees.categories.destroy', $category->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete category and packages?');">@csrf @method('DELETE')
                                    <button class="btn btn-danger btn-xs" type="submit">Delete</button>
                                </form>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.pages.fees.categories.update', $category->id) }}" class="form-inline m-b-md">
                            @csrf
                            <input type="text" name="name" class="form-control m-r-sm" value="{{ $category->name }}" required>
                            <input type="number" name="sort_order" class="form-control m-r-sm" value="{{ $category->sort_order }}" style="width:90px;">
                            <select name="is_visible" class="form-control m-r-sm">
                                <option value="1" @selected($category->is_visible)>Show</option>
                                <option value="0" @selected(!$category->is_visible)>Hide</option>
                            </select>
                            <button class="btn btn-default btn-sm" type="submit">Update Category</button>
                        </form>

                        <form method="POST" action="{{ route('admin.pages.fees.packages.store', $category->id) }}" class="m-b-sm">
                            @csrf
                            <div class="row">
                                <div class="col-md-3"><input class="form-control" name="package_name" placeholder="Package name" required></div>
                                <div class="col-md-2"><input class="form-control" name="currency" value="AED" required></div>
                                <div class="col-md-2"><input class="form-control" name="price" type="number" step="0.01" placeholder="Price" required></div>
                                <div class="col-md-2"><input class="form-control" name="tax_percentage" type="number" step="0.01" placeholder="Tax %"></div>
                                <div class="col-md-3"><input class="form-control" name="discount_amount" type="number" step="0.01" placeholder="Discount"></div>
                            </div>
                            <div class="row" style="margin-top:8px;">
                                <div class="col-md-4"><input class="form-control" name="short_description" placeholder="Short description"></div>
                                <div class="col-md-4"><input class="form-control" name="key_point" placeholder="Key points (comma separated)"></div>
                                <div class="col-md-2"><input class="form-control" name="installments" type="number" placeholder="Installments"></div>
                                <div class="col-md-2"><input class="form-control" name="priority" type="number" value="0" placeholder="Priority"></div>
                            </div>
                            <div class="row" style="margin-top:8px;">
                                <div class="col-md-6"><textarea class="form-control" name="package_includes" rows="2" placeholder="Include items (one per line)"></textarea></div>
                                <div class="col-md-2">
                                    <select name="showonwebsite" class="form-control">
                                        <option value="1">Show on website</option>
                                        <option value="0">Hide</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="status" class="form-control">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-2"><button class="btn btn-primary btn-block" type="submit">Add Package</button></div>
                            </div>
                            <div class="row" style="margin-top:8px;">
                                <div class="col-md-12">
                                    <label class="text-muted">Invoice T&amp;C Template</label>
                                    <select name="invoice_terms_template_id" class="form-control">
                                        <option value="">— Select template —</option>
                                        @foreach(($invoiceTermsTemplates ?? []) as $tpl)
                                            <option value="{{ $tpl->id }}">{{ $tpl->name }}{{ $tpl->is_active ? '' : ' (inactive)' }}</option>
                                        @endforeach
                                    </select>
                                    <p class="help-block m-b-none">
                                        Manage in <a href="{{ route('admin.invoice-terms-templates.index') }}" target="_blank">Settings → Invoice T&amp;C Templates</a>
                                    </p>
                                </div>
                            </div>
                        </form>

                        @foreach($category->packages as $package)
                            <div style="background:#f9f9f9;border:1px solid #eee;padding:10px;margin-top:10px;">
                                <form method="POST" action="{{ route('admin.pages.fees.packages.update', $package->id) }}">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-3"><input class="form-control" name="package_name" value="{{ $package->package_name }}" required></div>
                                        <div class="col-md-2"><input class="form-control" name="currency" value="{{ $package->currency }}" required></div>
                                        <div class="col-md-2"><input class="form-control" name="price" type="number" step="0.01" value="{{ $package->price }}" required></div>
                                        <div class="col-md-2"><input class="form-control" name="tax_percentage" type="number" step="0.01" value="{{ $package->tax_percentage }}" placeholder="Tax %"></div>
                                        <div class="col-md-3"><input class="form-control" name="discount_amount" type="number" step="0.01" value="{{ $package->discount_amount }}" placeholder="Discount"></div>
                                    </div>
                                    <div class="row" style="margin-top:8px;">
                                        <div class="col-md-4"><input class="form-control" name="short_description" value="{{ $package->short_description }}"></div>
                                        <div class="col-md-4"><input class="form-control" name="key_point" value="{{ $package->key_point }}"></div>
                                        <div class="col-md-2"><input class="form-control" name="installments" type="number" value="{{ $package->installments }}"></div>
                                        <div class="col-md-2"><input class="form-control" name="priority" type="number" value="{{ $package->priority }}"></div>
                                    </div>
                                    <div class="row" style="margin-top:8px;">
                                        <div class="col-md-6">
                                            <textarea class="form-control" name="package_includes" rows="2">{{ is_array($package->package_includes) ? implode("\n", $package->package_includes) : $package->package_includes }}</textarea>
                                        </div>
                                        <div class="col-md-2">
                                            <select name="showonwebsite" class="form-control">
                                                <option value="1" @selected($package->showonwebsite)>Show</option>
                                                <option value="0" @selected(!$package->showonwebsite)>Hide</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select name="status" class="form-control">
                                                <option value="1" @selected($package->status)>Active</option>
                                                <option value="0" @selected(!$package->status)>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2"><button class="btn btn-default btn-block" type="submit">Save Package</button></div>
                                    </div>
                                    <div class="row" style="margin-top:8px;">
                                        <div class="col-md-12">
                                            <label class="text-muted">Invoice T&amp;C Template</label>
                                            <select name="invoice_terms_template_id" class="form-control">
                                                <option value="">— Select template —</option>
                                                @foreach(($invoiceTermsTemplates ?? []) as $tpl)
                                                    <option value="{{ $tpl->id }}" @selected((string) $package->invoice_terms_template_id === (string) $tpl->id)>
                                                        {{ $tpl->name }}{{ $tpl->is_active ? '' : ' (inactive)' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </form>
                                <form action="{{ route('admin.pages.fees.packages.destroy', $package->id) }}" method="POST" style="margin-top:6px;" onsubmit="return confirm('Delete package?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-xs" type="submit">Delete Package</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="alert alert-warning">No services yet. Add the first service above.</div>
    @endforelse
</div>
@endsection
