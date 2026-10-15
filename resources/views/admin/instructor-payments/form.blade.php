@extends('admin.layout.app')
@section('title', ($payment->exists ? 'Edit' : 'Add') . ' Instructor Payment')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $payment->exists ? 'Edit' : 'Add' }} instructor payment</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.instructor-payments.index') }}">Instructor Payments</a></li>
            <li class="active"><strong>{{ $payment->exists ? 'Edit' : 'Add' }}</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox">
        <div class="ibox-content">
            <form method="POST" action="{{ $payment->exists ? route('admin.instructor-payments.update', $payment->id) : route('admin.instructor-payments.store') }}" enctype="multipart/form-data">
                @csrf
                @if($payment->exists) @method('PUT') @endif
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Batch *</label>
                        <select name="class_batch_id" id="class_batch_id" class="form-control" required>
                            <option value="">Select batch</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" @selected((string) old('class_batch_id', $payment->class_batch_id) === (string) $batch->id)>
                                    {{ $batch->code }} — {{ $batch->name }}@if($batch->course) ({{ $batch->course->title }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Instructor *</label>
                        <select name="instructor_id" id="instructor_id" class="form-control" required>
                            <option value="">Select instructor</option>
                            @foreach($instructors as $ins)
                                <option value="{{ $ins->id }}" @selected((string) old('instructor_id', $payment->instructor_id) === (string) $ins->id)>
                                    {{ $ins->name }} ({{ $ins->email }})
                                </option>
                            @endforeach
                        </select>
                        <span class="help-block">Choose a batch first to narrow the list to assigned instructors.</span>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Rate</label>
                        <input type="number" step="0.01" min="0" name="rate" class="form-control" value="{{ old('rate', $payment->rate) }}" placeholder="Optional">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Amount *</label>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ old('amount', $payment->amount) }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Currency *</label>
                        <select name="currency" class="form-control" required>
                            @php
                                $currencyCodes = $currencies->pluck('code')->map(fn ($c) => strtoupper($c))->unique()->values();
                                if ($currencyCodes->isEmpty()) {
                                    $currencyCodes = collect(['AED', 'USD', 'GBP', 'EUR']);
                                }
                                $selectedCurrency = strtoupper(old('currency', $payment->currency ?: 'AED'));
                                if (! $currencyCodes->contains($selectedCurrency)) {
                                    $currencyCodes->prepend($selectedCurrency);
                                }
                            @endphp
                            @foreach($currencyCodes as $code)
                                <option value="{{ $code }}" @selected($selectedCurrency === $code)>{{ $code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Status *</label>
                        <select name="status" class="form-control" required>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $payment->status ?: 'pending') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Paid date</label>
                        <input type="date" name="paid_date" class="form-control" value="{{ old('paid_date', optional($payment->paid_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-8 form-group">
                        <label>Payment slip {{ $payment->exists && $payment->hasSlip() ? '(replace)' : '' }}</label>
                        <input type="file" name="payment_slip" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        @if($payment->exists && $payment->hasSlip())
                            <span class="help-block">
                                Current:
                                <a href="{{ route('admin.instructor-payments.slip', $payment->id) }}" target="_blank">View uploaded slip</a>
                            </span>
                        @else
                            <span class="help-block">PDF or image, max 10 MB.</span>
                        @endif
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="4" maxlength="5000">{{ old('notes', $payment->notes) }}</textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.instructor-payments.index') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
@push('script')
<script>
$(function () {
    var metaUrl = @json(route('admin.instructor-payments.batch-instructors'));
    var $batch = $('#class_batch_id');
    var $instructor = $('#instructor_id');
    var selected = @json((string) old('instructor_id', $payment->instructor_id));

    function loadInstructors(batchId, keepSelected) {
        if (!batchId) {
            return;
        }
        $.getJSON(metaUrl, { batch_id: batchId }, function (resp) {
            var list = resp.instructors || [];
            if (!list.length) {
                return;
            }
            var current = keepSelected ? (keepSelected || $instructor.val()) : $instructor.val();
            $instructor.empty().append($('<option value="">Select instructor</option>'));
            list.forEach(function (row) {
                var opt = $('<option></option>').val(row.id).text(row.name + (row.email ? ' (' + row.email + ')' : ''));
                if (String(row.id) === String(current) || String(row.id) === String(selected)) {
                    opt.prop('selected', true);
                }
                $instructor.append(opt);
            });
        });
    }

    $batch.on('change', function () {
        loadInstructors($(this).val(), null);
    });

    if ($batch.val()) {
        loadInstructors($batch.val(), selected);
    }
});
</script>
@endpush
