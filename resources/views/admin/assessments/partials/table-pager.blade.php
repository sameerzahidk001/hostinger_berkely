@php
    $pager = $pager ?? null;
@endphp
@if($pager && method_exists($pager, 'lastPage'))
    <div class="lms-assigned-pager">
        @if($pager->onFirstPage())
            <span class="btn btn-default btn-sm disabled">Back</span>
        @else
            <a class="btn btn-default btn-sm" href="{{ $pager->previousPageUrl() }}">Back</a>
        @endif
        <span class="lms-page-label">Page {{ $pager->currentPage() }} of {{ $pager->lastPage() }} ({{ $pager->perPage() }} per page)</span>
        @if($pager->hasMorePages())
            <a class="btn btn-primary btn-sm" href="{{ $pager->nextPageUrl() }}">Next</a>
        @else
            <span class="btn btn-primary btn-sm disabled">Next</span>
        @endif
    </div>
@endif
