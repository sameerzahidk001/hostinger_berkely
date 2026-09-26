@extends('user.layout.app')
@section('title', 'Study Materials')
@section('content')
<style>
    .sm-folder-cards-row {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        margin-left: -15px;
        margin-right: -15px;
    }
    .sm-folder-cards-row > [class*="col-"] {
        display: flex;
        float: none;
        margin-bottom: 20px;
    }
    .sm-folder-card {
        width: 100%;
        min-height: 320px;
        display: flex;
        flex-direction: column;
        margin-bottom: 0;
    }
    .sm-folder-card .ibox-title {
        min-height: 64px;
        display: flex;
        align-items: center;
    }
    .sm-folder-card .ibox-title h5 {
        color: #fff;
        margin: 0;
        line-height: 1.35;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .sm-folder-card .ibox-content {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        padding-bottom: 12px !important;
    }
    .sm-folder-card .sm-folder-card-body {
        flex: 1 1 auto;
        padding-bottom: 12px;
    }
    .sm-folder-card .sm-folder-card-footer {
        margin-top: auto;
        padding-top: 4px;
        padding-bottom: 0;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        gap: 0;
    }
    .sm-folder-card .sm-folder-card-footer > .btn,
    .sm-folder-card .sm-folder-card-footer > a.btn {
        margin-bottom: 0;
    }
    .sm-folder-card .sm-folder-card-contact {
        min-height: 0;
        font-size: 13px;
        margin-top: 6px !important;
        margin-bottom: 0 !important;
    }
</style>
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
    <div class="sm-folder-cards-row">
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
