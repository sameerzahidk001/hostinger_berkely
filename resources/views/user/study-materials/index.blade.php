@extends('user.layout.app')
@section('title', 'Study Materials')
@section('content')
@include('user.study-materials._folder_cards_styles')
@php $isInstructorPortal = auth()->user()?->roles()->where('name', 'instructor')->exists(); @endphp
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-7">
        <h2>Study Materials</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('user.home') }}">Dashboard</a></li>
            <li class="active"><strong>Study Materials</strong></li>
        </ol>
    </div>
    @if($isInstructorPortal)
    <div class="col-lg-5 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.study-materials.folders.create') }}" class="btn btn-primary">Create Folder</a>
        <a href="{{ route('admin.study-materials.folders.index') }}" class="btn btn-default">Manage Folders</a>
        <a href="{{ route('admin.study-materials.access.students') }}" class="btn btn-default">Student Access</a>
    </div>
    @endif
</div>
<div class="wrapper wrapper-content">
    <div class="sm-folder-cards-row">
        @forelse($accesses as $access)
            @include('user.study-materials._folder_card', ['access' => $access])
        @empty
            <div class="col-lg-12">
                <div class="ibox"><div class="ibox-content text-center text-muted">
                    No study material folders assigned yet.
                    @if(!empty($isInstructorPortal))
                        <div style="margin-top:12px;">
                            <a href="{{ route('admin.study-materials.folders.create') }}" class="btn btn-primary">Create Folder</a>
                        </div>
                    @endif
                </div></div>
            </div>
        @endforelse
    </div>
</div>
@endsection
