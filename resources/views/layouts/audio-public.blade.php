<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
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
    <link rel="stylesheet" href="{{ asset('css/public.css') }}?v=6">
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
    </script>
</body>
</html>
