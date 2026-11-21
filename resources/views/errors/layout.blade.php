<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="color-scheme: light only;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <meta name="robots" content="noindex">
    <title>@yield('title') · SpeakLoudMate</title>
    <link rel="icon" href="{{ asset('images/favicon-32.webp') }}" type="image/webp">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800,900&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light only;
            --cream: #FFF8F0;
            --ink: #3D2B1F;
            --accent: #FF8C42;
            --accent-hover: #e67a35;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            min-height: 100%;
        }

        body {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background:
                radial-gradient(ellipse 80% 55% at 15% -10%, rgba(255, 140, 66, 0.18), transparent 55%),
                radial-gradient(ellipse 70% 50% at 95% 10%, rgba(255, 140, 66, 0.1), transparent 50%),
                radial-gradient(ellipse 60% 40% at 50% 110%, rgba(61, 43, 31, 0.05), transparent 55%),
                var(--cream);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .shell {
            width: 100%;
            max-width: 42rem;
            margin: 0 auto;
            padding: 1.5rem 1.25rem 3rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 3.5rem;
        }

        .brand img {
            height: 28px;
            width: auto;
            display: block;
        }

        .brand span {
            font-weight: 700;
            font-size: 15px;
            letter-spacing: -0.02em;
            color: var(--ink);
        }

        .panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 1rem 0 2rem;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(255, 140, 66, 0.1);
            color: var(--accent);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 0.3rem 0.75rem;
            border-radius: 999px;
            margin-bottom: 1.25rem;
            width: fit-content;
        }

        .eyebrow::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--accent);
        }

        .code {
            font-size: clamp(4.5rem, 18vw, 7.5rem);
            font-weight: 900;
            line-height: 0.9;
            letter-spacing: -0.06em;
            color: var(--accent);
            margin: 0 0 1rem;
            opacity: 0.92;
        }

        h1 {
            margin: 0 0 0.85rem;
            font-size: clamp(1.75rem, 5vw, 2.5rem);
            font-weight: 900;
            letter-spacing: -0.03em;
            line-height: 1.1;
            color: var(--ink);
        }

        .lead {
            margin: 0 0 2rem;
            max-width: 28rem;
            font-size: 15px;
            line-height: 1.6;
            color: rgba(61, 43, 31, 0.55);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            padding: 0.7rem 1.4rem;
            border-radius: 999px;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 2px 12px rgba(255, 140, 66, 0.35);
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        .btn-secondary {
            color: rgba(61, 43, 31, 0.7);
            border: 1px solid rgba(61, 43, 31, 0.15);
            background: transparent;
        }

        .btn-secondary:hover {
            color: var(--ink);
            border-color: rgba(61, 43, 31, 0.3);
        }

        .foot {
            margin-top: auto;
            padding-top: 2rem;
            font-size: 12px;
            color: rgba(61, 43, 31, 0.3);
        }

        .foot a:hover {
            color: var(--ink);
        }
    </style>
</head>
<body>
    <div class="shell">
        <a href="{{ url('/') }}" class="brand" aria-label="SpeakLoudMate home">
            <img
                src="{{ asset('images/logo-mark.webp') }}"
                width="40"
                height="28"
                alt="SpeakLoudMate"
                decoding="async"
            >
            <span>SpeakLoudMate</span>
        </a>

        <div class="panel">
            @yield('content')
        </div>

        <p class="foot">
            © {{ date('Y') }} SpeakLoudMate
        </p>
    </div>
</body>
</html>
