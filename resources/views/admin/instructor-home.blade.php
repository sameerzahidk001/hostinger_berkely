@extends('admin.layout.app')
@section('title', 'Dashboard')

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-7">
        <h2>Dashboard</h2>
        <ol class="breadcrumb">
            <li class="active"><strong>Course Access</strong></li>
        </ol>
    </div>
    <div class="col-lg-5 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.study-materials.folders.index') }}" class="btn btn-default">Manage Folders</a>
        <a href="{{ route('admin.study-materials.access.assign-student') }}" class="btn btn-success">Assign Access</a>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @include('user.study-materials._folder_cards_styles')
    <div class="sm-folder-cards-row">
        @forelse($courseAccesses as $access)
            @include('user.study-materials._folder_card', ['access' => $access])
        @empty
            <div class="col-lg-12">
                <div class="ibox">
                    <div class="ibox-content text-center text-muted">
                        No folders assigned yet. When admin assigns you folder access, it will appear here.
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
