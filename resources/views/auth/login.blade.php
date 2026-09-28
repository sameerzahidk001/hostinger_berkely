<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} | Login</title>
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

input, textarea {
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
}

input:focus, textarea:focus {
    -moz-box-shadow: none !important;
    -webkit-box-shadow: none !important;
    box-shadow: none !important;
    border: 1px solid #304FFE;
    outline-width: 0;
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
    min-height: 48px;
    padding: 12px 18px;
    color: #fff;
    border-radius: 2px;
    font-size: 16px;
    font-weight: 600;
}

.btn-blue:hover {
    background-color: #000;
    cursor: pointer;
}

.login-after-btn {
    margin-top: 8px !important;
    margin-bottom: 0 !important;
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

@media screen and (max-width: 991px) {
    .logo {
        margin-left: 0px;
    }

    .container-fluid,
    .card0 {
        height: auto !important;
        min-height: 0 !important;
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
        min-height: 0 !important;
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

    .btn-blue {
        min-height: 40px;
        padding: 8px 16px;
        font-size: 15px;
    }

    input, textarea {
        padding: 8px 10px;
        margin-bottom: 2px;
        margin-top: 0;
    }

    .login-captcha-hint {
        display: none;
    }

    .login-remember {
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 6px !important;
    }

    .login-remember a {
        margin-left: 0 !important;
    }
}
    </style>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
</head>
<body>

    <div class="container-fluid px-0">
        <div class="card card0 border-0">
            <div class="row d-flex">
                <div class="col-lg-6 order-2 order-lg-1" style="background-color:white;">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="card2 card border-0 d-flex flex-column justify-content-center px-4 py-5">
                            <div class="row mb-2 px-3">
                                <a href="{{ route('welcome') }}" class="login-logo">
                                    <img src="{{ asset('frontend/images/pngs/header-logo.png') }}" alt="Berkeley School of Business, Arts &amp; Sciences">
                                </a>
                            </div>
                            <div class="row px-3">
                                <h1 class="auth-heading">Sign in</h1>
                            </div>
                            <div class="row px-3">
                                <label class="mb-1"><h6 class="mb-0 text-sm">Email Address</h6></label>
                                <input class="mb-2 @error('email') border-danger @enderror" id="email" type="email" name="email" placeholder="Enter a valid email address" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                @error('email')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="row px-3 mb-3">
                                <label class="mb-1"><h6 class="mb-0 text-sm">Password</h6></label>
                                <input id="password" type="password" placeholder="Enter password" name="password" required autocomplete="current-password">
                                @error('password')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="row px-3 mb-2" style="margin-top:8px;">
                                <label class="mb-1"><h6 class="mb-0 text-sm">Captcha</h6></label>
                                <div class="d-flex align-items-center flex-wrap login-captcha" style="gap:8px;">
                                    <img id="login-captcha-img" src="{{ route('login.captcha') }}?t={{ time() }}" alt="Captcha" width="140" height="40"
                                        style="border:1px solid #ced4da;border-radius:4px;background:#f5f7fa;display:block;max-width:100%;height:40px;">
                                    <button type="button" id="login-captcha-refresh" class="btn btn-sm btn-outline-secondary" title="Refresh captcha"
                                        style="border:1px solid #ced4da;background:#fff;padding:6px 10px;cursor:pointer;">↻</button>
                                    <input class="mb-0 @error('captcha') border-danger @enderror" style="max-width:140px;flex:1;min-width:110px;"
                                        type="text" name="captcha" maxlength="8" placeholder="Enter code" required autocomplete="off" autocapitalize="characters">
                                </div>
                                <small class="text-muted d-block mt-1 login-captcha-hint">Type the characters shown in the image.</small>
                                @error('captcha')
                                    <p class="text-danger text-xs italic mb-0 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="row px-3 mb-2 login-remember" style="margin-top:8px;">
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input id="chk1" type="checkbox" name="chk" class="custom-control-input">
                                    <label for="chk1" class="custom-control-label text-sm">Remember me</label>
                                </div>
                                <a href="{{ route('password.request') }}" class="ml-auto mb-0 text-sm">Forgot Password?</a>
                            </div>
                            <div class="row mb-2 px-3">
                                <button type="submit" class="btn btn-blue text-center">Sign in</button>
                            </div>
                            <div class="row px-3 login-after-btn">
                                <small class="font-weight-bold">Don't have an account ? <a href="{{ route('register') }}" class="text-danger">Register</a></small>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-lg-6 order-1 order-lg-2 login-hero">
                    <div class="card1" style="height: 100vh;">
                        <div class="row px-0" style="height: 100%; margin: 0;">
                            <img src="{{ asset('student/images/pngs/login.jpg') }}?v=20260927" class="image" style="width: 100%; height: 100%; object-fit: cover;" alt="EduBerkeley">
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <script>
        (function () {
            var img = document.getElementById('login-captcha-img');
            var btn = document.getElementById('login-captcha-refresh');
            if (!img || !btn) return;
            btn.addEventListener('click', function () {
                img.src = @json(route('login.captcha')) + '?refresh=1&t=' + Date.now();
            });
        })();
    </script>
</body>
</html>