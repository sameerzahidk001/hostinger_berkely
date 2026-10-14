@extends('admin.layout.app')
@section('title', 'Fee Update in Page')
@section('content')
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-10">
            <h2>Fee Update in Page</h2>
            <ol class="breadcrumb">
                <li><a href="{{ route('admin.home') }}">Home</a></li>
                <li><a href="{{ route('pages.index') }}">Pages</a></li>
                <li class="active"><strong>Fee Update in Page</strong></li>
            </ol>
        </div>
    </div>

    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>Choose a page to manage Service → Category → Fee Package</h5>
                    </div>
                    <div class="ibox-content">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th>Page</th>
                                        <th>URL</th>
                                        <th style="width:110px;">Services</th>
                                        <th style="width:180px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pages as $index => $page)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $page->page_name }}</td>
                                            <td>{{ $page->url }}</td>
                                            <td>{{ (int) ($serviceCounts[$page->id] ?? 0) }}</td>
                                            <td>
                                                <a href="{{ route('admin.pages.fees.edit', $page->id) }}"
                                                   class="btn btn-warning btn-xs">
                                                    <i class="fa fa-money"></i> Fee Update in Page
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No active pages found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
