@php
    $index = $index ?? 0;
    $row = $row ?? [];
    $type = $row['type'] ?? 'mcq';
    $marks = $row['max_marks'] ?? 1;
    $stem = $row['stem'] ?? '';
    $explanation = $row['explanation'] ?? '';
    $timeLimit = $row['time_limit_minutes'] ?? '';
    $opts = $row['options'] ?? [['text'=>''],['text'=>''],['text'=>''],['text'=>'']];
    $correct = (int) ($row['correct_option'] ?? 0);
    $subs = $row['sub_questions'] ?? null;
    if (! is_array($subs)) {
        $subs = [['text' => '', 'marks' => '']];
    }
    $prefix = 'questions[' . $index . ']';
    $isMcq = $type === 'mcq';
@endphp
<div class="panel panel-default question-block" data-index="{{ $index }}" style="margin-bottom:16px;">
    <div class="panel-heading" style="display:flex;justify-content:space-between;align-items:center;">
        <strong class="js-block-label">Question {{ is_numeric($index) ? ((int)$index + 1) : 1 }}</strong>
        <button type="button" class="btn btn-xs btn-danger js-remove-block">Remove</button>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-3 form-group">
                <label>Type *</label>
                <select name="{{ $prefix }}[type]" class="form-control js-q-type lms-searchable" data-placeholder="Type to find type" data-allow-clear="0" required>
                    @foreach($types as $k => $label)
                        <option value="{{ $k }}" @selected($type === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 form-group">
                <label>Time limit (min)</label>
                <input type="number" min="1" max="600" name="{{ $prefix }}[time_limit_minutes]" class="form-control" value="{{ $timeLimit }}" placeholder="Optional">
                <p class="help-block m-b-none" style="font-size:11px;">Auto-save &amp; next when up.</p>
            </div>
            <div class="col-md-2 form-group js-max-marks-wrap">
                <label>Max marks *</label>
                <input type="number" step="0.1" min="0.1" name="{{ $prefix }}[max_marks]" class="form-control js-max-marks" value="{{ $marks }}" required>
            </div>
            <div class="col-md-12 form-group">
                <label>Question details * <span class="lms-char-hint">(max 10,000 characters)</span></label>
                <textarea name="{{ $prefix }}[stem]" class="form-control lms-text-editor" rows="4" data-max="10000" data-height="160" data-rich="1">{{ $stem }}</textarea>
            </div>
            <div class="col-md-12 form-group">
                <label>Explanation / model answer notes <span class="lms-char-hint">(max 10,000 characters)</span></label>
                <textarea name="{{ $prefix }}[explanation]" class="form-control lms-text-editor" rows="3" data-max="10000" data-height="120" data-rich="1">{{ $explanation }}</textarea>
            </div>
        </div>
        <div class="js-mcq-options" style="{{ $isMcq ? '' : 'display:none;' }}">
            <h5>MCQ options</h5>
            @foreach($opts as $i => $opt)
                <div class="form-group">
                    <label>
                        <input type="radio" name="{{ $prefix }}[correct_option]" value="{{ $i }}" @checked($correct === (int)$i)> Correct
                    </label>
                    <input type="text" name="{{ $prefix }}[options][{{ $i }}][text]" class="form-control" data-max="500" maxlength="500" value="{{ $opt['text'] ?? '' }}" placeholder="Option {{ $i + 1 }} (max 500)">
                </div>
            @endforeach
        </div>
        <div class="js-sub-questions" style="{{ $isMcq ? 'display:none;' : '' }}">
            <h5>Sub-questions <span class="text-muted">(optional — 1, 2, 3… marks each)</span></h5>
            <div class="js-sub-list">
                @foreach($subs as $si => $sub)
                    <div class="row js-sub-row" style="margin-bottom:8px;">
                        <div class="col-md-1">
                            <label class="js-sub-letter">{{ (int)$si + 1 }}.</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="{{ $prefix }}[sub_questions][{{ $si }}][text]" class="form-control" value="{{ $sub['text'] ?? '' }}" placeholder="Sub-question prompt">
                        </div>
                        <div class="col-md-2">
                            <input type="number" step="0.1" min="0" name="{{ $prefix }}[sub_questions][{{ $si }}][marks]" class="form-control js-sub-marks" value="{{ $sub['marks'] ?? '' }}" placeholder="Marks">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-xs btn-default js-remove-sub" title="Remove">&times;</button>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-xs btn-success js-add-sub"><i class="fa fa-plus"></i> Add sub-question</button>
            <p class="help-block m-b-none">Optional. Leave empty if the question has no parts.</p>
        </div>
    </div>
</div>
