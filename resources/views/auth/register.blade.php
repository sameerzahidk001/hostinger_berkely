<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, follow">
  <title>{{ config('app.name') }} | Register</title>
  <style>
    body {
      color: #000;
      overflow-x: hidden;
      height: 100%;
      background-color: #B0BEC5;
      background-repeat: no-repeat;
    }

    .card0 {
      box-shadow: 0px 4px 8px 0px #757575;
      border-radius: 0px;
    }

    .card2 {
      margin: 0px 40px;
    }

    .logo {
      width: 200px;
      height: 100px;
      margin-top: 20px;
      margin-left: 35px;
    }

    .image {
      width: 360px;
      height: 280px;
    }

    .border-line {
      border-right: 1px solid #EEEEEE;
    }

    .facebook {
      background-color: #3b5998;
      color: #fff;
      font-size: 18px;
      padding-top: 5px;
      border-radius: 50%;
      width: 35px;
      height: 35px;
      cursor: pointer;
    }

    .twitter {
      background-color: #1DA1F2;
      color: #fff;
      font-size: 18px;
      padding-top: 5px;
      border-radius: 50%;
      width: 35px;
      height: 35px;
      cursor: pointer;
    }

    .linkedin {
      background-color: #2867B2;
      color: #fff;
      font-size: 18px;
      padding-top: 5px;
      border-radius: 50%;
      width: 35px;
      height: 35px;
      cursor: pointer;
    }

    .line {
      height: 1px;
      width: 45%;
      background-color: #E0E0E0;
      margin-top: 10px;
    }

    .or {
      width: 10%;
      font-weight: bold;
    }

    .text-sm {
      font-size: 14px !important;
    }

    ::placeholder {
      color: #BDBDBD;
      opacity: 1;
      font-weight: 300
    }

    :-ms-input-placeholder {
      color: #BDBDBD;
      font-weight: 300
    }

    ::-ms-input-placeholder {
      color: #BDBDBD;
      font-weight: 300
    }

    input,
    textarea,
    select {
      padding: 10px 12px 10px 12px;
      border: 1px solid lightgrey;
      border-radius: 2px;
      margin-bottom: 5px;
      margin-top: 2px;
      width: 100%;
      box-sizing: border-box;
      color: #2C3E50;
      font-size: 14px;
      letter-spacing: 1px;
      background: #fff;
    }

    input:focus,
    textarea:focus,
    select:focus {
      -moz-box-shadow: none !important;
      -webkit-box-shadow: none !important;
      box-shadow: none !important;
      border: 1px solid #304FFE;
      outline-width: 0;
    }

    .journal-grid .form-group-label {
      margin-bottom: 4px;
    }

    .journal-complete {
      border-top: 1px solid #e5e5e5;
      margin-top: 18px;
      padding-top: 16px;
    }

    .journal-complete h2 {
      font-size: 20px;
      font-weight: 700;
      color: #000435;
      margin: 0 0 8px;
    }

    .journal-alert-row {
      display: flex;
      flex-wrap: wrap;
      gap: 12px 18px;
      margin: 10px 0 14px;
    }

    .journal-check {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      font-size: 13px;
      line-height: 1.35;
      margin-bottom: 8px;
    }

    .journal-check input {
      width: auto;
      margin: 3px 0 0;
      padding: 0;
    }

    .journal-register-wrap {
      min-height: 100vh;
      background: #fff;
    }

    .journal-register-hero {
      width: 100%;
      max-height: 220px;
      overflow: hidden;
      background: #000435;
    }

    .journal-register-hero img {
      width: 100%;
      height: 220px;
      object-fit: cover;
      display: block;
    }

    .journal-register-body {
      max-width: 980px;
      margin: 0 auto;
      padding: 24px 20px 40px;
    }

    @media (max-width: 767px) {
      .journal-register-hero img {
        height: 140px;
      }
    }

    button:focus {
      -moz-box-shadow: none !important;
      -webkit-box-shadow: none !important;
      box-shadow: none !important;
      outline-width: 0;
    }

    a {
      color: inherit;
      cursor: pointer;
    }

    .btn-blue {
      background-color: #1A237E;
      width: 150px;
      color: #fff;
      border-radius: 2px;
    }

    .btn-blue:hover {
      background-color: #000;
      cursor: pointer;
    }

    .bg-blue {
      color: #fff;
      background-color: #1A237E;
    }

    .auth-heading {
      font-size: 22px;
      font-weight: 700;
      color: #000435;
      margin: 0 0 12px;
    }

    .login-logo {
      display: block;
      max-width: 280px;
      width: 100%;
    }

    .login-logo img {
      display: block;
      width: 100%;
      max-width: 280px;
      height: auto;
      object-fit: contain;
    }

    @media screen and (max-width: 991px) {
      .logo {
        margin-left: 0px;
      }

      .login-hero,
      .login-hero .card1,
      .login-hero .row {
        height: 110px !important;
      }

      .login-hero img.image {
        width: 100% !important;
        height: 110px !important;
        object-fit: cover;
      }

      .border-line {
        border-right: none;
      }

      .card2 {
        border-top: none !important;
        margin: 0 6px;
        height: auto !important;
        padding: 10px 10px 16px !important;
        justify-content: flex-start !important;
      }

      .login-logo,
      .login-logo img {
        max-width: 180px;
      }

      .auth-heading {
        font-size: 18px;
        margin: 4px 0 8px;
      }
    }

    .shadow {
      box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075) !important;
    }

    .card.radio-checked {
      transition: border-color 0.3s ease-in-out;
      cursor: pointer;
    }

    .radio-checked:has(input[type="radio"]:checked) {
      border-color: #304FFE !important;
    }
  </style>
  <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
  <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
  <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
