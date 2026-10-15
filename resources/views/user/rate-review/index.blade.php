@extends('user.layout.app')
@section('title', 'Rate and Review')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Rate and Review</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('user.home') }}">Home</a></li>
            <li class="active"><strong>Rate and Review</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif

    <div class="ibox">
        <div class="ibox-title"><h5>Your assigned instructors</h5></div>
        <div class="ibox-content table-responsive">
            <p class="text-muted">Rate instructors from your active batches and study material folders. One rating per instructor per batch (or folder). You can edit until Admin locks it.</p>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Instructor</th>
                        <th>Context</th>
                        <th>Your rating</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($targets as $target)
                        @php $review = $target['existing']; @endphp
                        <tr>
                            <td>{{ $target['instructor']->name ?? '—' }}</td>
                            <td>
                                @if($target['batch'])
                                    Batch: {{ $target['batch']->displayLabel() }}
                                    @if($target['batch']->course)
                                        <br><small class="text-muted">{{ $target['batch']->course->title }}</small>
                                    @endif
                                @elseif($target['folder'])
                                    Folder: {{ $target['folder']->code ?? '' }} {{ $target['folder']->name ?? '' }}
                                    @if($target['folder']->course)
                                        <br><small class="text-muted">{{ $target['folder']->course->title }}</small>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($review)
                                    {{ $review->stars }} / 5
                                    @if($review->review_text)
                                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($review->review_text, 80) }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">Not rated yet</span>
                                @endif
                            </td>
                            <td>
                                @if($review)
                                    @if($review->is_locked)
                                        <span class="label label-warning">Locked</span>
                                    @else
                                        <span class="label label-primary">Saved</span>
                                    @endif
                                @else
                                    <span class="label label-default">Open</span>
                                @endif
                            </td>
                            <td>
                                @if($review && $review->is_locked)
                                    <span class="text-muted">View only</span>
                                @else
                                    <a class="btn btn-xs btn-primary" href="{{ route('user.rate-review.create', ['key' => $target['key']]) }}">
                                        {{ $review ? 'Edit' : 'Rate' }}
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No assigned instructors available to rate yet. You need active batch or folder access first.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
