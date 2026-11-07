<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? 'Email Notification' }}</title>
    <style>
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: #bc1701;
            color: #ffffff;
            text-align: center;
            padding: 20px;
            font-size: 24px;
            font-weight: bold;
        }
        .logo {
            max-width: 250px;
        }
        .content {
            padding: 20px;
            font-size: 16px;
            color: #333;
        }
        .footer {
            text-align: center;
            padding: 15px;
            font-size: 14px;
            color: #777;
            border-top: 1px solid #ddd;
        }
        .btn {
            display: inline-block;
            background: #2f4050;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 16px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            @php
                $emailLogo = null;
                foreach ([
                    public_path('frontend/images/pngs/header-logo-white.png'),
                    public_path('frontend/images/pngs/header-logo.png'),
                    public_path('images/white_logo.png'),
                    public_path('images/logo.png'),
                ] as $logoPath) {
                    if (is_string($logoPath) && is_file($logoPath) && filesize($logoPath) > 32) {
                        $emailLogo = $logoPath;
                        break;
                    }
                }
            @endphp
            @if($emailLogo && isset($message))
                <img src="{{ $message->embed($emailLogo) }}" alt="Berkeley School of Business, Arts &amp; Sciences" class="logo">
                <br>
            @endif
            {{ $subject ?? 'Email Notification' }}
        </div>

        <div class="content">
            {!! $body !!}
        </div>

        <div class="footer">
            &copy; Berkeley School of Business, Arts & Sciences. All rights reserved.
        </div>
    </div>

</body>
</html>