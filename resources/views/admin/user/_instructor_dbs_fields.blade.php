@php
    \App\Models\User::ensureInstructorExtraColumns();
    $dbs = old('dbs', $user->dbsBackgroundData());
    $holds = old('dbs.holds_certificate', $dbs['holds_certificate'] ?? '');
    $checkLevels = \App\Models\User::dbsCheckLevelOptions();
    $updateStatuses = \App\Models\User::dbsUpdateServiceStatusOptions();
    $certificateFile = $dbs['certificate_file'] ?? '';
@endphp

<h3 class="profile-section-heading">DBS / Background Check Information</h3>

<div id="dbs-background-section">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="dbs_holds_certificate">Do you currently hold a DBS Certificate?</label>
            <select name="dbs[holds_certificate]" id="dbs_holds_certificate" class="form-control">
                <option value="">Select…</option>
                <option value="yes" {{ $holds === 'yes' ? 'selected' : '' }}>Yes</option>
                <option value="no" {{ $holds === 'no' ? 'selected' : '' }}>No</option>
            </select>
        </div>
    </div>

    <div id="dbs-certificate-fields" style="{{ $holds === 'yes' ? '' : 'display:none;' }}">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="dbs_check_level">DBS Check Level</label>
                <select name="dbs[check_level]" id="dbs_check_level" class="form-control">
                    <option value="">Select…</option>
                    @foreach($checkLevels as $value => $label)
                        <option value="{{ $value }}" {{ ($dbs['check_level'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="dbs_issue_date">DBS Certificate Issue Date</label>
                <input type="date" name="dbs[issue_date]" id="dbs_issue_date" class="form-control"
                    value="{{ old('dbs.issue_date', $dbs['issue_date'] ?? '') }}"
                    max="{{ date('Y-m-d') }}"
                    placeholder="DD/MM/YYYY">
            </div>
            <div class="col-md-6 mb-3">
                <label for="dbs_certificate_number">DBS Certificate Number</label>
                <input type="text" name="dbs[certificate_number]" id="dbs_certificate_number" class="form-control"
                    value="{{ old('dbs.certificate_number', $dbs['certificate_number'] ?? '') }}"
                    placeholder="Enter certificate number" maxlength="100">
            </div>
            <div class="col-md-6 mb-3">
                <label for="dbs_update_service_registered">Registered with the DBS Update Service?</label>
                <select name="dbs[update_service_registered]" id="dbs_update_service_registered" class="form-control">
                    <option value="">Select…</option>
                    <option value="yes" {{ ($dbs['update_service_registered'] ?? '') === 'yes' ? 'selected' : '' }}>Yes</option>
                    <option value="no" {{ ($dbs['update_service_registered'] ?? '') === 'no' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="dbs_update_service_status">DBS Update Service Status</label>
                <select name="dbs[update_service_status]" id="dbs_update_service_status" class="form-control">
                    <option value="">Select…</option>
                    @foreach($updateStatuses as $value => $label)
                        <option value="{{ $value }}" {{ ($dbs['update_service_status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="dbs_name_on_certificate">Name on DBS Certificate</label>
                <input type="text" name="dbs[name_on_certificate]" id="dbs_name_on_certificate" class="form-control"
                    value="{{ old('dbs.name_on_certificate', $dbs['name_on_certificate'] ?? '') }}"
                    placeholder="As shown on certificate" maxlength="255">
            </div>
            <div class="col-md-12 mb-3">
                <label for="dbs_certificate_file">DBS Certificate Upload</label>
                <input type="file" name="dbs_certificate_file" id="dbs_certificate_file" class="form-control"
                    accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                <small class="text-muted">PDF, JPG or PNG (max 5MB).</small>
                @if($certificateFile)
                    <div class="mt-2">
                        <a href="{{ asset($certificateFile) }}" target="_blank" rel="noopener">View current certificate</a>
                        <label class="ml-3" style="font-weight: normal;">
                            <input type="checkbox" name="dbs_remove_certificate" value="1"> Remove uploaded file
                        </label>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="dbs-willing-fields" style="{{ $holds === 'no' ? '' : 'display:none;' }}">
        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="dbs_willing_to_undergo">
                    If you do not currently hold a DBS Certificate, are you willing to undergo a DBS check if required for your teaching role?
                </label>
                <select name="dbs[willing_to_undergo]" id="dbs_willing_to_undergo" class="form-control">
                    <option value="">Select…</option>
                    <option value="yes" {{ ($dbs['willing_to_undergo'] ?? '') === 'yes' ? 'selected' : '' }}>Yes</option>
                    <option value="no" {{ ($dbs['willing_to_undergo'] ?? '') === 'no' ? 'selected' : '' }}>No</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-group mb-3">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="dbs[declaration]" id="dbs_declaration" value="1"
                {{ !empty($dbs['declaration']) || old('dbs.declaration') ? 'checked' : '' }}>
            <label class="form-check-label" for="dbs_declaration" style="font-weight: normal;">
                I confirm that the DBS information provided above is accurate and current. I understand that Berkeley School of Business may request verification of my DBS status where this is relevant to my teaching or safeguarding responsibilities.
            </label>
        </div>
    </div>
</div>

<script>
(function () {
    var holdsSelect = document.getElementById('dbs_holds_certificate');
    var certFields = document.getElementById('dbs-certificate-fields');
    var willingFields = document.getElementById('dbs-willing-fields');
    if (!holdsSelect || !certFields || !willingFields) return;

    function syncDbsFields() {
        var val = holdsSelect.value;
        certFields.style.display = val === 'yes' ? '' : 'none';
        willingFields.style.display = val === 'no' ? '' : 'none';
    }

    holdsSelect.addEventListener('change', syncDbsFields);
    syncDbsFields();
})();
</script>
