<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>419 · Page Expired</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --navy: #000435;
            --red: #bc1701;
            --gold: #e6a60b
        }

        html,
        body {
            height: 100%
        }

        body {
            margin: 0;
            color: #fff;
            background:
                radial-gradient(1200px 800px at 80% 10%, rgba(230, 166, 11, .18) 0%, rgba(0, 4, 53, 0) 60%),
                radial-gradient(900px 600px at 10% 90%, rgba(188, 23, 1, .20) 0%, rgba(0, 4, 53, 0) 60%),
                var(--navy);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Ubuntu, "Helvetica Neue", Arial;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100%;
        }

        .wrap {
            text-align: center;
            padding: 24px
        }

        .kicker {
            letter-spacing: .2em;
            text-transform: uppercase;
            opacity: .9
        }

        .code {
            font-weight: 900;
            line-height: 1;
            letter-spacing: .02em;
            font-size: clamp(72px, 18vw, 220px);
            background: linear-gradient(90deg, var(--gold), var(--red));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 0 18px rgba(230, 166, 11, .25);
        }

        .title {
            font-weight: 800;
            margin-top: .25rem;
            font-size: clamp(20px, 3.5vw, 36px)
        }

        .lead {
            color: #cfd3ff;
            max-width: 780px;
            margin: 10px auto 24px
        }

        .btn-gold {
            background: var(--gold);
            border-color: var(--gold);
            color: #000435;
            font-weight: 700
        }

        .btn-gold:hover {
            filter: brightness(.92);
            color: #fff;
            border-color: var(--gold);
        }

        .btn-outline-red {
            border-color: var(--red);
            color: #fff;
            font-weight: 700
        }

        .btn-outline-red:hover {
            background: var(--red);
            color: #fff
        }

        .bar {
            height: 6px;
            background: linear-gradient(90deg, var(--gold), var(--red));
            width: min(680px, 90%);
            margin: 18px auto;
            border-radius: 999px;
            opacity: .95
        }

        footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            color: #8ea0ff;
            font-size: .85rem;
            padding: 10px 16px;
            text-align: center;
            pointer-events: none
        }
    </style>
</head>

<body>
    <main class="wrap container">
        <div class="kicker">Error</div>
        <div class="code">419</div>
        <div class="bar"></div>
        <h1 class="title">Page expired</h1>
        <p class="lead">Your session expired or the CSRF token is invalid. Refresh the page editor, sign in again if needed, then save.</p>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a href="javascript:history.back()" class="btn btn-gold px-4">Go Back &amp; Refresh</a>
            <a href="{{ public_login_url() }}" class="btn btn-outline-red px-4">Sign in</a>
        </div>
    </main>
    <footer>Copyright © 2025 Berkeley School of Business, Arts & Sciences</footer>
</body>

</html>