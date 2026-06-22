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

@media screen and (max-width: 991px) {
    .logo {
        margin-left: 0px;
    }

    .image {
        width: 300px;
        height: 220px;
    }

    .border-line {
        border-right: none;
    }

    .card2 {
        border-top: 1px solid #EEEEEE !important;
        margin: 0px 15px;
    }
    
}
    </style>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
</head>
<body>

    <div class="container-fluid px-0" style="height:100vh;">
        <div class="card card0 border-0" style="height:100vh;">
            <div class="row d-flex">
                <div class="col-lg-6">
                    <div class="card1" style="height: 100vh;"> <!-- Set the height of the card to fill the viewport height -->
                        <div class="row px-0 border-line" style="height: 100%;"> <!-- Ensure the row takes full height -->
                            <img src="{{ asset('student/images/pngs/login.png') }}" class="image" style="width: 100%; height: 100%; object-fit: cover;" alt="">
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" style="background-color:white;">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="card2 card border-0 d-flex flex-column justify-content-center px-4 py-5" style="height: 100vh;">
                            <!-- <div class="row mb-4 px-3">
                                <h6 class="mb-0 mr-4 mt-2">Sign in with</h6>
                                <div class="facebook text-center mr-3"><div class="fa fa-facebook"></div></div>
                                <div class="twitter text-center mr-3"><div class="fa fa-twitter"></div></div>
                                <div class="linkedin text-center mr-3"><div class="fa fa-linkedin"></div></div>
                            </div> -->
                            <div class="row mb-4 px-3">
                                <a href="{{ route('welcome') }}">
                                    <img src="{{ asset('frontend/images/pngs/header-logo.png') }}" alt="logo">
                                </a>
                            </div>
                            <!-- <div class="row px-3 mb-4">
                                <div class="line"></div>
                                <small class="or text-center">Or</small>
                                <div class="line"></div>
                            </div> -->
                            <div class="row px-3">
                                <label class="mb-1"><h6 class="mb-0 text-sm">Email Address</h6></label>
                                <input class="mb-2 @error('email') border-danger @enderror" id="email" type="email" name="email" placeholder="Enter a valid email address" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                @error('email')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="row px-3">
                                <label class="mb-1"><h6 class="mb-0 text-sm">Password</h6></label>
                                <input id="password" type="password" placeholder="Enter password" name="password" required autocomplete="current-password">
                                @error('password')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="row px-3 my-4">
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input id="chk1" type="checkbox" name="chk" class="custom-control-input"> 
                                    <label for="chk1" class="custom-control-label text-sm">Remember me</label>
                                </div>
                                <a href="{{ route('password.request') }}" class="ml-auto mb-0 text-sm">Forgot Password?</a>
                            </div>
                            <div class="row mb-3 px-3">
                                <button type="submit" class="btn btn-blue text-center">Login</button>
                            </div>
                            <div class="row mb-4 px-3">
                                <small class="font-weight-bold">Don't have an account ? <a href="{{ route('register') }}"class="text-danger">Register</a></small>
                            </div>
                        </div>
                    </form>
                </div>


            </div>
            
        </div>
    </div>

</body>
</html>