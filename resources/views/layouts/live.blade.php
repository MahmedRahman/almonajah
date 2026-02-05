<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <title>@yield('title', 'بث مباشر - المناجاة')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #188781;
            --text-primary: #1f2937;
            --bg-primary: #000000;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Alexandria', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
            height: 100vh;
            width: 100vw;
        }
        .live-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .live-container video {
            width: 100%;
            height: 100%;
            object-fit: contain;
            position: relative;
            z-index: 1;
        }
        .live-exit {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: rgba(0,0,0,0.7);
            color: #fff;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background 0.2s;
            pointer-events: auto;
        }
        .live-exit:hover {
            background: rgba(0,0,0,0.9);
            color: #fff;
        }
        .live-loading {
            position: fixed;
            inset: 0;
            z-index: 100;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        .live-loading.hidden {
            display: none;
        }
        .live-loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(255,255,255,0.2);
            border-top-color: #fff;
            border-radius: 50%;
            animation: live-spin 0.8s linear infinite;
        }
        @keyframes live-spin {
            to { transform: rotate(360deg); }
        }
        .live-controls {
            position: fixed;
            bottom: 1.5rem;
            left: 1rem;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            pointer-events: none;
        }
        .live-controls button {
            width: 48px;
            height: 48px;
            border: none;
            border-radius: 50%;
            background: rgba(0,0,0,0.6);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            cursor: pointer;
            pointer-events: auto;
            transition: background 0.2s;
        }
        .live-controls button:hover {
            background: rgba(0,0,0,0.8);
        }
        .live-cast-icon {
            width: 1.25rem;
            height: 1.25rem;
        }
    </style>
    <script>window.__onGCastApiAvailable = function(isAvailable) { window.__castApiAvailable = isAvailable; };</script>
    <script src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>
    @stack('styles')
</head>
<body>
    <a href="{{ route('home') }}" class="live-exit" id="liveExit" aria-label="العودة">
        <i class="bi bi-box-arrow-in-down-left"></i>
        <span>خروج</span>
    </a>
    <div class="live-container">
        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
