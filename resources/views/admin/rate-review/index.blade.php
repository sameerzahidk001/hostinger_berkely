@extends(lms_portal_layout())
@section('title', 'Rate and Review')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Rate and Review</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li class="active"><strong>Instructor ratings</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif

    <div class="ibox">
        <div class="ibox-title"><h5>Scores by instructor</h5></div>
        <div class="ibox-content table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr><th>Instructor</th><th>Average</th><th>Reviews</th></tr>
                </thead>
                <tbody>
                    @forelse($report as $row)
                        <tr>
                            <td>{{ $row['instructor']->name ?? ('#' . $row['instructor_id']) }}</td>
                            <td>{{ number_format($row['average'], 1) }}</td>
                            <td>{{ $row['count'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">No ratings yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="ibox">
        <div class="ibox-title"><h5>All reviews</h5></div>
        <div class="ibox-content table-responsive">
            <form method="GET" action="{{ route('admin.rate-review.index') }}" class="m-b-md" style="margin-bottom:15px;">
                <div class="row">
                    @if(!empty($isAdmin))
                    <div class="col-sm-3 form-group">
                        <label>Instructor</label>
                        <select name="instructor_id" class="form-control">
                            <option value="0">All</option>
                            @foreach($instructors as $ins)
                                <option value="{{ $ins->id }}" @selected((int)$filters['instructor_id'] === (int)$ins->id)>{{ $ins->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-sm-3 form-group">
                        <label>Batch</label>
                        <select name="batch_id" class="form-control">
                            <option value="0">All</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" @selected((int)$filters['batch_id'] === (int)$batch->id)>{{ $batch->code }} — {{ $batch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2 form-group">
                        <label>From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                    </div>
                    <div class="col-sm-2 form-group">
                        <label>To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                    </div>
                    <div class="col-sm-2 form-group">
                        <label>Public</label>
                        <select name="published" class="form-control">
                            <option value="">All</option>
                            <option value="1" @selected($filters['published'] === '1')>Published</option>
                            <option value="0" @selected($filters['published'] === '0')>Hidden</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.rate-review.index') }}" class="btn btn-default">Clear</a>
            </form>

            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Instructor</th>
                        <th>Student</th>
                        <th>Stars</th>
                        <th>Review</th>
                        <th>Context</th>
                        <th>Flags</th>
                        @if(!empty($isAdmin))<th>Actions</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ optional($row->created_at)->format('d M Y') }}</td>
                            <td>{{ $row->instructor->name ?? '—' }}</td>
                            <td>
                                {{ $row->student->name ?? '—' }}
                                @if(!empty($row->student->email))
                                    <br><small class="text-muted">{{ $row->student->email }}</small>
                                @endif
                            </td>
                            <td>{{ $row->stars }} / 5</td>
                            <td>{{ $row->review_text ?: '—' }}</td>
                            <td>{{ $row->contextLabel() }}</td>
                            <td>
                                @if($row->is_published)<span class="label label-primary">Published</span>@else<span class="label label-default">Hidden</span>@endif
                                @if($row->is_locked)<span class="label label-warning">Locked</span>@endif
                                @if($row->hide_student_name)<span class="label label-info">Name hidden</span>@endif
                            </td>
                            @if(!empty($isAdmin))
                            <td style="white-space:nowrap;">
                                @if($row->is_published)
                                    <form action="{{ route('admin.rate-review.hide', $row->id) }}" method="POST" style="display:inline;">@csrf<button class="btn btn-xs btn-default">Hide</button></form>
                                @else
                                    <form action="{{ route('admin.rate-review.publish', $row->id) }}" method="POST" style="display:inline;">@csrf<button class="btn btn-xs btn-primary">Publish</button></form>
                                @endif
                                @if($row->is_locked)
                                    <form action="{{ route('admin.rate-review.unlock', $row->id) }}" method="POST" style="display:inline;">@csrf<button class="btn btn-xs btn-default">Unlock</button></form>
                                @else
                                    <form action="{{ route('admin.rate-review.lock', $row->id) }}" method="POST" style="display:inline;">@csrf<button class="btn btn-xs btn-warning">Lock</button></form>
                                @endif
                                <form action="{{ route('admin.rate-review.toggle-name', $row->id) }}" method="POST" style="display:inline;">@csrf<button class="btn btn-xs btn-info">{{ $row->hide_student_name ? 'Show name' : 'Hide name' }}</button></form>
                                <form action="{{ route('admin.rate-review.destroy', $row->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Remove this review?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-xs btn-danger">Remove</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ !empty($isAdmin) ? 8 : 7 }}" class="text-center">No reviews match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection
