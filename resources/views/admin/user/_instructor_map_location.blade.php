@php
    \App\Models\User::ensureLatLngColumns();
    $lat = old('latitude', $user->latitude);
    $lng = old('longitude', $user->longitude);
    $hasLoc = filled($lat) && filled($lng);
@endphp
<div class="form-group mt-3" id="instructor-map-location">
    <label>Map location (for “Find trainers nearby”)</label>
    <p class="help-block" style="margin-bottom:8px;">
        Set the instructor’s real location so students can find them by distance. Use the button to capture GPS, or paste coordinates.
    </p>
    <div class="row">
        <div class="col-sm-4 form-group">
            <label for="latitude">Latitude</label>
            <input type="number" step="any" name="latitude" id="latitude" class="form-control"
                value="{{ $lat }}" placeholder="e.g. 25.2048">
        </div>
        <div class="col-sm-4 form-group">
            <label for="longitude">Longitude</label>
            <input type="number" step="any" name="longitude" id="longitude" class="form-control"
                value="{{ $lng }}" placeholder="e.g. 55.2708">
        </div>
        <div class="col-sm-4 form-group">
            <label>&nbsp;</label>
            <div>
                <button type="button" class="btn btn-default btn-block" id="use-my-location-btn">
                    Use my current location
                </button>
            </div>
        </div>
    </div>
    <p id="map-location-status" class="text-muted" style="margin-top:4px;">
        @if($hasLoc)
            Location saved ({{ number_format((float) $lat, 5) }}, {{ number_format((float) $lng, 5) }}).
        @else
            No map location set yet.
        @endif
    </p>
    <button type="button" class="btn btn-link btn-sm" id="clear-map-location-btn" style="padding-left:0;">Clear map location</button>
</div>
<script>
(function () {
    var statusEl = document.getElementById('map-location-status');
    var latEl = document.getElementById('latitude');
    var lngEl = document.getElementById('longitude');
    var btn = document.getElementById('use-my-location-btn');
    var clearBtn = document.getElementById('clear-map-location-btn');
    if (!btn || !latEl || !lngEl) return;

    btn.addEventListener('click', function () {
        if (!navigator.geolocation) {
            if (statusEl) statusEl.textContent = 'Geolocation is not supported in this browser.';
            return;
        }
        btn.disabled = true;
        if (statusEl) statusEl.textContent = 'Detecting location…';
        navigator.geolocation.getCurrentPosition(function (pos) {
            latEl.value = pos.coords.latitude.toFixed(7);
            lngEl.value = pos.coords.longitude.toFixed(7);
            if (statusEl) {
                statusEl.textContent = 'Location captured. Save the profile to keep it.';
            }
            btn.disabled = false;
        }, function (err) {
            if (statusEl) statusEl.textContent = 'Could not get location: ' + (err.message || 'permission denied');
            btn.disabled = false;
        }, { enableHighAccuracy: true, timeout: 15000 });
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            latEl.value = '';
            lngEl.value = '';
            if (statusEl) statusEl.textContent = 'Map location cleared. Save the profile to apply.';
        });
    }
})();
</script>
