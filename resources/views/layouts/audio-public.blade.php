<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <script>
    (function () {
        var NEXT_KEY = 'almonajah_theme';
        var LEGACY_KEYS = ['site_theme', 'almonajahAudioTheme'];
        var root = document.documentElement;
        var t = null;
        try {
            t = localStorage.getItem(NEXT_KEY);
            if (t !== 'dark' && t !== 'light') {
                for (var i = 0; i < LEGACY_KEYS.length; i++) {
                    var legacy = localStorage.getItem(LEGACY_KEYS[i]);
                    if (legacy === 'dark' || legacy === 'light') {
                        t = legacy;
                        break;
                    }
                }
            }
        } catch (e) {}
        var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        var theme = (t === 'dark' || t === 'light') ? t : (prefersDark ? 'dark' : 'light');
        root.setAttribute('data-theme', theme);
        root.classList.toggle('dark-mode', theme === 'dark');
    })();
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <title>@yield('title', 'المنصة الصوتية - المناجاة')</title>
    
    @yield('meta')
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-5W9T0JNV5D"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-5W9T0JNV5D');
    </script>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/public.css') }}?v=10">
    <style>
        .audio-global-sticky {
            position: fixed;
            inset-inline: 0;
            bottom: 0;
            z-index: 1190;
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.55rem 1rem;
            background: rgba(10, 10, 10, 0.96);
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            direction: rtl;
        }
        .audio-global-sticky.is-visible { display: flex; }
        .audio-global-sticky__meta { display: flex; align-items: center; gap: 0.55rem; min-width: 0; }
        .audio-global-sticky__meta-link { display: flex; align-items: center; gap: 0.7rem; min-width: 0; }
        .audio-global-sticky__thumb { width: 46px; height: 46px; border-radius: 8px; object-fit: cover; flex-shrink: 0; }
        .audio-global-sticky__text { min-width: 0; }
        .audio-global-sticky__title {
            color: #f5f5f5; font-size: 0.9rem; font-weight: 700; line-height: 1.2;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .audio-global-sticky__sub {
            color: #aaa; font-size: 0.8rem;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .audio-global-sticky__controls { display: inline-flex; align-items: center; gap: 0.4rem; flex-shrink: 0; }
        .audio-global-sticky__transport { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 0.2rem; }
        .audio-global-sticky__btn--play { width: 2.45rem; height: 2.45rem; background: #fff; color: #111; border-color: rgba(255,255,255,0.95); }
        .audio-global-sticky__btn--play:hover { background: #f3f4f6; color: #111; }
        .audio-global-sticky__btn--text { color: #22c55e; border-color: rgba(34,197,94,0.45); }
        .audio-global-sticky__btn--text.is-active { background: rgba(34,197,94,0.18); }
        .audio-global-sticky__btn--text-side {
            width: 46px;
            height: 46px;
            border-radius: 8px;
            flex-shrink: 0;
            font-size: 1.2rem;
            background: rgba(255,255,255,0.12);
        }
        .audio-global-sticky__text-panel {
            position: absolute; bottom: calc(100% + 10px); right: 0; width: min(760px, 95vw);
            max-height: min(72vh, 560px); overflow: auto; display: none; padding: 1rem 1.1rem;
            background: #0f0f0f; border: 1px solid rgba(255,255,255,0.13); border-radius: 12px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.5);
        }
        .audio-global-sticky__text-panel.is-open { display: block; }
        .audio-global-sticky__text-title { font-size: 1rem; color: #e5e7eb; margin: 0 0 0.75rem; font-weight: 700; }
        .audio-global-sticky__text-body { font-size: 1rem; color: #d1d5db; line-height: 2; white-space: pre-wrap; }
        .audio-global-sticky__text-segment {
            display: block;
            padding: 0.35rem 0.45rem;
            border-radius: 8px;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .audio-global-sticky__text-segment.is-active {
            background: rgba(34, 197, 94, 0.2);
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(34, 197, 94, 0.45);
        }
        .audio-global-sticky__middle { flex: 1; min-width: 180px; max-width: 520px; display: flex; flex-direction: column; gap: 0.2rem; }
        .audio-global-sticky__timeline { display: flex; align-items: center; gap: 0.5rem; }
        .audio-global-sticky__time { color: #c8c8c8; font-size: 0.72rem; min-width: 2.2rem; text-align: center; }
        .audio-global-sticky__seek { width: 100%; accent-color: #188781; direction: ltr; }
        .audio-global-sticky__volume { width: 90px; accent-color: #188781; }
        .audio-global-sticky__queue-wrap { position: relative; }
        .audio-global-sticky__queue-panel {
            position: fixed;
            top: 0;
            left: 0;
            width: min(420px, 92vw);
            height: 100vh;
            max-height: 100vh;
            overflow: auto;
            background: linear-gradient(180deg, #111 0%, #0b0b0b 100%);
            border-right: 1px solid rgba(255,255,255,0.12);
            border-radius: 0 14px 14px 0;
            box-shadow: 0 12px 34px rgba(0,0,0,0.52);
            display: block;
            padding: 0.6rem;
            z-index: 1320;
            transform: translateX(calc(-100% - 16px));
            opacity: 0;
            pointer-events: none;
            transition: transform 0.24s ease, opacity 0.24s ease;
        }
        .audio-global-sticky__queue-panel.is-open {
            transform: translateX(0);
            opacity: 1;
            pointer-events: auto;
        }
        .audio-global-sticky__queue-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.35rem 0.5rem 0.45rem; border-bottom: 1px solid rgba(255,255,255,0.09);
            margin-bottom: 0.3rem;
        }
        .audio-global-sticky__queue-title-main { color: #fff; font-weight: 700; font-size: 0.86rem; }
        .audio-global-sticky__queue-count { color: #9ca3af; font-size: 0.74rem; }
        .audio-global-sticky__queue-item {
            width: 100%; text-align: right; border: 0; background: transparent; color: #eee;
            display: grid; grid-template-columns: 40px 1fr auto; gap: 0.6rem; align-items: center;
            padding: 0.48rem; border-radius: 10px; cursor: pointer;
        }
        .audio-global-sticky__queue-item:hover { background: rgba(255,255,255,0.08); }
        .audio-global-sticky__queue-item.is-upnext { background: rgba(24, 135, 129, 0.18); }
        .audio-global-sticky__queue-item img { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; }
        .audio-global-sticky__queue-title { font-size: 0.86rem; font-weight: 600; line-height: 1.2; }
        .audio-global-sticky__queue-sub { font-size: 0.74rem; color: #aaa; }
        .audio-global-sticky__queue-badge {
            font-size: 0.68rem; color: #d1fae5; background: rgba(24, 135, 129, 0.25);
            border: 1px solid rgba(24, 135, 129, 0.55); border-radius: 999px; padding: 0.14rem 0.45rem;
        }
        .audio-global-sticky__queue-empty { color: #888; font-size: 0.8rem; padding: 0.6rem; text-align: center; }
        .audio-global-sticky__btn {
            width: 2.2rem; height: 2.2rem; border: 1px solid rgba(255,255,255,0.18); border-radius: 999px;
            background: rgba(255,255,255,0.08); color: #fff; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
        }
        .audio-global-sticky__btn:hover { background: rgba(24, 135, 129, 0.22); border-color: rgba(24, 135, 129, 0.6); }
        .audio-global-sticky__link { color: inherit; text-decoration: none; }
        .audio-global-sticky__meta { order: 1; }
        .audio-global-sticky__middle { order: 2; }
        .audio-global-sticky__controls { order: 3; }
        .audio-global-sticky__title,
        .audio-global-sticky__sub { text-align: right; }

        @media (max-width: 900px) {
            .audio-global-sticky {
                display: none;
                grid-template-columns: auto 1fr auto;
                align-items: center;
                row-gap: 0.35rem;
            }
            .audio-global-sticky.is-visible { display: grid; }
            .audio-global-sticky__meta { grid-column: 1 / 3; }
            .audio-global-sticky__controls { grid-column: 3 / 4; justify-self: end; }
            .audio-global-sticky__middle { grid-column: 1 / 4; max-width: none; min-width: 0; }
            .audio-global-sticky__volume { width: 72px; }
        }
    </style>
    <style id="audio-platform-theme">
        html[data-theme="dark"] {
            color-scheme: dark;
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --bg-primary: #111827;
            --bg-secondary: #0f172a;
            --bg-tertiary: #1e293b;
            --border-color: rgba(255, 255, 255, 0.12);
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.45);
            --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 12px 24px rgba(0, 0, 0, 0.45);
        }
        html[data-theme="dark"] body.audio-platform-page {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }
        html[data-theme="dark"] .audio-home-hero {
            background: linear-gradient(165deg, rgba(24, 135, 129, 0.38) 0%, rgba(15, 23, 42, 0.98) 50%, var(--bg-secondary) 100%);
            border-bottom-color: var(--border-color);
        }
        html[data-theme="dark"] .audio-home-chip {
            background: var(--bg-tertiary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        html[data-theme="dark"] .audio-home-chip:hover {
            background: rgba(24, 135, 129, 0.22);
            border-color: var(--primary-color, #188781);
            color: #e0f2f1;
        }
        html[data-theme="dark"] .audio-home-row .video-card:hover {
            background: rgba(255, 255, 255, 0.06) !important;
        }
        html[data-theme="dark"] .audio-home-section--grid .video-card {
            background: var(--bg-primary) !important;
            border-color: var(--border-color);
        }
        html[data-theme="dark"] .audio-home-section--grid .video-card:hover {
            background: var(--bg-tertiary) !important;
        }
        html[data-theme="dark"] .modal-content {
            background: var(--bg-primary);
            color: var(--text-primary);
            border-color: var(--border-color);
        }
        html[data-theme="dark"] .modal-header,
        html[data-theme="dark"] .modal-footer {
            border-color: var(--border-color);
        }
        html[data-theme="dark"] .btn-close {
            filter: invert(1);
        }
        .audio-theme-toggle {
            flex-shrink: 0;
            width: 42px;
            height: 42px;
            padding: 0;
            border-radius: 999px;
            border: 1px solid var(--border-color);
            background: var(--bg-secondary);
            color: var(--text-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s, color 0.2s;
        }
        .audio-theme-toggle:hover {
            background: var(--bg-tertiary);
            border-color: var(--primary-color, #188781);
            color: var(--primary-color, #188781);
        }
        .audio-theme-toggle i { font-size: 1.15rem; line-height: 1; }
    </style>
    @stack('styles')
</head>
<body class="audio-platform-page {{ request()->routeIs('shorts') ? 'shorts-page' : '' }}">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="navbar-inner">
            <div class="navbar-left">
                <button class="navbar-menu-btn" id="navbarMenuBtn" onclick="toggleSidebar()" title="إظهار/إخفاء القائمة">
                    <i class="bi bi-list"></i>
                </button>
                <a class="navbar-brand" href="{{ route('audio.home') }}" title="المنصة الصوتية">
                    <img src="{{ asset('images/logo.png') }}" alt="المناجاة" class="navbar-logo">
                </a>
                <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary ms-2 d-none d-md-inline-flex align-items-center gap-1" title="المحتوى المرئي">
                    <i class="bi bi-film"></i><span>المحتوى المرئي</span>
                </a>
            </div>
            <div class="navbar-search-wrap">
                <form class="navbar-search-form" id="navbarSearchForm" action="{{ route('audio.home') }}" method="get" role="search">
                    <input type="text" class="navbar-search-input" id="navbarSearchInput" name="search" value="{{ request('search') }}"
                        placeholder="بحث في العناوين والشيوخ والوصف..."
                        data-placeholder-lg="بحث في العناوين والشيوخ والوصف..."
                        data-placeholder-md="بحث في العناوين والشيوخ..."
                        data-placeholder-sm="بحث..."
                        autocomplete="off" aria-label="بحث">
                    <button type="submit" class="navbar-search-btn" title="بحث">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
                <div class="navbar-search-dropdown" id="navbarSearchDropdown" role="listbox" aria-hidden="true"></div>
            </div>
            <button type="button" class="audio-theme-toggle ms-2" id="audioThemeToggle" title="الوضع الليلي" aria-label="تبديل الوضع الليلي / النهاري" aria-pressed="false">
                <i class="bi bi-moon-stars" data-theme-icon-when="light" aria-hidden="true"></i>
                <i class="bi bi-brightness-high d-none" data-theme-icon-when="dark" aria-hidden="true"></i>
            </button>
            <div class="navbar-right collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    @auth
                        <li class="nav-item">
                            <span class="nav-link user-name-display">{{ Auth::user()->name }}</span>
                        </li>
                    @else
                        <li class="nav-item">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#authModal" data-bs-mode="login">
                                تسجيل الدخول
                            </button>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <div id="audioPageShell">
        @if(session('success'))
            <div class="container-fluid px-4 mt-3">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="container-fluid px-4 mt-3">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="mb-0 font-monospace small" style="white-space: pre-wrap; max-height: 70vh; overflow-y: auto;">{!! nl2br(e(session('error'))) !!}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="container-fluid px-4 mt-3">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif

        @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <h5>المنصة الصوتية — المناجاة</h5>
            <p>محاضرات ومحتوى صوتي من موقع المناجاة. <a href="{{ route('home') }}">الانتقال إلى المحتوى المرئي</a></p>
            <p class="footer-legal mt-2 mb-0">
                <a href="{{ route('legal.privacy') }}">سياسة الخصوصية</a>
                <span class="footer-legal-sep">·</span>
                <a href="{{ route('legal.terms') }}">شروط الخدمة</a>
            </p>
        </div>
    </footer>

    <!-- Auth Modal -->
    <div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content auth-modal-content">
                <div class="modal-header auth-modal-header">
                    <div class="auth-modal-header-content">
                        <img src="{{ asset('images/logo_min.png') }}" alt="المناجاة" class="auth-modal-logo">
                        <h5 class="modal-title" id="authModalLabel">تسجيل الدخول</h5>
                    </div>
                    <button type="button" class="auth-modal-close" data-bs-dismiss="modal" aria-label="إغلاق">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body auth-modal-body">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs auth-nav-tabs mb-4" id="authTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab" data-title="تسجيل الدخول">
                                تسجيل الدخول
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab" data-title="إنشاء حساب">
                                إنشاء حساب
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="authTabContent">
                        <!-- Login Form -->
                        <div class="tab-pane fade show active" id="login" role="tabpanel">
                            <form id="loginForm">
                                @csrf
                                <div class="mb-3">
                                    <label for="loginEmail" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="loginEmail" name="email" required autocomplete="email">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="loginPassword" class="form-label">كلمة المرور</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" class="form-control" id="loginPassword" name="password" required autocomplete="current-password">
                                        <button type="button" class="password-toggle-btn" onclick="togglePassword('loginPassword', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div id="loginError" class="alert alert-danger d-none" role="alert"></div>
                                <button type="submit" class="btn btn-primary w-100 mb-3">تسجيل الدخول</button>
                                
                                <!-- Divider -->
                                <div class="divider-with-text my-3">
                                    <span>أو</span>
                                </div>
                                
                                <!-- Google Login Button -->
                                <a href="{{ route('google.redirect') }}" class="btn btn-google w-100">
                                    <svg width="18" height="18" viewBox="0 0 18 18">
                                        <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/>
                                        <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.96-2.184l-2.908-2.258c-.806.54-1.837.86-3.052.86-2.347 0-4.33-1.585-5.04-3.715H.957v2.332C2.438 15.983 5.482 18 9 18z"/>
                                        <path fill="#FBBC05" d="M3.96 10.703c-.18-.54-.282-1.117-.282-1.703s.102-1.163.282-1.703V4.965H.957C.348 6.175 0 7.55 0 9s.348 2.825.957 4.035l3.003-2.332z"/>
                                        <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.965L3.96 7.297C4.67 5.167 6.653 3.58 9 3.58z"/>
                                    </svg>
                                    تسجيل الدخول باستخدام Google
                                </a>
                            </form>
                        </div>

                        <!-- Register Form -->
                        <div class="tab-pane fade" id="register" role="tabpanel">
                            <form id="registerForm">
                                @csrf
                                <div class="mb-3">
                                    <label for="registerName" class="form-label">الاسم</label>
                                    <input type="text" class="form-control" id="registerName" name="name" required autocomplete="name">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="registerEmail" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="registerEmail" name="email" required autocomplete="email">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="registerPassword" class="form-label">كلمة المرور</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" class="form-control" id="registerPassword" name="password" required autocomplete="new-password">
                                        <button type="button" class="password-toggle-btn" onclick="togglePassword('registerPassword', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div id="registerError" class="alert alert-danger d-none" role="alert"></div>
                                <button type="submit" class="btn btn-primary w-100 mb-3">إنشاء حساب</button>
                                
                                <!-- Divider -->
                                <div class="divider-with-text my-3">
                                    <span>أو</span>
                                </div>
                                
                                <!-- Google Register Button -->
                                <a href="{{ route('google.redirect') }}" class="btn btn-google w-100">
                                    <svg width="18" height="18" viewBox="0 0 18 18">
                                        <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/>
                                        <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.96-2.184l-2.908-2.258c-.806.54-1.837.86-3.052.86-2.347 0-4.33-1.585-5.04-3.715H.957v2.332C2.438 15.983 5.482 18 9 18z"/>
                                        <path fill="#FBBC05" d="M3.96 10.703c-.18-.54-.282-1.117-.282-1.703s.102-1.163.282-1.703V4.965H.957C.348 6.175 0 7.55 0 9s.348 2.825.957 4.035l3.003-2.332z"/>
                                        <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.965L3.96 7.297C4.67 5.167 6.653 3.58 9 3.58z"/>
                                    </svg>
                                    التسجيل باستخدام Google
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="audio-global-sticky" id="audioGlobalStickyPlayer" aria-live="polite">
        <div class="audio-global-sticky__meta">
            <button type="button" class="audio-global-sticky__btn audio-global-sticky__btn--text audio-global-sticky__btn--text-side" id="audioGlobalStickyTextBtn" aria-label="المحتوى النصي">
                <i class="bi bi-text-paragraph"></i>
            </button>
            <a class="audio-global-sticky__meta-link audio-global-sticky__link" id="audioGlobalStickyLink" href="#">
                <img src="{{ asset('images/logo_min.png') }}" alt="" class="audio-global-sticky__thumb" id="audioGlobalStickyThumb" width="46" height="46">
                <div class="audio-global-sticky__text">
                    <div class="audio-global-sticky__title" id="audioGlobalStickyTitle">جاري التشغيل</div>
                    <div class="audio-global-sticky__sub" id="audioGlobalStickySpeaker">المنصة الصوتية</div>
                </div>
            </a>
        </div>
        <div class="audio-global-sticky__controls">
            <input type="range" id="audioGlobalStickyVolume" class="audio-global-sticky__volume" min="0" max="1" step="0.01" value="1" aria-label="مستوى الصوت">
            <div class="audio-global-sticky__queue-wrap">
                <button type="button" class="audio-global-sticky__btn" id="audioGlobalStickyQueueBtn" aria-label="قائمة التالي">
                    <i class="bi bi-music-note-list"></i>
                </button>
                <div class="audio-global-sticky__queue-panel" id="audioGlobalStickyQueuePanel"></div>
            </div>
            <button type="button" class="audio-global-sticky__btn" id="audioGlobalStickyCloseBtn" aria-label="إيقاف وإخفاء">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="audio-global-sticky__middle">
            <div class="audio-global-sticky__transport">
                <button type="button" class="audio-global-sticky__btn" id="audioGlobalStickyNextBtn" aria-label="التالي">
                    <i class="bi bi-skip-forward-fill"></i>
                </button>
                <button type="button" class="audio-global-sticky__btn audio-global-sticky__btn--play" id="audioGlobalStickyPlayBtn" aria-label="تشغيل أو إيقاف">
                    <i class="bi bi-play-fill" id="audioGlobalStickyPlayIcon"></i>
                </button>
            </div>
            <div class="audio-global-sticky__timeline">
                <span id="audioGlobalStickyCur" class="audio-global-sticky__time">0:00</span>
                <input type="range" id="audioGlobalStickySeek" class="audio-global-sticky__seek" min="0" max="1000" step="1" value="0" aria-label="موضع التشغيل">
                <span id="audioGlobalStickyDur" class="audio-global-sticky__time">—:—</span>
            </div>
        </div>
        <div class="audio-global-sticky__text-panel" id="audioGlobalStickyTextPanel">
            <h4 class="audio-global-sticky__text-title">المحتوى النصي المرتبط</h4>
            <div class="audio-global-sticky__text-body" id="audioGlobalStickyTextBody">لا يوجد محتوى نصي لهذا الدعاء.</div>
        </div>
        <audio id="audioGlobalStickyElement" class="visually-hidden" preload="metadata" playsinline></audio>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function () {
        var STORAGE_KEY = 'almonajah_theme';
        var LEGACY_KEYS = ['site_theme', 'almonajahAudioTheme'];
        var root = document.documentElement;
        function persistTheme(theme) {
            try {
                localStorage.setItem(STORAGE_KEY, theme);
                LEGACY_KEYS.forEach(function (key) { localStorage.setItem(key, theme); });
            } catch (e) {}
        }
        function syncToggleUI() {
            var isDark = root.getAttribute('data-theme') === 'dark';
            var btn = document.getElementById('audioThemeToggle');
            if (!btn) return;
            btn.querySelectorAll('[data-theme-icon-when]').forEach(function (icon) {
                var when = icon.getAttribute('data-theme-icon-when');
                var show = (when === 'dark' && isDark) || (when === 'light' && !isDark);
                icon.classList.toggle('d-none', !show);
            });
            btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            btn.title = isDark ? 'الوضع النهاري' : 'الوضع الليلي';
            btn.setAttribute('aria-label', isDark ? 'التبديل إلى الوضع النهاري' : 'التبديل إلى الوضع الليلي');
        }
        function setTheme(mode) {
            var dark = mode === 'dark';
            root.setAttribute('data-theme', dark ? 'dark' : 'light');
            root.classList.toggle('dark-mode', dark);
            persistTheme(dark ? 'dark' : 'light');
            syncToggleUI();
        }
        function toggleTheme() {
            setTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        }
        function initAudioThemeToggle() {
            var stored = null;
            try {
                stored = localStorage.getItem(STORAGE_KEY);
                if (stored !== 'dark' && stored !== 'light') {
                    LEGACY_KEYS.some(function (key) {
                        var legacy = localStorage.getItem(key);
                        if (legacy === 'dark' || legacy === 'light') {
                            stored = legacy;
                            return true;
                        }
                        return false;
                    });
                }
            } catch (e) {}
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            var initialTheme = (stored === 'dark' || stored === 'light') ? stored : (prefersDark ? 'dark' : 'light');
            root.setAttribute('data-theme', initialTheme);
            root.classList.toggle('dark-mode', initialTheme === 'dark');
            persistTheme(initialTheme);
            syncToggleUI();
            var btn = document.getElementById('audioThemeToggle');
            if (btn) btn.addEventListener('click', toggleTheme);
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAudioThemeToggle);
        } else {
            initAudioThemeToggle();
        }
    })();
    </script>
    <div id="audioDynamicStylesTemplate" style="display:none;">
        @stack('styles')
    </div>
    <div id="audioDynamicScriptsTemplate" style="display:none;">
        @stack('scripts')
    </div>
    
    <script>
        // إزالة ?nocache= من الرابط بعد تسجيل الدخول (بدون إعادة تحميل)
        (function() {
            var q = location.search;
            if (q && /^\?nocache=\d+$/.test(q)) {
                history.replaceState(null, '', location.pathname + location.hash || '');
            }
        })();

        // Handle modal mode switching
        const authModal = document.getElementById('authModal');
        const authModalLabel = document.getElementById('authModalLabel');
        
        function updateModalTitle(title) {
            if (authModalLabel) {
                authModalLabel.textContent = title;
            }
        }
        
        if (authModal) {
            authModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const mode = button ? button.getAttribute('data-bs-mode') : 'login';
                
                // Reset forms
                const loginForm = document.getElementById('loginForm');
                const registerForm = document.getElementById('registerForm');
                if (loginForm) loginForm.reset();
                if (registerForm) registerForm.reset();
                
                // Clear errors inside the modal only (لا نلمس رسائل الخطأ خارج المودال مثل session('error'))
                const modalBody = authModal.querySelector('.modal-body');
                if (modalBody) {
                    modalBody.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                    modalBody.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
                    modalBody.querySelectorAll('.alert-danger').forEach(el => el.classList.add('d-none'));
                }
                
                if (mode === 'register') {
                    const registerTab = document.getElementById('register-tab');
                    if (registerTab) {
                        const bsTab = new bootstrap.Tab(registerTab);
                        bsTab.show();
                        updateModalTitle('إنشاء حساب');
                    }
                } else {
                    const loginTab = document.getElementById('login-tab');
                    if (loginTab) {
                        const bsTab = new bootstrap.Tab(loginTab);
                        bsTab.show();
                        updateModalTitle('تسجيل الدخول');
                    }
                }
            });
            
            // Update title when switching tabs
            const loginTab = document.getElementById('login-tab');
            const registerTab = document.getElementById('register-tab');
            
            if (loginTab) {
                loginTab.addEventListener('shown.bs.tab', function () {
                    updateModalTitle(this.getAttribute('data-title') || 'تسجيل الدخول');
                });
            }
            
            if (registerTab) {
                registerTab.addEventListener('shown.bs.tab', function () {
                    updateModalTitle(this.getAttribute('data-title') || 'إنشاء حساب');
                });
            }
        }

        // Login Form Handler
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const loginError = document.getElementById('loginError');
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                
                // Clear previous errors
                loginError.classList.add('d-none');
                loginError.textContent = '';
                this.querySelectorAll('.is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                    el.nextElementSibling.textContent = '';
                });
                
                submitBtn.disabled = true;
                submitBtn.textContent = 'جاري تسجيل الدخول...';
                
                try {
                    const loginUrl = '{{ route("login") }}';
                    const loginUrlRelative = loginUrl.replace(/^https?:\/\/[^\/]+/, '');
                    
                    const response = await fetch(loginUrlRelative, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            email: formData.get('email'),
                            password: formData.get('password')
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.location.href = data.redirect || '{{ route("home") }}';
                    } else {
                        if (data.errors) {
                            Object.keys(data.errors).forEach(field => {
                                const input = document.getElementById('login' + field.charAt(0).toUpperCase() + field.slice(1));
                                if (input) {
                                    input.classList.add('is-invalid');
                                    const feedback = input.nextElementSibling;
                                    if (feedback) {
                                        feedback.textContent = data.errors[field][0];
                                    }
                                }
                            });
                        }
                        loginError.textContent = data.message || 'حدث خطأ أثناء تسجيل الدخول';
                        loginError.classList.remove('d-none');
                    }
                } catch (error) {
                    loginError.textContent = 'حدث خطأ أثناء الاتصال بالخادم';
                    loginError.classList.remove('d-none');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            });
        }

        // Register Form Handler
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const registerError = document.getElementById('registerError');
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                
                // Clear previous errors
                registerError.classList.add('d-none');
                registerError.textContent = '';
                this.querySelectorAll('.is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                    const feedback = el.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.textContent = '';
                    }
                });
                
                submitBtn.disabled = true;
                submitBtn.textContent = 'جاري إنشاء الحساب...';
                
                try {
                    const registerUrl = '{{ route("register") }}';
                    const registerUrlRelative = registerUrl.replace(/^https?:\/\/[^\/]+/, '');
                    
                    const response = await fetch(registerUrlRelative, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            name: formData.get('name'),
                            email: formData.get('email'),
                            password: formData.get('password'),
                            password_confirmation: formData.get('password')
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.location.href = data.redirect || '{{ route("home") }}';
                    } else {
                        if (data.errors) {
                            Object.keys(data.errors).forEach(field => {
                                const fieldName = field.replace('_', '');
                                const input = document.getElementById('register' + fieldName.charAt(0).toUpperCase() + fieldName.slice(1));
                                if (input) {
                                    input.classList.add('is-invalid');
                                    const feedback = input.nextElementSibling;
                                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                                        feedback.textContent = data.errors[field][0];
                                    }
                                }
                            });
                        }
                        registerError.textContent = data.message || 'حدث خطأ أثناء إنشاء الحساب';
                        registerError.classList.remove('d-none');
                    }
                } catch (error) {
                    registerError.textContent = 'حدث خطأ أثناء الاتصال بالخادم';
                    registerError.classList.remove('d-none');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            });
        }

        // Navbar search autocomplete (تشغيل بعد اكتمال DOM ليعمل في كل الصفحات بما فيها صفحة الفيديو)
        function initNavbarSearchAutocomplete() {
            var searchInput = document.getElementById('navbarSearchInput');
            var searchForm = document.getElementById('navbarSearchForm');
            var dropdown = document.getElementById('navbarSearchDropdown');
            if (!searchInput || !searchForm || !dropdown) return;

            function syncSearchPlaceholder() {
                var lg = searchInput.getAttribute('data-placeholder-lg') || searchInput.placeholder;
                var md = searchInput.getAttribute('data-placeholder-md') || lg;
                var sm = searchInput.getAttribute('data-placeholder-sm') || 'بحث...';
                var w = window.innerWidth;
                searchInput.placeholder = w < 480 ? sm : (w < 768 ? md : lg);
            }
            syncSearchPlaceholder();
            window.addEventListener('resize', syncSearchPlaceholder);

            var suggestionsUrl = '{{ route("search.suggestions") }}';
            var homeUrl = '{{ route("audio.home") }}';
            var storageBase = '{{ url("storage") }}';
            var defaultThumb = '{{ asset("images/logo_min.png") }}';
            var debounceTimer = null;
            var debounceMs = 300;

            function closeDropdown() {
                if (dropdown) {
                    dropdown.classList.remove('is-open');
                    dropdown.setAttribute('aria-hidden', 'true');
                }
            }

            function renderResults(results, query) {
                if (!dropdown) return;
                dropdown.innerHTML = '';
                if (!results || results.length === 0) {
                    dropdown.innerHTML = '<div class="navbar-search-dropdown-empty">لا توجد نتائج</div>';
                } else {
                    results.forEach(function(r) {
                        var thumb = (r.thumbnail_path ? storageBase + '/' + r.thumbnail_path : defaultThumb);
                        var a = document.createElement('a');
                        a.href = r.url;
                        a.className = 'navbar-search-dropdown-item';
                        a.setAttribute('role', 'option');
                        a.innerHTML = '<img src="' + thumb + '" alt="" onerror="this.src=\'' + defaultThumb + '\'">' +
                            '<div class="navbar-search-dropdown-item-content">' +
                            '<div class="navbar-search-dropdown-item-title">' + (r.title || '').replace(/</g, '&lt;') + '</div>' +
                            (r.speaker_name ? '<div class="navbar-search-dropdown-item-meta">' + (r.speaker_name || '').replace(/</g, '&lt;') + '</div>' : '') +
                            '</div>';
                        a.addEventListener('click', function(e) {
                            e.preventDefault();
                            window.location.href = r.url;
                        });
                        dropdown.appendChild(a);
                    });
                    var allLink = document.createElement('a');
                    allLink.href = homeUrl + (query ? '?search=' + encodeURIComponent(query) : '');
                    allLink.className = 'navbar-search-dropdown-all';
                    allLink.textContent = 'عرض كل النتائج';
                    dropdown.appendChild(allLink);
                }
                dropdown.classList.add('is-open');
                dropdown.setAttribute('aria-hidden', 'false');
            }

            function fetchSuggestions(query) {
                if (!query || query.length < 1) {
                    closeDropdown();
                    return;
                }
                fetch(suggestionsUrl + '?type=audio&q=' + encodeURIComponent(query), { headers: { 'Accept': 'application/json' } })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        renderResults(data.results || [], query);
                    })
                    .catch(function() {
                        if (dropdown) {
                            dropdown.innerHTML = '<div class="navbar-search-dropdown-empty">حدث خطأ</div>';
                            dropdown.classList.add('is-open');
                        }
                    });
            }

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    var q = (searchInput.value || '').trim();
                    clearTimeout(debounceTimer);
                    if (!q) {
                        closeDropdown();
                        return;
                    }
                    debounceTimer = setTimeout(function() {
                        fetchSuggestions(q);
                    }, debounceMs);
                });
                searchInput.addEventListener('focus', function() {
                    var q = (searchInput.value || '').trim();
                    if (q && dropdown && dropdown.classList.contains('is-open')) return;
                    if (q) fetchSuggestions(q);
                });
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') closeDropdown();
                });
            }

            document.addEventListener('click', function(e) {
                if (!searchForm.contains(e.target) && !dropdown.contains(e.target)) {
                    closeDropdown();
                }
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initNavbarSearchAutocomplete);
        } else {
            initNavbarSearchAutocomplete();
        }

        // Sidebar Toggle Function (Global)
        // Default to open on desktop, closed on mobile
        let sidebarOpen = localStorage.getItem('sidebarOpen');
        if (sidebarOpen === null) {
            sidebarOpen = window.innerWidth > 1024;
        } else {
            sidebarOpen = sidebarOpen === 'true';
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebarMenu');
            const navbarBtn = document.getElementById('navbarMenuBtn');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (!sidebar) return; // Sidebar only exists on home page
            
            sidebarOpen = !sidebarOpen;
            localStorage.setItem('sidebarOpen', sidebarOpen);
            
            if (sidebarOpen) {
                sidebar.classList.remove('collapsed');
                if (navbarBtn) navbarBtn.classList.add('active');
                if (overlay && window.innerWidth <= 1024) {
                    overlay.classList.add('active');
                }
            } else {
                sidebar.classList.add('collapsed');
                if (navbarBtn) navbarBtn.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
            }
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebarMenu');
            const navbarBtn = document.getElementById('navbarMenuBtn');
            const overlay = document.getElementById('sidebarOverlay');
            if (!sidebar) return;
            sidebarOpen = false;
            localStorage.setItem('sidebarOpen', 'false');
            sidebar.classList.add('collapsed');
            if (navbarBtn) navbarBtn.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
        }

        // Initialize sidebar state (only on pages with sidebar)
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebarMenu');
            if (!sidebar) return; // Sidebar only exists on home page
            
            const navbarBtn = document.getElementById('navbarMenuBtn');
            const mainContent = document.querySelector('.main-content-wrapper');
            let overlay = document.getElementById('sidebarOverlay');
            
            // Create overlay if it doesn't exist
            if (!overlay) {
                const newOverlay = document.createElement('div');
                newOverlay.id = 'sidebarOverlay';
                newOverlay.className = 'sidebar-overlay';
                newOverlay.onclick = toggleSidebar;
                document.body.appendChild(newOverlay);
                overlay = newOverlay;
            }
            
            // On mobile, start with sidebar closed if not set
            if (window.innerWidth <= 1024 && localStorage.getItem('sidebarOpen') === null) {
                sidebarOpen = false;
                localStorage.setItem('sidebarOpen', 'false');
            }
            
            // Set initial state
            if (sidebarOpen) {
                sidebar.classList.remove('collapsed');
                if (navbarBtn) navbarBtn.classList.add('active');
                if (overlay && window.innerWidth <= 1024) {
                    overlay.classList.add('active');
                }
            } else {
                sidebar.classList.add('collapsed');
                if (navbarBtn) navbarBtn.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
            }
            
            // على الموبايل: عند الضغط على أي رابط في القائمة ننتقل للصفحة ونغلق القائمة
            var sidebarLinks = sidebar.querySelectorAll('a.sidebar-item[href]');
            sidebarLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 1024) {
                        closeSidebar();
                    }
                });
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                const sidebar = document.getElementById('sidebarMenu');
                if (!sidebar) return;
                
                const overlay = document.getElementById('sidebarOverlay');
                
                if (window.innerWidth <= 1024) {
                    if (sidebarOpen && overlay) {
                        overlay.classList.add('active');
                    } else if (overlay) {
                        overlay.classList.remove('active');
                    }
                } else {
                    if (overlay) overlay.classList.remove('active');
                }
            });
        });

        // Toggle Password Visibility
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        // Prefetch on hover for faster navigation (video cards, sidebar, search results)
        (function() {
            var prefetched = {};
            document.addEventListener('mouseover', function(e) {
                var a = e.target.closest ? e.target.closest('a') : null;
                if (!a || !a.href) return;
                var href = (a.getAttribute('href') || '').trim();
                if (!href || href === '#' || href.indexOf('javascript:') === 0) return;
                var full = a.href;
                if (new URL(full).origin !== window.location.origin) return;
                if (prefetched[full]) return;
                var link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = full;
                link.as = 'document';
                document.head.appendChild(link);
                prefetched[full] = true;
            }, { passive: true, capture: true });
        })();

        // Global sticky audio player across whole site
        (function() {
            var PLAYER_STATE_KEY = 'almonajah_audio_player_state';
            var CUSTOM_PLAYLIST_KEY = 'almonajah_custom_playlist';
            var sticky = document.getElementById('audioGlobalStickyPlayer');
            var audio = document.getElementById('audioGlobalStickyElement');
            var titleEl = document.getElementById('audioGlobalStickyTitle');
            var speakerEl = document.getElementById('audioGlobalStickySpeaker');
            var thumbEl = document.getElementById('audioGlobalStickyThumb');
            var playBtn = document.getElementById('audioGlobalStickyPlayBtn');
            var playIcon = document.getElementById('audioGlobalStickyPlayIcon');
            var closeBtn = document.getElementById('audioGlobalStickyCloseBtn');
            var linkEl = document.getElementById('audioGlobalStickyLink');
            var seekEl = document.getElementById('audioGlobalStickySeek');
            var curEl = document.getElementById('audioGlobalStickyCur');
            var durEl = document.getElementById('audioGlobalStickyDur');
            var volumeEl = document.getElementById('audioGlobalStickyVolume');
            var nextBtn = document.getElementById('audioGlobalStickyNextBtn');
            var queueBtn = document.getElementById('audioGlobalStickyQueueBtn');
            var queuePanel = document.getElementById('audioGlobalStickyQueuePanel');
            var textBtn = document.getElementById('audioGlobalStickyTextBtn');
            var textPanel = document.getElementById('audioGlobalStickyTextPanel');
            var textBody = document.getElementById('audioGlobalStickyTextBody');
            var activeTextSegmentIndex = -1;
            if (!sticky || !audio) return;
            function esc(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

            function fmt(s) {
                if (!isFinite(s) || s < 0) return '0:00';
                s = Math.floor(s);
                var m = Math.floor(s / 60);
                var sec = s % 60;
                return m + ':' + (sec < 10 ? '0' : '') + sec;
            }

            function setVisible(v) {
                sticky.classList.toggle('is-visible', !!v);
                document.body.style.paddingBottom = v ? '82px' : '';
            }

            function setPlayingUI(playing) {
                if (!playIcon) return;
                playIcon.className = playing ? 'bi bi-pause-fill' : 'bi bi-play-fill';
            }

            function getSeekDuration() {
                if (isFinite(audio.duration) && audio.duration > 0) return audio.duration;
                if (audio.seekable && audio.seekable.length > 0) {
                    var last = audio.seekable.end(audio.seekable.length - 1);
                    if (isFinite(last) && last > 0) return last;
                }
                var st = loadState() || {};
                if (isFinite(st.duration) && st.duration > 0) return st.duration;
                return 0;
            }

            function renderTimeline() {
                var d = getSeekDuration();
                var c = audio.currentTime || 0;
                if (curEl) curEl.textContent = fmt(c);
                if (durEl) durEl.textContent = isFinite(d) && d > 0 ? fmt(d) : '—:—';
                if (seekEl && isFinite(d) && d > 0) {
                    seekEl.value = String(Math.min(1000, Math.max(0, (c / d) * 1000)));
                } else if (seekEl) {
                    seekEl.value = '0';
                }
            }

            function saveState(extra) {
                try {
                    var prev = {};
                    try { prev = JSON.parse(localStorage.getItem(PLAYER_STATE_KEY) || '{}'); } catch (_) {}
                    var payload = Object.assign({}, prev, {
                        currentTime: audio.currentTime || 0,
                        duration: isFinite(audio.duration) ? audio.duration : null,
                        paused: audio.paused,
                        volume: audio.volume,
                        updatedAt: Date.now()
                    }, extra || {});
                    localStorage.setItem(PLAYER_STATE_KEY, JSON.stringify(payload));
                } catch (_) {}
            }
            function isSameTrack(a, b) {
                if (!a || !b) return false;
                if (a.id != null && b.id != null && String(a.id) !== '' && String(b.id) !== '') {
                    return String(a.id) === String(b.id);
                }
                return !!(a.src && b.src && String(a.src) === String(b.src));
            }
            function loadCustomPlaylist() {
                try {
                    var raw = localStorage.getItem(CUSTOM_PLAYLIST_KEY);
                    var list = raw ? JSON.parse(raw) : [];
                    return Array.isArray(list) ? list : [];
                } catch (_) { return []; }
            }
            function normalizeQueueItems() {
                return loadCustomPlaylist().filter(function(it) { return it && it.src; });
            }
            function playNextFromQueue() {
                var st = loadState() || {};
                var q = Array.isArray(st.queue) ? st.queue.filter(function(it) { return it && it.src; }) : [];
                if (!q.length) q = normalizeQueueItems();
                if (!q.length) return false;
                var next = q.shift();
                while (next && !next.src && q.length) next = q.shift();
                if (!next || !next.src) return false;
                window.AlmonajahAudioGlobal.setTrack({
                    id: next.id || null,
                    src: next.src,
                    title: next.title || 'محتوى صوتي',
                    speaker: next.speaker || 'المنصة الصوتية',
                    poster: next.poster || '',
                    pageUrl: next.pageUrl || window.location.href,
                    queue: q,
                    nextUrl: q.length && q[0].pageUrl ? q[0].pageUrl : ''
                }, { autoplay: true, toggle: false });
                return true;
            }
            function renderQueuePanel() {
                if (!queuePanel) return;
                var q = normalizeQueueItems();
                if (!q.length) {
                    queuePanel.innerHTML = '<div class="audio-global-sticky__queue-empty">لا يوجد عناصر في قائمة التالي</div>';
                    return;
                }
                var rows = q.map(function(item, idx) {
                    var title = (item.title || 'محتوى صوتي').replace(/</g, '&lt;');
                    var sub = (item.speaker || 'المنصة الصوتية').replace(/</g, '&lt;');
                    var dur = (item.duration || '').replace(/</g, '&lt;');
                    var poster = (item.poster || '').replace(/"/g, '&quot;');
                    var href = (item.pageUrl || '').replace(/"/g, '&quot;');
                    var src = (item.src || '').replace(/"/g, '&quot;');
                    var trackId = (item.id || '').toString().replace(/"/g, '&quot;');
                    var upnext = idx === 0 ? ' is-upnext' : '';
                    var badge = idx === 0 ? '<span class="audio-global-sticky__queue-badge">التالي</span>' : '';
                    var metaLine = dur
                        ? '<div class="audio-global-sticky__queue-sub">' + sub + ' · ' + dur + '</div>'
                        : '<div class="audio-global-sticky__queue-sub">' + sub + '</div>';
                    return '<button type="button" class="audio-global-sticky__queue-item' + upnext + '" data-idx="' + idx + '" data-href="' + href + '" data-src="' + src + '" data-id="' + trackId + '" data-title="' + title.replace(/"/g, '&quot;') + '" data-speaker="' + sub.replace(/"/g, '&quot;') + '" data-poster="' + poster + '">' +
                        '<img src="' + poster + '" alt="">' +
                        '<span><div class="audio-global-sticky__queue-title">' + title + '</div>' + metaLine + '</span>' +
                        badge +
                    '</button>';
                }).join('');
                queuePanel.innerHTML = '<div class="audio-global-sticky__queue-header"><span class="audio-global-sticky__queue-title-main">قائمة التشغيل التالية</span><span class="audio-global-sticky__queue-count">' + q.length + ' عناصر</span></div>' + rows;
            }
            function normalizeTimedSegments(state) {
                var raw = (state && Array.isArray(state.timedSegments)) ? state.timedSegments : [];
                return raw.filter(function(seg) {
                    if (!seg) return false;
                    var start = Number(seg.start);
                    var end = Number(seg.end);
                    var text = String(seg.text || '').trim();
                    return Number.isFinite(start) && Number.isFinite(end) && end > start && text !== '';
                }).map(function(seg) {
                    return { start: Number(seg.start), end: Number(seg.end), text: String(seg.text || '').trim() };
                });
            }

            function renderTextPanel() {
                if (!textBody) return;
                var st = loadState() || {};
                var text = (st.textContent || '').trim();
                var timedSegments = normalizeTimedSegments(st);
                activeTextSegmentIndex = -1;

                if (timedSegments.length) {
                    textBody.innerHTML = timedSegments.map(function(seg, idx) {
                        return '<span class="audio-global-sticky__text-segment" data-seg-idx="' + idx + '" data-start="' + seg.start + '" data-end="' + seg.end + '">' + esc(seg.text) + '</span>';
                    }).join('');
                    syncTextPanelWithTime(audio.currentTime || 0);
                    return;
                }

                textBody.innerHTML = text ? esc(text).replace(/\n/g, '<br>') : 'لا يوجد محتوى نصي لهذا الدعاء.';
            }

            function syncTextPanelWithTime(currentTime) {
                if (!textBody || !textPanel || !textPanel.classList.contains('is-open')) return;
                var segments = textBody.querySelectorAll('.audio-global-sticky__text-segment');
                if (!segments.length) return;

                var nextIndex = -1;
                for (var i = 0; i < segments.length; i++) {
                    var segStart = parseFloat(segments[i].getAttribute('data-start') || '0');
                    var segEnd = parseFloat(segments[i].getAttribute('data-end') || '0');
                    if (currentTime >= segStart && currentTime <= segEnd) {
                        nextIndex = i;
                        break;
                    }
                }

                if (nextIndex === activeTextSegmentIndex) return;
                if (activeTextSegmentIndex >= 0 && segments[activeTextSegmentIndex]) {
                    segments[activeTextSegmentIndex].classList.remove('is-active');
                }
                activeTextSegmentIndex = nextIndex;
                if (nextIndex >= 0 && segments[nextIndex]) {
                    segments[nextIndex].classList.add('is-active');
                    segments[nextIndex].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            }

            function loadState() {
                try {
                    var raw = localStorage.getItem(PLAYER_STATE_KEY);
                    if (!raw) return null;
                    return JSON.parse(raw);
                } catch (_) { return null; }
            }

            function applyStateAndPlay(state) {
                if (!state || !state.src) return;
                audio.src = state.src;
                audio.load();
                if (titleEl) titleEl.textContent = state.title || 'جاري التشغيل';
                if (speakerEl) speakerEl.textContent = state.speaker || 'المنصة الصوتية';
                if (thumbEl && state.poster) thumbEl.src = state.poster;
                if (linkEl && state.pageUrl) linkEl.href = state.pageUrl;
                if (volumeEl && typeof state.volume === 'number') volumeEl.value = String(Math.max(0, Math.min(1, state.volume)));
                audio.volume = typeof state.volume === 'number' ? Math.max(0, Math.min(1, state.volume)) : audio.volume;
                setVisible(true);
                setPlayingUI(state.paused === false);
                renderQueuePanel();
                renderTextPanel();

                audio.addEventListener('loadedmetadata', function() {
                    if (typeof state.currentTime === 'number' && state.currentTime > 0 && isFinite(audio.duration) && audio.duration > 1) {
                        audio.currentTime = Math.min(Math.max(0, state.currentTime), Math.max(0, audio.duration - 0.5));
                    }
                    renderTimeline();
                    if (state.paused === false) audio.play().catch(function() {});
                }, { once: true });
            }

            var state = loadState();
            if (state && state.src && state.updatedAt && (Date.now() - state.updatedAt <= 1000 * 60 * 60 * 24)) {
                applyStateAndPlay(state);
            }

            audio.addEventListener('play', function() { setPlayingUI(true); saveState(); });
            audio.addEventListener('pause', function() { setPlayingUI(false); saveState(); });
            audio.addEventListener('loadedmetadata', renderTimeline);
            audio.addEventListener('durationchange', renderTimeline);
            audio.addEventListener('timeupdate', function() {
                renderTimeline();
                syncTextPanelWithTime(audio.currentTime || 0);
                saveState();
            });
            audio.addEventListener('volumechange', function() { if (volumeEl) volumeEl.value = String(audio.volume); saveState(); });
            audio.addEventListener('ended', function() {
                playNextFromQueue();
            });

            if (playBtn) {
                playBtn.addEventListener('click', function() {
                    if (audio.paused) audio.play().catch(function() {});
                    else audio.pause();
                });
            }
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    audio.pause();
                    audio.removeAttribute('src');
                    audio.load();
                    setVisible(false);
                    try { localStorage.removeItem(PLAYER_STATE_KEY); } catch (_) {}
                });
            }
            if (seekEl) {
                var applySeek = function() {
                    var d = getSeekDuration();
                    if (!isFinite(d) || d <= 0) return;
                    var target = (parseFloat(seekEl.value || '0') / 1000) * d;
                    try { audio.currentTime = Math.max(0, target); } catch (_) {}
                    renderTimeline();
                };
                seekEl.addEventListener('input', applySeek);
                seekEl.addEventListener('change', applySeek);
            }
            if (volumeEl) {
                volumeEl.addEventListener('input', function() {
                    var v = Math.max(0, Math.min(1, parseFloat(volumeEl.value || '1')));
                    audio.volume = v;
                    saveState();
                });
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    playNextFromQueue();
                });
            }
            if (queueBtn && queuePanel) {
                if (queuePanel.parentElement !== document.body) {
                    document.body.appendChild(queuePanel);
                }
                queueBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    renderQueuePanel();
                    queuePanel.classList.toggle('is-open');
                });
                queuePanel.addEventListener('click', function(e) {
                    var item = e.target.closest('.audio-global-sticky__queue-item');
                    if (!item) return;
                    var src = item.getAttribute('data-src');
                    var href = item.getAttribute('data-href');
                    if (src) {
                        var q = normalizeQueueItems();
                        var idx = parseInt(item.getAttribute('data-idx') || '-1', 10);
                        if (idx >= 0) {
                            q = q.slice(idx + 1);
                        }
                        window.AlmonajahAudioGlobal.setTrack({
                            id: item.getAttribute('data-id') || null,
                            src: src,
                            title: item.getAttribute('data-title') || 'محتوى صوتي',
                            speaker: item.getAttribute('data-speaker') || 'المنصة الصوتية',
                            poster: item.getAttribute('data-poster') || '',
                            pageUrl: href || window.location.href,
                            queue: q,
                            nextUrl: q.length && q[0].pageUrl ? q[0].pageUrl : ''
                        }, { autoplay: true, toggle: false });
                        queuePanel.classList.remove('is-open');
                        return;
                    }
                    if (href) window.location.href = href;
                });
                document.addEventListener('click', function(e) {
                    if (!queuePanel.classList.contains('is-open')) return;
                    if (e.target.closest('#audioGlobalStickyQueuePanel') || e.target.closest('#audioGlobalStickyQueueBtn')) return;
                    queuePanel.classList.remove('is-open');
                });
            }
            if (textBtn && textPanel) {
                textBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    renderTextPanel();
                    textPanel.classList.toggle('is-open');
                    textBtn.classList.toggle('is-active', textPanel.classList.contains('is-open'));
                });
                document.addEventListener('click', function(e) {
                    if (!textPanel.classList.contains('is-open')) return;
                    if (e.target.closest('#audioGlobalStickyTextPanel') || e.target.closest('#audioGlobalStickyTextBtn')) return;
                    textPanel.classList.remove('is-open');
                    textBtn.classList.remove('is-active');
                });
            }
            window.addEventListener('beforeunload', saveState);

            // Expose global API for all pages.
            window.AlmonajahAudioGlobal = {
                setVisible: setVisible,
                saveState: saveState,
                loadState: loadState,
                applyStateAndPlay: applyStateAndPlay,
                getAudioElement: function() { return audio; },
                getState: loadState,
                isCurrentTrack: function(trackId, src) {
                    var st = loadState() || {};
                    if (trackId != null && st.trackId != null) return String(st.trackId) === String(trackId);
                    return !!(src && st.src && st.src === src);
                },
                setTrack: function(track, opts) {
                    if (!track || !track.src) return;
                    var options = opts || {};
                    var current = loadState() || {};
                    var sameTrack = (track.id != null && current.trackId != null)
                        ? String(track.id) === String(current.trackId)
                        : (current.src && current.src === track.src);
                    if (sameTrack) {
                        if (options.autoplay) audio.play().catch(function() {});
                        else if (options.toggle !== false) {
                            if (audio.paused) audio.play().catch(function() {}); else audio.pause();
                        }
                        return;
                    }
                    var st = {
                        trackId: track.id != null ? String(track.id) : null,
                        src: track.src,
                        title: track.title || 'جاري التشغيل',
                        speaker: track.speaker || 'المنصة الصوتية',
                        poster: track.poster || '',
                        pageUrl: track.pageUrl || window.location.href,
                        nextUrl: track.nextUrl || '',
                        queue: Array.isArray(track.queue) ? track.queue : normalizeQueueItems().filter(function(it) { return !isSameTrack(it, { id: track.id, src: track.src }); }),
                        textContent: track.textContent || '',
                        timedSegments: Array.isArray(track.timedSegments) ? track.timedSegments : [],
                        currentTime: 0,
                        paused: options.autoplay === false,
                        volume: typeof current.volume === 'number' ? current.volume : audio.volume,
                        updatedAt: Date.now()
                    };
                    saveState(st);
                    applyStateAndPlay(st);
                    if (options.autoplay !== false) audio.play().catch(function() {});
                }
            };
        })();

        // Lightweight PJAX inside /audio: keep player alive, replace content only.
        (function() {
            var shell = document.getElementById('audioPageShell');
            if (!shell) return;
            var PLAYER_STATE_KEY = 'almonajah_audio_player_state';

            function isAudioInternalUrl(url) {
                try {
                    var u = new URL(url, window.location.href);
                    return u.origin === window.location.origin && u.pathname.indexOf('/audio') === 0;
                } catch (_) { return false; }
            }

            function snapshotPageAudioState() {
                var pageAudio = document.getElementById('scAudioElement');
                if (!pageAudio) return null;
                // لا نعتبر صفحة التراك الحالية "مصدر الحالة" إلا إذا تم تشغيلها/تحريكها فعليًا.
                if (pageAudio.paused && (pageAudio.currentTime || 0) <= 0.05) return null;
                var srcEl = pageAudio.querySelector('source');
                var src = (srcEl && srcEl.src) ? srcEl.src : (pageAudio.currentSrc || pageAudio.src || '');
                if (!src) return null;
                var titleEl = document.querySelector('.audio-sticky-player__title, .sc-audio-track__title');
                var speakerEl = document.querySelector('.audio-sticky-player__sub, .sc-audio-track__artist');
                var thumbEl = document.querySelector('.audio-sticky-player__thumb, .sc-audio-track__art-img');
                return {
                    src: src,
                    title: titleEl ? titleEl.textContent.trim() : document.title,
                    speaker: speakerEl ? speakerEl.textContent.trim() : 'المنصة الصوتية',
                    poster: thumbEl ? thumbEl.src : '',
                    pageUrl: window.location.href,
                    currentTime: pageAudio.currentTime || 0,
                    duration: isFinite(pageAudio.duration) ? pageAudio.duration : null,
                    paused: pageAudio.paused,
                    updatedAt: Date.now()
                };
            }

            function migratePageAudioToGlobal() {
                // الأولوية دائمًا للحالة الفعلية في المشغّل العالمي.
                if (window.AlmonajahAudioGlobal && typeof window.AlmonajahAudioGlobal.saveState === 'function') {
                    window.AlmonajahAudioGlobal.saveState();
                    return;
                }
                var st = snapshotPageAudioState();
                if (!st) return;
                try { localStorage.setItem(PLAYER_STATE_KEY, JSON.stringify(st)); } catch (_) {}
            }

            function runDynamicScriptsFrom(doc) {
                var tpl = doc.getElementById('audioDynamicScriptsTemplate');
                if (!tpl) return;
                document.querySelectorAll('script[data-audio-pjax-dynamic="1"]').forEach(function(s) { s.remove(); });
                var scripts = tpl.querySelectorAll('script');
                scripts.forEach(function(oldScript) {
                    var s = document.createElement('script');
                    if (oldScript.src) {
                        s.src = oldScript.src;
                        if (oldScript.defer) s.defer = true;
                    } else {
                        s.textContent = oldScript.textContent || '';
                    }
                    s.setAttribute('data-audio-pjax-dynamic', '1');
                    document.body.appendChild(s);
                });
            }

            function runDynamicStylesFrom(doc) {
                var tpl = doc.getElementById('audioDynamicStylesTemplate');
                if (!tpl) return;
                document.head.querySelectorAll('style[data-audio-pjax-style="1"], link[data-audio-pjax-style="1"]').forEach(function(n) { n.remove(); });
                var nodes = tpl.querySelectorAll('style,link[rel="stylesheet"]');
                nodes.forEach(function(oldNode) {
                    var node;
                    if (oldNode.tagName.toLowerCase() === 'style') {
                        node = document.createElement('style');
                        node.textContent = oldNode.textContent || '';
                    } else {
                        node = document.createElement('link');
                        node.rel = 'stylesheet';
                        node.href = oldNode.href;
                    }
                    node.setAttribute('data-audio-pjax-style', '1');
                    document.head.appendChild(node);
                });
            }

            async function navigatePjax(url, push) {
                migratePageAudioToGlobal();
                var res = await fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('navigation failed');
                var html = await res.text();
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newShell = doc.getElementById('audioPageShell');
                if (!newShell) throw new Error('missing shell');
                shell.innerHTML = newShell.innerHTML;
                document.title = doc.title || document.title;
                runDynamicStylesFrom(doc);
                if (push) history.pushState({ audioPjax: true }, '', url);
                runDynamicScriptsFrom(doc);
                window.scrollTo({ top: 0, behavior: 'auto' });
                try {
                    if (window.initNavbarSearchAutocomplete) window.initNavbarSearchAutocomplete();
                } catch (_) {}
            }

            document.addEventListener('click', function(e) {
                var a = e.target.closest ? e.target.closest('a[href]') : null;
                if (!a) return;
                if (a.target === '_blank' || a.hasAttribute('download')) return;
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                var href = a.getAttribute('href') || '';
                if (!href || href[0] === '#') return;
                if (!isAudioInternalUrl(a.href)) return;
                e.preventDefault();
                navigatePjax(a.href, true).catch(function() {
                    window.location.href = a.href;
                });
            }, true);

            window.addEventListener('popstate', function() {
                if (!isAudioInternalUrl(window.location.href)) return;
                navigatePjax(window.location.href, false).catch(function() {
                    window.location.reload();
                });
            });
        })();
    </script>
</body>
</html>
