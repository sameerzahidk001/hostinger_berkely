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
    <div class="alert alert-info">
        Build fees as <strong>Service → Category → Fee Package</strong> on this CMS page (no Course required).
        Visible packages render on the public page automatically.
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
                            <strong>Category: {{ $category->name }}</strong>
                            <div>
                                <form action="{{ route('admin.pages.fees.categories.duplicate', $category->id) }}" method="POST" style="display:inline;">@csrf
                                    <button class="btn btn-info btn-xs" type="submit">Duplicate Category</button>
                                </form>
                                <form action="{{ route('admin.pages.fees.categories.destroy', $category->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete category and packages?');">@csrf @method('DELETE')
                                    <button class="btn btn-danger btn-xs" type="submit">Delete</button>
                                </form>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.pages.fees.packages.store', $category->id) }}" class="m-b-sm">
                            @csrf
                            <div class="row">
                                <div class="col-md-3"><input class="form-control" name="package_name" placeholder="Package name" required></div>
                                <div class="col-md-2"><input class="form-control" name="currency" value="AED" required></div>
                                <div class="col-md-2"><input class="form-control" name="price" type="number" step="0.01" placeholder="Price" required></div>
                                <div class="col-md-2"><input class="form-control" name="discount_amount" type="number" step="0.01" placeholder="Discount"></div>
                                <div class="col-md-3"><input class="form-control" name="short_description" placeholder="Short description"></div>
                            </div>
                            <div class="row" style="margin-top:8px;">
                                <div class="col-md-6"><textarea class="form-control" name="package_includes" rows="2" placeholder="Include items (one per line)"></textarea></div>
                                <div class="col-md-2"><input class="form-control" name="installments" type="number" placeholder="Installments"></div>
                                <div class="col-md-2">
                                    <select name="showonwebsite" class="form-control">
                                        <option value="1">Show on website</option>
                                        <option value="0">Hide</option>
                                    </select>
                                </div>
                                <div class="col-md-2"><button class="btn btn-primary btn-block" type="submit">Add Package</button></div>
                            </div>
                        </form>

                        @if($category->packages->isNotEmpty())
                            <table class="table table-bordered table-striped m-t-sm">
                                <thead>
                                    <tr>
                                        <th>Package</th>
                                        <th>Price</th>
                                        <th>Includes</th>
                                        <th style="width:90px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($category->packages as $package)
                                        <tr>
                                            <td>{{ $package->package_name }}</td>
                                            <td>{{ $package->currency }} {{ number_format((float)$package->price, 2) }}</td>
                                            <td>{{ is_array($package->package_includes) ? implode(', ', $package->package_includes) : '' }}</td>
                                            <td>
                                                <form action="{{ route('admin.pages.fees.packages.destroy', $package->id) }}" method="POST" onsubmit="return confirm('Delete package?');">@csrf @method('DELETE')
                                                    <button class="btn btn-danger btn-xs" type="submit">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="alert alert-warning">No services yet. Add the first service above.</div>
    @endforelse
</div>
@endsection
