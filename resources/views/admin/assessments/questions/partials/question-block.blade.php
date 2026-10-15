@php
    $index = $index ?? 0;
    $row = $row ?? [];
    $type = $row['type'] ?? 'mcq';
    $marks = $row['max_marks'] ?? 1;
    $stem = $row['stem'] ?? '';
    $explanation = $row['explanation'] ?? '';
    $topic = $row['topic'] ?? '';
    $opts = $row['options'] ?? [['text'=>''],['text'=>''],['text'=>''],['text'=>'']];
    $correct = (int) ($row['correct_option'] ?? 0);
    $prefix = 'questions[' . $index . ']';
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
            <div class="col-md-3 form-group">
                <label>Topic (optional override) <span class="lms-char-hint">(max 255)</span></label>
                <input type="text" name="{{ $prefix }}[topic]" class="form-control js-topic" data-max="255" maxlength="255" value="{{ $topic }}">
            </div>
            <div class="col-md-3 form-group">
                <label>Max marks *</label>
                <input type="number" step="0.1" min="0.1" name="{{ $prefix }}[max_marks]" class="form-control js-max-marks" value="{{ $marks }}" required>
            </div>
            <div class="col-md-12 form-group">
                <label>Question stem * <span class="lms-char-hint">(max 10,000 characters)</span></label>
                <textarea name="{{ $prefix }}[stem]" class="form-control text-editor" rows="4" data-max="10000" data-height="160" data-rich="1">{{ $stem }}</textarea>
            </div>
            <div class="col-md-12 form-group">
                <label>Explanation / model answer notes <span class="lms-char-hint">(max 10,000 characters)</span></label>
                <textarea name="{{ $prefix }}[explanation]" class="form-control text-editor" rows="3" data-max="10000" data-height="120" data-rich="1">{{ $explanation }}</textarea>
            </div>
        </div>
        <div class="js-mcq-options" style="{{ $type === 'mcq' ? '' : 'display:none;' }}">
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
    </div>
</div>
