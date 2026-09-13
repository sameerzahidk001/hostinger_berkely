<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Document')</title>
    <style>
        body {
            margin: 0;
            padding: 16px;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 13px;
            line-height: 1.45;
            background: #fff;
        }
        a { color: #333; text-decoration: none; }
        img { max-width: 100%; }
    </style>
    @stack('style')
</head>
<body>
    @yield('content')
</body>
</html>
