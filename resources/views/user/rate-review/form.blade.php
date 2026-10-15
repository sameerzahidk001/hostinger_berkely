@extends('user.layout.app')
@section('title', ($review ? 'Edit' : 'Rate') . ' Instructor')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $review ? 'Edit rating' : 'Rate and Review' }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('user.home') }}">Home</a></li>
            <li><a href="{{ route('user.rate-review.index') }}">Rate and Review</a></li>
            <li class="active"><strong>{{ $review ? 'Edit' : 'Rate' }}</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox">
        <div class="ibox-title"><h5>{{ $target['instructor']->name ?? 'Instructor' }}</h5></div>
        <div class="ibox-content">
            <p class="text-muted">
                @if($target['batch'])
                    Batch: {{ $target['batch']->displayLabel() }}
                @elseif($target['folder'])
                    Folder: {{ $target['folder']->code ?? '' }} {{ $target['folder']->name ?? '' }}
                @endif
            </p>
            <form method="POST" action="{{ route('user.rate-review.store') }}">
                @csrf
                <input type="hidden" name="key" value="{{ $target['key'] }}">
                <div class="form-group">
                    <label>Star rating (1–5) *</label>
                    <select name="stars" class="form-control" required style="max-width:200px;">
                        <option value="">Select</option>
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" @selected((string) old('stars', $review->stars ?? '') === (string) $i)>{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                        @endfor
                    </select>
                </div>
                <div class="form-group">
                    <label>Written review{{ !empty($target['review_text_required']) ? ' *' : ' (optional)' }}</label>
                    <textarea name="review_text" class="form-control" rows="5" maxlength="2000" @if(!empty($target['review_text_required'])) required @endif placeholder="Teaching, clarity, support…">{{ old('review_text', $review->review_text ?? '') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save rating</button>
                <a href="{{ route('user.rate-review.index') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
