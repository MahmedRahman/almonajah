<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'المناجاة - منصة المحتوى الرقمي')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-tertiary: #f3f4f6;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
            font-size: 16px;
        }

        /* Navbar */
        .navbar {
            background-color: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            padding: var(--spacing-sm) 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .navbar-brand:hover {
            color: var(--primary-hover) !important;
        }

        .nav-link {
            color: var(--text-primary) !important;
            font-weight: 500;
            padding: var(--spacing-xs) var(--spacing-sm) !important;
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background-color: var(--bg-tertiary);
            color: var(--primary-color) !important;
        }

        /* Container */
        .container-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: var(--spacing-lg) var(--spacing-md);
        }

        /* Video Grid */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: var(--spacing-md);
            margin-top: var(--spacing-md);
        }

        @media (max-width: 768px) {
            .video-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: var(--spacing-sm);
            }
        }

        /* Video Card */
        .video-card {
            background-color: var(--bg-primary);
            border-radius: var(--radius-md);
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            box-shadow: var(--shadow-sm);
        }

        .video-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .video-thumbnail {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            background-color: var(--bg-tertiary);
            overflow: hidden;
        }

        .video-thumbnail video,
        .video-thumbnail img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .video-thumbnail img {
            display: block;
        }

        .video-thumbnail-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .video-thumbnail-placeholder i {
            font-size: 3rem;
        }

        .video-duration {
            position: absolute;
            bottom: 0.5rem;
            left: 0.5rem;
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .video-info {
            padding: var(--spacing-sm);
        }

        .video-title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
        }

        .video-meta {
            font-size: 0.8125rem;
            color: var(--text-secondary);
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
        }

        /* Search and Filters */
        .search-section {
            background-color: var(--bg-primary);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
            box-shadow: var(--shadow-sm);
        }

        .search-input {
            width: 100%;
            padding: var(--spacing-sm);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .filter-group {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
            margin-top: var(--spacing-sm);
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: var(--radius-sm);
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        /* Video Player Page */
        .video-player-section {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: var(--spacing-lg);
            margin-top: var(--spacing-md);
        }

        @media (max-width: 1024px) {
            .video-player-section {
                grid-template-columns: 1fr;
            }
        }

        .video-player-container {
            background-color: var(--bg-primary);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .video-player-container video {
            width: 100%;
            display: block;
        }

        .video-details {
            background-color: var(--bg-primary);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-top: var(--spacing-md);
            box-shadow: var(--shadow-sm);
        }

        .video-details-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
            line-height: 1.3;
        }

        .video-details-meta {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
            margin-bottom: var(--spacing-md);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
        }

        .video-details-meta span {
            font-size: 0.875rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .video-description {
            margin-top: var(--spacing-md);
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--border-color);
        }

        .video-description-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
        }

        .video-description-text {
            font-size: 0.9375rem;
            color: var(--text-secondary);
            line-height: 1.7;
            white-space: pre-wrap;
        }

        /* Sidebar */
        .sidebar {
            background-color: var(--bg-primary);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-sm);
        }

        .sidebar-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--spacing-md);
        }

        .related-video {
            display: flex;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
            padding: var(--spacing-sm);
            border-radius: var(--radius-sm);
            transition: background-color 0.2s ease;
            text-decoration: none;
            color: inherit;
        }

        .related-video:last-child {
            margin-bottom: 0;
        }

        .related-video:hover {
            background-color: var(--bg-tertiary);
        }

        .related-video-thumb {
            width: 168px;
            height: 94px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            flex-shrink: 0;
            background-color: var(--bg-tertiary);
            position: relative;
        }

        .related-video-thumb video,
        .related-video-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .related-video-info {
            flex: 1;
        }

        .related-video-title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
        }

        .related-video-meta {
            font-size: 0.8125rem;
            color: var(--text-secondary);
        }

        /* Footer */
        .footer {
            background-color: var(--bg-primary);
            border-top: 1px solid var(--border-color);
            padding: var(--spacing-xl) 0;
            margin-top: var(--spacing-xl);
            text-align: center;
        }

        .footer h5 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: var(--spacing-sm);
        }

        .footer p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-sm);
            font-size: 0.8125rem;
            font-weight: 500;
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .badge-primary {
            background-color: var(--primary-color);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: var(--spacing-xl) var(--spacing-md);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-secondary);
            margin-bottom: var(--spacing-md);
        }

        .empty-state p {
            color: var(--text-secondary);
            font-size: 1.125rem;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: var(--spacing-xs);
            margin-top: var(--spacing-xl);
        }

        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .page-link:hover {
            background-color: var(--bg-tertiary);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="{{ route('home') }}">
                <i class="bi bi-play-circle-fill"></i>
                <span>المناجاة</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}">
                            <i class="bi bi-house me-1"></i>الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('shorts') }}">
                            <i class="bi bi-camera-reels me-1"></i>Shorts
                        </a>
                    </li>
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
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
