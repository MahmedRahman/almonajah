<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'المناجاة - إدارة المحتوى الرقمي')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #188781;
            --secondary-color: #1f9f97;
            --accent-color: #f6bd21;
        }
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #188781 0%, #1f9f97 100%);
            color: white;
            padding: 20px 0;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .main-content {
            padding: 30px;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #188781 0%, #1f9f97 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
        }
        .stat-card.blue {
            background: linear-gradient(135deg, #188781 0%, #1f9f97 100%);
        }
        .stat-card.green {
            background: linear-gradient(135deg, #1f9f97 0%, #188781 100%);
        }
        .stat-card.orange {
            background: linear-gradient(135deg, #f6bd21 0%, #e6ad1f 100%);
        }
        .stat-card.purple {
            background: linear-gradient(135deg, #188781 0%, #1f9f97 100%);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(24, 135, 129, 0.25);
        }
        .pagination {
            margin-bottom: 0;
        }
        .pagination .page-link {
            color: var(--primary-color);
            border-color: #dee2e6;
        }
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .pagination .page-link:hover {
            color: var(--secondary-color);
            background-color: #e9ecef;
            border-color: #dee2e6;
        }
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #fff;
            border-color: #dee2e6;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <div class="text-center mb-4">
                    <h4 class="fw-bold">المناجاة</h4>
                    <small>إدارة المحتوى الرقمي</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2 me-2"></i> لوحة التحكم
                    </a>
                    <a class="nav-link {{ request()->routeIs('content.*') ? 'active' : '' }}" href="{{ route('content.index') }}">
                        <i class="bi bi-file-text me-2"></i> المحتوى
                    </a>
                    <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                        <i class="bi bi-tags me-2"></i> التصنيفات
                    </a>
                    <a class="nav-link {{ request()->routeIs('media.*') ? 'active' : '' }}" href="{{ route('media.index') }}">
                        <i class="bi bi-images me-2"></i> الوسائط
                    </a>
                    <a class="nav-link {{ request()->routeIs('assets.index') || request()->routeIs('assets.show') || request()->routeIs('assets.destroy') ? 'active' : '' }}" href="{{ route('assets.index') }}">
                        <i class="bi bi-play-circle me-2"></i> الفيديوهات
                    </a>
                    <a class="nav-link {{ request()->routeIs('assets.analytics') ? 'active' : '' }}" href="{{ route('assets.analytics') }}">
                        <i class="bi bi-graph-up me-2"></i> تحليل الفيديوهات
                    </a>
                    <hr class="text-white-50">
                    <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                        <i class="bi bi-gear me-2"></i> الإعدادات
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-link w-100 text-start border-0 bg-transparent">
                            <i class="bi bi-box-arrow-right me-2"></i> تسجيل الخروج
                        </button>
                    </form>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>

