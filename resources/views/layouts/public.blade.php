<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <title>@yield('title', 'المناجاة - منصة المحتوى الرقمي')</title>
    
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
    <link rel="stylesheet" href="{{ asset('css/public.css') }}?v=5">
    <style>
        .audio-global-sticky {
            position: fixed; inset-inline: 0; bottom: 0; z-index: 1190; display: none;
            align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.55rem 1rem;
            background: rgba(10, 10, 10, 0.96); border-top: 1px solid rgba(255,255,255,0.12); backdrop-filter: blur(8px); direction: rtl;
        }
        .audio-global-sticky.is-visible { display: flex; }
        .audio-global-sticky__meta { display: flex; align-items: center; gap: 0.7rem; min-width: 0; order: 1; }
        .audio-global-sticky__thumb { width: 46px; height: 46px; border-radius: 8px; object-fit: cover; flex-shrink: 0; }
        .audio-global-sticky__text { min-width: 0; }
        .audio-global-sticky__title { color: #f5f5f5; font-size: 0.9rem; font-weight: 700; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: right; }
        .audio-global-sticky__sub { color: #aaa; font-size: 0.8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: right; }
        .audio-global-sticky__middle { flex: 1; min-width: 180px; max-width: 520px; display: flex; flex-direction: column; gap: 0.2rem; order: 2; }
        .audio-global-sticky__timeline { display: flex; align-items: center; gap: 0.5rem; }
        .audio-global-sticky__time { color: #c8c8c8; font-size: 0.72rem; min-width: 2.2rem; text-align: center; }
        .audio-global-sticky__seek { width: 100%; accent-color: #188781; direction: ltr; }
        .audio-global-sticky__controls { display: inline-flex; align-items: center; gap: 0.4rem; flex-shrink: 0; order: 3; }
        .audio-global-sticky__volume { width: 90px; accent-color: #188781; }
        .audio-global-sticky__queue-wrap { position: relative; }
        .audio-global-sticky__queue-panel {
            position: absolute; bottom: calc(100% + 10px); left: 0; width: min(420px, 88vw);
            max-height: 360px; overflow: auto; background: linear-gradient(180deg, #111 0%, #0b0b0b 100%); border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px; box-shadow: 0 12px 34px rgba(0,0,0,0.52); display: none; padding: 0.4rem;
        }
        .audio-global-sticky__queue-panel.is-open { display: block; }
        .audio-global-sticky__queue-header { display: flex; align-items: center; justify-content: space-between; padding: 0.35rem 0.5rem 0.45rem; border-bottom: 1px solid rgba(255,255,255,0.09); margin-bottom: 0.3rem; }
        .audio-global-sticky__queue-title-main { color: #fff; font-weight: 700; font-size: 0.86rem; }
        .audio-global-sticky__queue-count { color: #9ca3af; font-size: 0.74rem; }
        .audio-global-sticky__queue-item { width: 100%; text-align: right; border: 0; background: transparent; color: #eee; display: grid; grid-template-columns: 40px 1fr auto; gap: 0.6rem; align-items: center; padding: 0.48rem; border-radius: 10px; cursor: pointer; }
        .audio-global-sticky__queue-item:hover { background: rgba(255,255,255,0.08); }
        .audio-global-sticky__queue-item.is-upnext { background: rgba(24, 135, 129, 0.18); }
        .audio-global-sticky__queue-item img { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; }
        .audio-global-sticky__queue-title { font-size: 0.86rem; font-weight: 600; line-height: 1.2; }
        .audio-global-sticky__queue-sub { font-size: 0.74rem; color: #aaa; }
        .audio-global-sticky__queue-badge { font-size: 0.68rem; color: #d1fae5; background: rgba(24, 135, 129, 0.25); border: 1px solid rgba(24, 135, 129, 0.55); border-radius: 999px; padding: 0.14rem 0.45rem; }
        .audio-global-sticky__queue-empty { color: #888; font-size: 0.8rem; padding: 0.6rem; text-align: center; }
        .audio-global-sticky__btn { width: 2.2rem; height: 2.2rem; border: 1px solid rgba(255,255,255,0.18); border-radius: 999px; background: rgba(255,255,255,0.08); color: #fff; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .audio-global-sticky__btn:hover { background: rgba(24, 135, 129, 0.22); border-color: rgba(24, 135, 129, 0.6); }
        .audio-global-sticky__link { color: inherit; text-decoration: none; }
        @media (max-width: 900px) {
            .audio-global-sticky { display: none; grid-template-columns: auto 1fr auto; align-items: center; row-gap: 0.35rem; }
            .audio-global-sticky.is-visible { display: grid; }
            .audio-global-sticky__meta { grid-column: 1 / 3; }
            .audio-global-sticky__controls { grid-column: 3 / 4; justify-self: end; }
            .audio-global-sticky__middle { grid-column: 1 / 4; max-width: none; min-width: 0; }
            .audio-global-sticky__volume { width: 72px; }
        }
    </style>
    @stack('styles')
</head>
<body class="{{ request()->routeIs('shorts') ? 'shorts-page' : '' }}">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="navbar-inner">
            <div class="navbar-left">
                <button class="navbar-menu-btn" id="navbarMenuBtn" onclick="toggleSidebar()" title="إظهار/إخفاء القائمة">
                    <i class="bi bi-list"></i>
                </button>
                <a class="navbar-brand" href="{{ route('home') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="المناجاة" class="navbar-logo">
                </a>
                <a href="{{ route('audio.home') }}" class="btn btn-sm btn-outline-secondary ms-2 d-none d-md-inline-flex align-items-center gap-1" title="المنصة الصوتية">
                    <i class="bi bi-mic"></i><span>الصوت</span>
                </a>
            </div>
            <div class="navbar-search-wrap">
                <form class="navbar-search-form" id="navbarSearchForm" action="{{ route('home') }}" method="get" role="search">
                    <input type="text" class="navbar-search-input" id="navbarSearchInput" name="search" value="{{ request('search') }}" placeholder="بحث في العناوين والشيوخ والوصف..." autocomplete="off" aria-label="بحث">
                    <button type="submit" class="navbar-search-btn" title="بحث">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
                <div class="navbar-search-dropdown" id="navbarSearchDropdown" role="listbox" aria-hidden="true"></div>
            </div>
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
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <h5>المناجاة</h5>
            <p>منصة المحتوى الرقمي</p>
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
        <a class="audio-global-sticky__meta audio-global-sticky__link" id="audioGlobalStickyLink" href="#">
            <img src="{{ asset('images/logo_min.png') }}" alt="" class="audio-global-sticky__thumb" id="audioGlobalStickyThumb" width="46" height="46">
            <div class="audio-global-sticky__text">
                <div class="audio-global-sticky__title" id="audioGlobalStickyTitle">جاري التشغيل</div>
                <div class="audio-global-sticky__sub" id="audioGlobalStickySpeaker">المنصة الصوتية</div>
            </div>
        </a>
        <div class="audio-global-sticky__controls">
            <input type="range" id="audioGlobalStickyVolume" class="audio-global-sticky__volume" min="0" max="1" step="0.01" value="1" aria-label="مستوى الصوت">
            <div class="audio-global-sticky__queue-wrap">
                <button type="button" class="audio-global-sticky__btn" id="audioGlobalStickyQueueBtn" aria-label="قائمة التالي"><i class="bi bi-music-note-list"></i></button>
                <div class="audio-global-sticky__queue-panel" id="audioGlobalStickyQueuePanel"></div>
            </div>
            <button type="button" class="audio-global-sticky__btn" id="audioGlobalStickyNextBtn" aria-label="التالي"><i class="bi bi-skip-forward-fill"></i></button>
            <button type="button" class="audio-global-sticky__btn" id="audioGlobalStickyPlayBtn" aria-label="تشغيل أو إيقاف"><i class="bi bi-play-fill" id="audioGlobalStickyPlayIcon"></i></button>
            <button type="button" class="audio-global-sticky__btn" id="audioGlobalStickyCloseBtn" aria-label="إيقاف وإخفاء"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="audio-global-sticky__middle">
            <div class="audio-global-sticky__timeline">
                <span id="audioGlobalStickyCur" class="audio-global-sticky__time">0:00</span>
                <input type="range" id="audioGlobalStickySeek" class="audio-global-sticky__seek" min="0" max="1000" step="1" value="0" aria-label="موضع التشغيل">
                <span id="audioGlobalStickyDur" class="audio-global-sticky__time">—:—</span>
            </div>
        </div>
        <audio id="audioGlobalStickyElement" class="visually-hidden" preload="metadata" playsinline></audio>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
    
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
            var suggestionsUrl = '{{ route("search.suggestions") }}';
            var homeUrl = '{{ route("home") }}';
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
                fetch(suggestionsUrl + '?q=' + encodeURIComponent(query), { headers: { 'Accept': 'application/json' } })
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

        // Global sticky audio player on public layout too
        (function() {
            if (window.AlmonajahAudioGlobal) return;
            var PLAYER_STATE_KEY = 'almonajah_audio_player_state';
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
            if (!sticky || !audio) return;

            function fmt(s) { if (!isFinite(s) || s < 0) return '0:00'; s = Math.floor(s); var m = Math.floor(s / 60); var sec = s % 60; return m + ':' + (sec < 10 ? '0' : '') + sec; }
            function setVisible(v) { sticky.classList.toggle('is-visible', !!v); document.body.style.paddingBottom = v ? '82px' : ''; }
            function setPlayingUI(playing) { if (playIcon) playIcon.className = playing ? 'bi bi-pause-fill' : 'bi bi-play-fill'; }
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
                var d = getSeekDuration(); var c = audio.currentTime || 0;
                if (curEl) curEl.textContent = fmt(c);
                if (durEl) durEl.textContent = isFinite(d) && d > 0 ? fmt(d) : '—:—';
                if (seekEl && isFinite(d) && d > 0) seekEl.value = String(Math.min(1000, Math.max(0, (c / d) * 1000)));
                else if (seekEl) seekEl.value = '0';
            }
            function normalizeQueueItems(state) {
                var q = (state && Array.isArray(state.queue)) ? state.queue : [];
                return q.filter(function(it) { return it && it.pageUrl; });
            }
            function renderQueuePanel() {
                if (!queuePanel) return;
                var st = loadState() || {};
                var q = normalizeQueueItems(st);
                if (!q.length) {
                    queuePanel.innerHTML = '<div class="audio-global-sticky__queue-empty">لا يوجد عناصر في قائمة التالي</div>';
                    return;
                }
                var rows = q.map(function(item, idx) {
                    var title = (item.title || 'محتوى صوتي').replace(/</g, '&lt;');
                    var sub = (item.speaker || 'المنصة الصوتية').replace(/</g, '&lt;');
                    var poster = (item.poster || '').replace(/"/g, '&quot;');
                    var href = (item.pageUrl || '').replace(/"/g, '&quot;');
                    var upnext = idx === 0 ? ' is-upnext' : '';
                    var badge = idx === 0 ? '<span class="audio-global-sticky__queue-badge">التالي</span>' : '';
                    return '<button type="button" class="audio-global-sticky__queue-item' + upnext + '" data-idx="' + idx + '" data-href="' + href + '">' +
                        '<img src="' + poster + '" alt="">' +
                        '<span><div class="audio-global-sticky__queue-title">' + title + '</div><div class="audio-global-sticky__queue-sub">' + sub + '</div></span>' +
                        badge +
                    '</button>';
                }).join('');
                queuePanel.innerHTML = '<div class="audio-global-sticky__queue-header"><span class="audio-global-sticky__queue-title-main">قائمة التشغيل التالية</span><span class="audio-global-sticky__queue-count">' + q.length + ' عناصر</span></div>' + rows;
            }
            function saveState(extra) {
                try {
                    var prev = {}; try { prev = JSON.parse(localStorage.getItem(PLAYER_STATE_KEY) || '{}'); } catch (_) {}
                    localStorage.setItem(PLAYER_STATE_KEY, JSON.stringify(Object.assign({}, prev, {
                        currentTime: audio.currentTime || 0, duration: isFinite(audio.duration) ? audio.duration : null,
                        paused: audio.paused, volume: audio.volume, updatedAt: Date.now()
                    }, extra || {})));
                } catch (_) {}
            }
            function loadState() { try { var raw = localStorage.getItem(PLAYER_STATE_KEY); return raw ? JSON.parse(raw) : null; } catch (_) { return null; } }
            function applyStateAndPlay(state) {
                if (!state || !state.src) return;
                audio.src = state.src; audio.load();
                if (titleEl) titleEl.textContent = state.title || 'جاري التشغيل';
                if (speakerEl) speakerEl.textContent = state.speaker || 'المنصة الصوتية';
                if (thumbEl && state.poster) thumbEl.src = state.poster;
                if (linkEl && state.pageUrl) linkEl.href = state.pageUrl;
                audio.volume = typeof state.volume === 'number' ? Math.max(0, Math.min(1, state.volume)) : audio.volume;
                if (volumeEl) volumeEl.value = String(audio.volume);
                setVisible(true); setPlayingUI(state.paused === false);
                renderQueuePanel();
                audio.addEventListener('loadedmetadata', function() {
                    if (typeof state.currentTime === 'number' && state.currentTime > 0 && isFinite(audio.duration) && audio.duration > 1) {
                        audio.currentTime = Math.min(Math.max(0, state.currentTime), Math.max(0, audio.duration - 0.5));
                    }
                    renderTimeline();
                    if (state.paused === false) audio.play().catch(function() {});
                }, { once: true });
            }

            var state = loadState();
            if (state && state.src && state.updatedAt && (Date.now() - state.updatedAt <= 1000 * 60 * 60 * 24)) applyStateAndPlay(state);

            audio.addEventListener('play', function() { setPlayingUI(true); saveState(); });
            audio.addEventListener('pause', function() { setPlayingUI(false); saveState(); });
            audio.addEventListener('loadedmetadata', renderTimeline);
            audio.addEventListener('durationchange', renderTimeline);
            audio.addEventListener('timeupdate', function() { renderTimeline(); saveState(); });
            audio.addEventListener('volumechange', function() { if (volumeEl) volumeEl.value = String(audio.volume); saveState(); });
            audio.addEventListener('ended', function() {
                var st = loadState() || {};
                var q = normalizeQueueItems(st);
                if (q.length && q[0].pageUrl) { window.location.href = q[0].pageUrl; return; }
                if (st.nextUrl) window.location.href = st.nextUrl;
            });

            if (playBtn) playBtn.addEventListener('click', function() { if (audio.paused) audio.play().catch(function() {}); else audio.pause(); });
            if (closeBtn) closeBtn.addEventListener('click', function() { audio.pause(); audio.removeAttribute('src'); audio.load(); setVisible(false); try { localStorage.removeItem(PLAYER_STATE_KEY); } catch (_) {} });
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
            if (volumeEl) volumeEl.addEventListener('input', function() { audio.volume = Math.max(0, Math.min(1, parseFloat(volumeEl.value || '1'))); saveState(); });
            if (nextBtn) nextBtn.addEventListener('click', function() {
                var st = loadState() || {};
                var q = normalizeQueueItems(st);
                if (q.length && q[0].pageUrl) { window.location.href = q[0].pageUrl; return; }
                if (st.nextUrl) window.location.href = st.nextUrl;
            });
            if (queueBtn && queuePanel) {
                queueBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    renderQueuePanel();
                    queuePanel.classList.toggle('is-open');
                });
                queuePanel.addEventListener('click', function(e) {
                    var item = e.target.closest('.audio-global-sticky__queue-item');
                    if (!item) return;
                    var href = item.getAttribute('data-href');
                    if (href) window.location.href = href;
                });
                document.addEventListener('click', function(e) {
                    if (!queuePanel.classList.contains('is-open')) return;
                    if (e.target.closest('#audioGlobalStickyQueuePanel') || e.target.closest('#audioGlobalStickyQueueBtn')) return;
                    queuePanel.classList.remove('is-open');
                });
            }
            window.addEventListener('beforeunload', saveState);

            window.AlmonajahAudioGlobal = {
                setVisible: setVisible, saveState: saveState, loadState: loadState, applyStateAndPlay: applyStateAndPlay,
                getAudioElement: function() { return audio; }, getState: loadState,
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
                        else if (options.toggle !== false) { if (audio.paused) audio.play().catch(function() {}); else audio.pause(); }
                        return;
                    }
                    var st = {
                        trackId: track.id != null ? String(track.id) : null, src: track.src,
                        title: track.title || 'جاري التشغيل', speaker: track.speaker || 'المنصة الصوتية',
                        poster: track.poster || '', pageUrl: track.pageUrl || window.location.href,
                        nextUrl: track.nextUrl || '', queue: Array.isArray(track.queue) ? track.queue : [], currentTime: 0, paused: options.autoplay === false,
                        volume: typeof current.volume === 'number' ? current.volume : audio.volume, updatedAt: Date.now()
                    };
                    saveState(st); applyStateAndPlay(st); if (options.autoplay !== false) audio.play().catch(function() {});
                }
            };
        })();
    </script>
</body>
</html>
