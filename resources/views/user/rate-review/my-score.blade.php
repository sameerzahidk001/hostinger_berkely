@extends('user.layout.app')
@section('title', 'My Instructor Rating')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>My Instructor Rating</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('user.home') }}">Home</a></li>
            <li class="active"><strong>My Rating</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    <div class="ibox">
        <div class="ibox-title"><h5>Overall score</h5></div>
        <div class="ibox-content">
            @if($score['count'] > 0)
                <p style="font-size:28px;margin:0;">
                    <strong>{{ number_format($score['average'], 1) }}</strong>
                    <span class="text-muted" style="font-size:16px;"> from {{ $score['count'] }} student{{ $score['count'] === 1 ? '' : 's' }}</span>
                </p>
            @else
                <p class="text-muted" style="margin:0;">No student ratings yet.</p>
            @endif
            <p class="help-block" style="margin-top:10px;margin-bottom:0;">You can read reviews of yourself. You cannot change student ratings.</p>
        </div>
    </div>

    <div class="ibox">
        <div class="ibox-title"><h5>Student reviews</h5></div>
        <div class="ibox-content table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Stars</th>
                        <th>Review</th>
                        <th>Context</th>
                        <th>Date</th>
                        <th>Public</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                        <tr>
                            <td>{{ $review->studentDisplayName() }}</td>
                            <td>{{ $review->stars }} / 5</td>
                            <td>{{ $review->review_text ?: '—' }}</td>
                            <td>{{ $review->contextLabel() }}</td>
                            <td>{{ optional($review->created_at)->format('d M Y') }}</td>
                            <td>
                                @if($review->is_published)
                                    <span class="label label-primary">Published</span>
                                @else
                                    <span class="label label-default">Hidden</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No reviews yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $reviews->links() }}
        </div>
    </div>
</div>
@endsection
