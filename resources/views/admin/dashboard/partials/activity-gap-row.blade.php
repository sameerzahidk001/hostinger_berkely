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
            @if($savedComment !== '')
                <span class="activity-gap-saved-text">— {{ $savedComment }}</span>
            @endif
            <form method="POST" action="{{ route('admin.home.activity-gap-comment') }}" class="activity-gap-comment-form {{ $savedComment !== '' ? 'activity-gap-comment-form-saved' : '' }}">
                @csrf
                <input type="hidden" name="scope" value="{{ $scope }}">
                <input type="hidden" name="from_ts" value="{{ $fromTs }}">
                <input type="hidden" name="to_ts" value="{{ $toTs }}">
                @if($savedComment !== '')
                    <details class="activity-gap-edit">
                        <summary class="btn btn-xs btn-default">Edit</summary>
                        <div class="activity-gap-edit-fields">
                            <input type="text" name="comment" class="form-control input-sm" maxlength="500" value="{{ $savedComment }}" placeholder="Reason / comment for this gap">
                            <button type="submit" class="btn btn-xs btn-primary">Save</button>
                        </div>
                    </details>
                @else
                    <input type="text" name="comment" class="form-control input-sm" maxlength="500" value="" placeholder="Reason / comment for this gap">
                    <button type="submit" class="btn btn-xs btn-primary">Save</button>
                @endif
            </form>
        </div>
    </td>
</tr>
