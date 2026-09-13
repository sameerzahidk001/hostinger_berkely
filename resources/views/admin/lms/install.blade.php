@extends('admin.layout.app')
@section('title', 'Install LMS')

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Install LMS tables</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li class="active"><strong>LMS Install</strong></li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="ibox">
        <div class="ibox-content">
            @if(session('fail'))
                <div class="alert alert-danger">{{ session('fail') }}</div>
            @endif
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <p>This creates <strong>new LMS tables only</strong>. It does not change payments, users, courses, or images.</p>
            <p class="text-danger"><strong>Do not</strong> click Ignition “Run Migrations” — that runs all migrations and can break live.</p>

            <h4>Table status</h4>
            <ul>
                @foreach($tables as $name => $exists)
                    <li>
                        <code>{{ $name }}</code>:
                        @if($exists)
                            <span class="text-navy">OK</span>
                        @else
                            <span class="text-danger">missing</span>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if($ready)
                <a href="{{ route('admin.study-materials.folders.index') }}" class="btn btn-primary">Open Study Materials</a>
            @else
                <form method="POST" action="{{ route('admin.lms.install.run') }}" onsubmit="return confirm('Create LMS tables on this live database now?');">
                    @csrf
                    <button type="submit" class="btn btn-primary">Create LMS tables now</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
