@php
    \App\Models\User::ensureInstructorExtraColumns();
    $academicRows = old('education', $user->educationList());
    if ($academicRows === []) {
        $academicRows = [''];
    }
    $proQualRows = old('professional_qualifications', method_exists($user, 'professionalQualificationsList') ? $user->professionalQualificationsList() : []);
    if ($proQualRows === []) {
        $proQualRows = [''];
    }
@endphp

<h3 class="profile-section-heading">Professional Profile</h3>
<div class="form-group">
    <label for="short_description">Professional Profile <span id="short_char_count" class="text-muted">(0 / 500 Characters)</span></label>
    <textarea name="short_description" id="short_description" class="form-control" rows="3"
        placeholder="Brief professional profile…">{!! old('short_description', $user->short_description) !!}</textarea>
</div>

<h3 class="profile-section-heading">Academic Qualifications</h3>
<div class="form-group">
    <label for="education[]">Academic Qualifications</label>
    <div id="education-wrapper">
        @foreach($academicRows as $edu)
            <div class="form-group mb-2 education-group">
                <input type="text" name="education[]" class="form-control"
                    placeholder="e.g. MBA, MSc…" value="{{ $edu }}">
                @if(! $loop->first)
                    <button type="button" class="btn btn-danger btn-sm remove-education"
                        style="margin-left: 10px; float: right; margin-top: 10px;">Remove</button>
                @endif
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addEducationInput()">Add More</button>
</div>

<h3 class="profile-section-heading">Professional Qualifications &amp; Certifications</h3>
<div class="form-group">
    <label for="professional_qualifications[]">Professional Qualifications &amp; Certifications</label>
    <div id="pro-qual-wrapper">
        @foreach($proQualRows as $item)
            <div class="form-group mb-2 pro-qual-group">
                <input type="text" name="professional_qualifications[]" class="form-control"
                    placeholder="e.g. CFA, ACCA, PMP…" value="{{ $item }}">
                @if(! $loop->first)
                    <button type="button" class="btn btn-danger btn-sm remove-pro-qual"
                        style="margin-left: 10px; float: right; margin-top: 10px;">Remove</button>
                @endif
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-pro-qual-btn">Add More</button>
</div>

<h3 class="profile-section-heading">Executive &amp; Industry Experience</h3>
<div class="form-group">
    <label for="executive_experience">Executive &amp; Industry Experience <span id="executive_char_count" class="text-muted">(0 / 2000 Characters)</span></label>
    <textarea name="executive_experience" id="executive_experience" class="form-control" rows="4"
        placeholder="Describe executive and industry experience…">{!! old('executive_experience', $user->executive_experience) !!}</textarea>
</div>

<h3 class="profile-section-heading">Teaching &amp; Academic Experience</h3>
<div class="form-group">
    <label for="experience">Teaching &amp; Academic Experience <span id="experience_char_count" class="text-muted">(0 / 5000 Characters)</span></label>
    <textarea name="experience" id="experience" class="form-control" rows="4"
        placeholder="Describe teaching and academic experience…">{!! old('experience', $user->experience) !!}</textarea>
</div>

@php
    $recognitionOptions = \App\Models\User::teachingRecognitionOptions();
    $selectedRecognition = old('teaching_recognition', method_exists($user, 'teachingRecognitionList') ? $user->teachingRecognitionList() : []);
@endphp
<h3 class="profile-section-heading">Teaching Recognition and Certificates</h3>
<div class="form-group">
    <label for="teaching_recognition">Teaching Recognition and Certificates</label>
    <select name="teaching_recognition[]" id="teaching_recognition" class="form-control" multiple size="8">
        @foreach($recognitionOptions as $value => $label)
            <option value="{{ $value }}" @selected(in_array($value, (array) $selectedRecognition, true))>{{ $label }}</option>
        @endforeach
    </select>
    <span class="help-block">Hold Ctrl/Cmd to select multiple. Leave empty if none apply.</span>
</div>

<h3 class="profile-section-heading">Professional Training Expertise</h3>
<div class="form-group">
    <label for="training_expertise">Professional Training Expertise <span id="training_char_count" class="text-muted">(0 / 2000 Characters)</span></label>
    <textarea name="training_expertise" id="training_expertise" class="form-control" rows="4"
        placeholder="Describe professional training expertise…">{!! old('training_expertise', $user->training_expertise) !!}</textarea>
</div>

<h3 class="profile-section-heading">Corporate &amp; Executive Training Experience</h3>
<div class="form-group">
    <label for="corporate_training">Corporate &amp; Executive Training Experience <span id="corporate_char_count" class="text-muted">(0 / 2000 Characters)</span></label>
    <textarea name="corporate_training" id="corporate_training" class="form-control" rows="4"
        placeholder="Describe corporate and executive training experience…">{!! old('corporate_training', $user->corporate_training) !!}</textarea>
</div>

<h3 class="profile-section-heading">Professional &amp; Academic Specialisations</h3>
@php
    $expertiseRows = old('expertise', $user->expertiseList());
    if ($expertiseRows === []) {
        $expertiseRows = [''];
    }
@endphp
<div class="form-group">
    <label for="expertise[]">Professional &amp; Academic Specialisations</label>
    <div id="expertise-wrapper">
        @foreach($expertiseRows as $i => $item)
            <div class="form-group mb-2 expertise-group">
                <input type="text" name="expertise[]" class="form-control" placeholder="Enter specialisation" value="{{ $item }}">
                @if($i > 0)
                    <button type="button" class="btn btn-danger btn-sm remove-expertise" style="margin-left:10px;float:right;margin-top:10px;">Remove</button>
                @endif
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-expertise-btn">Add More</button>
</div>

<h3 class="profile-section-heading">Institutions &amp; Organisations</h3>
<div class="form-group">
    <label for="institutions">Institutions &amp; Organisations <span id="institutions_char_count" class="text-muted">(0 / 2000 Characters)</span></label>
    <textarea name="institutions" id="institutions" class="form-control" rows="4"
        placeholder="List institutions and organisations…">{!! old('institutions', $user->institutions) !!}</textarea>
</div>