</head>

<body>
  @if(!empty($isJournalRegister))
  <div class="journal-register-wrap">
    <div class="journal-register-hero">
      <img src="{{ asset('student/images/pngs/login.jpg') }}?v=20261127j" alt="Berkeley Journal">
    </div>
    <div class="journal-register-body">
      <form method="POST" action="{{ $registerAction ?? route('register') }}">
        @csrf
        @if(!empty($pathPrefix))
          <input type="hidden" name="path_prefix" value="{{ $pathPrefix }}">
        @endif
        <div class="mb-3">
          <a href="{{ $homeUrl ?? route('welcome') }}" class="login-logo">
            <img src="{{ ($authBrand['logo_url'] ?? null) ?: asset('frontend/images/pngs/header-logo.png') }}" alt="{{ optional($authBrand['branding'] ?? null)->name ?: 'Berkeley Journal' }}">
          </a>
        </div>
        <h1 class="auth-heading">Sign up</h1>
        <div class="row mb-3">
          @foreach ($roles as $key => $role)
            <div class="col-md-6">
              <label class="card rounded shadow radio-checked" for="{{ $role->name }}-radio" style="display:block;">
                <div class="card-body">
                  <input type="radio" name="role" value="{{ $role->id }}" id="{{ $role->name }}-radio" hidden {{ $key == 0 ? 'checked' : '' }}>
                  <h5 class="card-title">{{ role_display_name($role->name) }}</h5>
                  <p class="text-muted mb-0">{{ $role->description }}</p>
                </div>
              </label>
            </div>
          @endforeach
        </div>
        @error('role')
          <p class="text-danger text-xs italic">{{ $message }}</p>
        @enderror
                <div class="row journal-grid">
                  <div class="col-md-6 px-2">
                    <label class="mb-1 form-group-label"><h6 class="mb-0 text-sm">Your Fullname *</h6></label>
                    <input id="name" type="text" class="mb-2 @error('name') border-danger @enderror" name="name"
                      value="{{ old('name') }}" placeholder="Enter your fullname" required autocomplete="name" autofocus>
                    @error('name')<p class="text-danger text-xs italic">{{ $message }}</p>@enderror
                  </div>
                  <div class="col-md-6 px-2">
                    <label class="mb-1 form-group-label"><h6 class="mb-0 text-sm">Email Address *</h6></label>
                    <input class="mb-2 @error('email') border-danger @enderror" id="email" type="email" name="email"
                      placeholder="Enter a valid email address" value="{{ old('email') }}" required autocomplete="email">
                    @error('email')<p class="text-danger text-xs italic">{{ $message }}</p>@enderror
                  </div>
                  <div class="col-md-6 px-2">
                    <label class="mb-1 form-group-label"><h6 class="mb-0 text-sm">Mobile Number *</h6></label>
                    <input class="mb-2 @error('mobile_number') border-danger @enderror" id="mobile_number" type="tel" name="mobile_number"
                      placeholder="+11234567890" value="{{ old('mobile_number') }}" required
                      autocomplete="tel" pattern="^\+?[1-9]\d{0,2}\d{6,14}$"
                      title="Enter a valid mobile number with country code (e.g., +11234567890)">
                    @error('mobile_number')<p class="text-danger text-xs italic">{{ $message }}</p>@enderror
                  </div>
                  <div class="col-md-6 px-2">
                    <label class="mb-1 form-group-label"><h6 class="mb-0 text-sm">Please select a country/region. *</h6></label>
                    <select class="mb-2 @error('country') border-danger @enderror" name="country" required>
                      <option value="">Please select a country/region.</option>
                      @foreach(($countries ?? []) as $country)
                        <option value="{{ $country->iso_code }}" @selected(old('country') === $country->iso_code)>{{ $country->name }}</option>
                      @endforeach
                    </select>
                    @error('country')<p class="text-danger text-xs italic">{{ $message }}</p>@enderror
                  </div>
                  <div class="col-md-6 px-2">
                    <label class="mb-1 form-group-label"><h6 class="mb-0 text-sm">Please select a profession. *</h6></label>
                    <select class="mb-2 @error('profession') border-danger @enderror" name="profession" required>
                      <option value="">Please select a profession.</option>
                      @foreach(($professions ?? []) as $profession)
                        <option value="{{ $profession }}" @selected(old('profession') === $profession)>{{ $profession }}</option>
                      @endforeach
                    </select>
                    @error('profession')<p class="text-danger text-xs italic">{{ $message }}</p>@enderror

                    <label class="mb-1 form-group-label"><h6 class="mb-0 text-sm">Please select a specialization. *</h6></label>
                    <select class="mb-2 @error('specialization') border-danger @enderror" name="specialization" required>
                      <option value="">Please select a specialization.</option>
                      @foreach(($specializations ?? []) as $specialization)
                        <option value="{{ $specialization }}" @selected(old('specialization') === $specialization)>{{ $specialization }}</option>
                      @endforeach
                    </select>
                    @error('specialization')<p class="text-danger text-xs italic">{{ $message }}</p>@enderror
                  </div>
                  <div class="col-md-6 px-2">
                    <label class="mb-1 form-group-label"><h6 class="mb-0 text-sm">Please select a work setting. *</h6></label>
                    <select class="mb-2 @error('work_setting') border-danger @enderror" name="work_setting" required>
                      <option value="">Please select a work setting.</option>
                      @foreach(($workSettings ?? []) as $workSetting)
                        <option value="{{ $workSetting }}" @selected(old('work_setting') === $workSetting)>{{ $workSetting }}</option>
                      @endforeach
                    </select>
                    @error('work_setting')<p class="text-danger text-xs italic">{{ $message }}</p>@enderror
                  </div>
                  <div class="col-md-6 px-2">
                    <label class="mb-1 form-group-label"><h6 class="mb-0 text-sm">Password *</h6></label>
                    <input class="mb-2 @error('password') border-danger @enderror" id="password" type="password" placeholder="Enter password" name="password" required autocomplete="new-password">
                    @error('password')<p class="text-danger text-xs italic">{{ $message }}</p>@enderror
                  </div>
                  <div class="col-md-6 px-2">
                    <label class="mb-1 form-group-label"><h6 class="mb-0 text-sm">Confirm Password *</h6></label>
                    <input class="mb-2 @error('password_confirmation') border-danger @enderror" id="password_confirmation" type="password" placeholder="Confirm password" name="password_confirmation" required autocomplete="new-password">
                    @error('password_confirmation')<p class="text-danger text-xs italic">{{ $message }}</p>@enderror
                  </div>
                </div>

                <div class="row px-3 journal-complete">
                  <div class="col-12 px-2">
                    <h2>Complete registration</h2>
                    <p class="text-muted mb-2" style="font-size:13px;">Receive Berkeley Press journal email alerts</p>
                    <ul class="mb-2 pl-3" style="font-size:13px;color:#555;">
                      @foreach(($alertOptions ?? []) as $alertKey => $alertLabel)
                        <li>{{ $alertLabel }}</li>
                      @endforeach
                    </ul>
                    <div class="journal-alert-row">
                      @foreach(($alertOptions ?? []) as $alertKey => $alertLabel)
                        @php $short = ['toc' => 'TOC', 'online_now' => 'Online Now', 'published_articles' => 'Published Articles'][$alertKey] ?? $alertKey; @endphp
                        <label class="journal-check">
                          <input type="checkbox" name="alert_prefs[]" value="{{ $alertKey }}" @checked(in_array($alertKey, (array) old('alert_prefs', []), true))>
                          <span>{{ $short }}</span>
                        </label>
                      @endforeach
                    </div>
                    <p class="mb-2" style="font-size:13px;font-weight:600;">I wish to receive special offers and promotions about related products, services, and events from:</p>
                    @foreach(($marketingOptions ?? []) as $mKey => $mLabel)
                      <label class="journal-check">
                        <input type="checkbox" name="marketing_prefs[]" value="{{ $mKey }}" @checked(in_array($mKey, (array) old('marketing_prefs', []), true))>
                        <span>{{ $mLabel }}</span>
                      </label>
                    @endforeach
                    <label class="journal-check mt-3">
                      <input type="checkbox" name="terms" value="1" required @checked(old('terms'))>
                      <span>By clicking Register, I agree to the <a href="{{ url('/terms-and-conditions') }}" target="_blank" rel="noopener">Terms of Use</a> and <a href="{{ url('/privacy-policy') }}" target="_blank" rel="noopener">Privacy policy</a>.</span>
                    </label>
                    @error('terms')<p class="text-danger text-xs italic">{{ $message }}</p>@enderror
                  </div>
                </div>

                <div class="row mb-3 mt-3">
                  <div class="col-12">
                    <button type="submit" class="btn btn-blue text-center">Complete Registration</button>
                  </div>
                </div>
                <div class="row mb-2">
                  <div class="col-12">
                    <small class="font-weight-bold">Already have an account? <a href="{{ $loginUrl ?? route('login') }}" class="text-danger">Sign In</a></small>
                  </div>
                </div>
      </form>
    </div>
  </div>
  @else
  <div class="container-fluid px-0" style="min-height: 100vh;">
    <div class="card card0 border-0" style="height: 100%;">
      <div class="row d-flex">
        <div class="col-lg-6 order-1 order-lg-1 login-hero">
          <div class="card1" style="height: 100vh;">
            <div class="row px-0" style="height: 100%; margin: 0;">
              <img src="{{ asset('student/images/pngs/login.jpg') }}?v=20260927" class="image"
                style="width: 100%; height: 100%; object-fit: cover;" alt="EduBerkeley">
            </div>
          </div>
        </div>
        <div class="col-lg-6 order-2" style="background-color:white;">
          <form method="POST" action="{{ $registerAction ?? route('register') }}">
            @csrf
            @if(!empty($pathPrefix))
              <input type="hidden" name="path_prefix" value="{{ $pathPrefix }}">
            @endif
            <div class="card2 card border-0 d-flex flex-column justify-content-center px-4 py-5">
              <div class="row mb-2 px-3">
                <a href="{{ $homeUrl ?? route('welcome') }}" class="login-logo">
                  <img src="{{ ($authBrand['logo_url'] ?? null) ?: asset('frontend/images/pngs/header-logo.png') }}" alt="{{ optional($authBrand['branding'] ?? null)->name ?: 'Berkeley School of Business, Arts &amp; Sciences' }}">
                </a>
              </div>
              <div class="row px-3">
                <h1 class="auth-heading">Sign up</h1>
              </div>
              <div class="row px-3">
                @foreach ($roles as $key => $role)
                  <div class="col">
                    <label class="card rounded shadow radio-checked" for="{{ $role->name }}-radio">
                    <div class="card-body">
                      <input type="radio" name="role" value="{{ $role->id }}" id="{{ $role->name }}-radio" hidden
                      {{ $key == 0 ? 'checked' : '' }}>
                      <h5 class="card-title">{{ ucfirst($role->name) }}</h5>
                      <p class="text-muted">{{ $role->description }}</p>
                    </div>
                    </label>
                  </div>
                @endforeach
              </div>
              @error('role')
                <p class="text-danger text-xs italic">{{ $message }}</p>
              @enderror
              <div class="row px-3">
                <label class="mb-1">
                  <h6 class="mb-0 text-sm">Your Fullname</h6>
                </label>
                <input id="name" type="text" class="mb-2 form-control @error('name') is-invalid @enderror" name="name"
                  value="{{ old('name') }}" placeholder="Enter your fullname" required autocomplete="name"
                  autofocus>
                @error('name')
                  <p class="text-danger text-xs italic">{{ $message }}</p>
                @enderror
              </div>
              <div class="row px-3">
                <label class="mb-1">
                  <h6 class="mb-0 text-sm">Email Address</h6>
                </label>
                <input class="mb-2 @error('email') border-danger @enderror" id="email" type="email" name="email"
                  placeholder="Enter a valid email address" value="{{ old('email') }}" required
                  autocomplete="email" autofocus>
                @error('email')
                  <p class="text-danger text-xs italic">{{ $message }}</p>
                @enderror
              </div>
              <div class="row px-3">
                <label class="mb-1">
                  <h6 class="mb-0 text-sm">Mobile Number</h6>
                </label>
                <input class="mb-2 @error('mobile_number') border-danger @enderror" id="mobile_number" type="tel" name="mobile_number"
                  placeholder="+11234567890" value="{{ old('mobile_number') }}" required
                  autocomplete="mobile_number" autofocus pattern="^\+?[1-9]\d{0,2}\d{6,14}$"
                  title="Enter a valid mobile number with country code (e.g., +11234567890)">
                @error('mobile_number')
                  <p class="text-danger text-xs italic">{{ $message }}</p>
                @enderror
              </div>
              <div class="row px-3">
                <label class="mb-1">
                  <h6 class="mb-0 text-sm">Password</h6>
                </label>
                <input class="mb-2" id="password" type="password" placeholder="Enter password" name="password" required
                  autocomplete="current-password">
                @error('password')
                  <p class="text-danger text-xs italic">{{ $message }}</p>
                @enderror
              </div>
              <div class="row px-3">
                <label class="mb-1">
                  <h6 class="mb-0 text-sm">Confirm Password</h6>
                </label>
                <input id="password_confirmation" type="password" placeholder="Confirm password"
                  name="password_confirmation" required autocomplete="new-password">
                @error('password_confirmation')
                  <p class="text-danger text-xs italic">{{ $message }}</p>
                @enderror
              </div>
              <div class="row px-3 my-4">
                <div class="custom-control custom-checkbox custom-control-inline">
                  <input id="chk1" type="checkbox" name="chk" class="custom-control-input">
                  <label for="chk1" class="custom-control-label text-sm">Remember me</label>
                </div>
                <a href="{{ route('password.request') }}" class="ml-auto mb-0 text-sm">Forgot
                  Password?</a>
              </div>
              <div class="row mb-3 px-3">
                <button type="submit" class="btn btn-blue text-center">Sign up</button>
              </div>
              <div class="row mb-4 px-3">
                <small class="font-weight-bold">Already have an account ? <a href="{{ $loginUrl ?? route('login') }}"
                    class="text-danger">Sign In</a></small>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  @endif
</body>

</html>