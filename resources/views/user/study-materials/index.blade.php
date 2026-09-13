@extends('user.layout.app')
@section('title', 'Study Materials')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Study Materials</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('user.home') }}">Dashboard</a></li>
            <li class="active"><strong>Study Materials</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    <div class="row">
        @forelse($accesses as $access)
            @include('user.study-materials._folder_card', ['access' => $access])
        @empty
            <div class="col-lg-12">
                <div class="ibox"><div class="ibox-content text-center text-muted">No study material folders assigned yet.</div></div>
            </div>
        @endforelse
    </div>
</div>
@endsection
