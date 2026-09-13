@extends('admin.layout.app')
@section('title', 'Dashboard')

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Dashboard</h2>
        <ol class="breadcrumb">
            <li class="active"><strong>Course Access</strong></li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
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
