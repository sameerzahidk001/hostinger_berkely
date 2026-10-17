@php
    $fromTs = (int) $fromAt->getTimestamp();
    $toTs = (int) $toAt->getTimestamp();
    $gapKey = app(\App\Services\ActivityGapCommentService::class)->key($scope, $fromTs, $toTs);
    $savedComment = $activityGapComments[$gapKey] ?? '';
@endphp
<tr>
    <td colspan="{{ $colspan }}" class="activity-gap-cell">
        <div class="activity-gap-line">
            <span class="activity-gap-marker">…………………… 10+ minute gap ……………………</span>
            <form method="POST" action="{{ route('admin.home.activity-gap-comment') }}" class="activity-gap-comment-form">
                @csrf
                <input type="hidden" name="scope" value="{{ $scope }}">
                <input type="hidden" name="from_ts" value="{{ $fromTs }}">
                <input type="hidden" name="to_ts" value="{{ $toTs }}">
                <input type="text" name="comment" class="form-control input-sm" maxlength="500" value="{{ $savedComment }}" placeholder="Reason / comment for this gap">
                <button type="submit" class="btn btn-xs btn-primary">Save</button>
            </form>
        </div>
        @if($savedComment !== '')
            <div class="activity-gap-saved">Comment: {{ $savedComment }}</div>
        @endif
    </td>
</tr>
