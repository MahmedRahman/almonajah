<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'المناجاة - منصة المحتوى الرقمي')</title>
    
    @yield('meta')
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
            --primary-hover: #1f9f97;
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
            font-family: 'Cairo', sans-serif;
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

        .navbar-menu-btn {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            padding: 0.5rem;
            margin-left: var(--spacing-sm);
            cursor: pointer;
            border-radius: 50%;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        .navbar-menu-btn:hover {
            background-color: var(--bg-tertiary);
        }

        .navbar-menu-btn.active {
            background-color: var(--bg-tertiary);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            margin-right: var(--spacing-lg);
        }
        
        .navbar-nav {
            margin-right: auto;
            margin-left: 0;
        }

        .user-name-display {
            color: var(--text-primary) !important;
            font-weight: 600;
            cursor: default;
            padding: var(--spacing-xs) var(--spacing-sm) !important;
        }

        .user-name-display:hover {
            background-color: transparent !important;
            color: var(--text-primary) !important;
        }

        .navbar-logo {
            height: 50px;
            width: auto;
            object-fit: contain;
            max-width: 200px;
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
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            box-shadow: var(--shadow-sm);
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
            background: linear-gradient(135deg, #188781 0%, #1f9f97 100%);
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
            box-shadow: 0 0 0 3px rgba(24, 135, 129, 0.1);
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
            box-shadow: 0 0 0 3px rgba(24, 135, 129, 0.1);
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
            margin-bottom: var(--spacing-sm);
            padding-bottom: 0;
            border-bottom: none;
        }

        .video-details-meta span {
            font-size: 0.875rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .video-description {
            margin-top: var(--spacing-sm);
            padding-top: 0;
            border-top: none;
        }

        .video-description-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
        }

        .video-description-text {
            font-size: 1.0625rem;
            color: var(--text-secondary);
            line-height: 1.9;
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

        /* Content Categories Filter */
        .content-categories-filter {
            background-color: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            padding: var(--spacing-sm) 0;
            position: sticky;
            top: 60px;
            z-index: 999;
            box-shadow: var(--shadow-sm);
        }

        .categories-scroll {
            display: flex;
            gap: var(--spacing-xs);
            overflow-x: auto;
            overflow-y: hidden;
            padding: var(--spacing-xs) 0;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: var(--border-color) transparent;
        }

        .categories-scroll::-webkit-scrollbar {
            height: 4px;
        }

        .categories-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .categories-scroll::-webkit-scrollbar-thumb {
            background-color: var(--border-color);
            border-radius: 2px;
        }

        .category-btn {
            padding: 0.5rem 1.25rem;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            white-space: nowrap;
            transition: all 0.2s ease;
            display: inline-block;
        }

        .category-btn:hover {
            background-color: var(--bg-tertiary);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .category-btn.active {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .category-btn.active:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            color: white;
        }

        @media (max-width: 768px) {
            .content-categories-filter {
                top: 56px;
            }

            .category-btn {
                padding: 0.4rem 1rem;
                font-size: 0.8125rem;
            }
        }

        /* Auth Modal Styles */
        .auth-modal-content {
            border-radius: var(--radius-lg);
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .auth-modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: var(--spacing-lg) var(--spacing-md) var(--spacing-md);
            background: linear-gradient(135deg, rgba(24, 135, 129, 0.05) 0%, rgba(31, 159, 151, 0.05) 100%);
            position: relative;
        }

        .auth-modal-header-content {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .auth-modal-logo {
            height: 32px;
            width: auto;
            object-fit: contain;
        }

        .auth-modal-header .modal-title {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1.25rem;
            margin: 0;
        }

        .auth-modal-close {
            position: absolute;
            top: var(--spacing-md);
            left: var(--spacing-md);
            background: rgba(0, 0, 0, 0.05);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.8;
            transition: all 0.2s ease;
            padding: 0;
            border: none;
            cursor: pointer;
            z-index: 10;
        }

        .auth-modal-close i {
            color: #000000;
            font-size: 1.25rem;
            font-weight: 600;
            line-height: 1;
        }

        .auth-modal-close:hover {
            opacity: 1;
            background: rgba(0, 0, 0, 0.1);
            transform: rotate(90deg);
        }

        .auth-modal-close:hover i {
            color: #000000;
        }

        .auth-modal-body {
            padding: var(--spacing-lg);
        }

        .auth-nav-tabs {
            border-bottom: 2px solid var(--border-color);
            gap: var(--spacing-xs);
        }

        .auth-nav-tabs .nav-link {
            border: none;
            border-bottom: 2px solid transparent;
            color: var(--text-secondary);
            padding: var(--spacing-sm) var(--spacing-md);
            transition: all 0.2s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm) var(--radius-sm) 0 0;
        }

        .auth-nav-tabs .nav-link:hover {
            border-color: var(--border-color);
            color: var(--primary-color);
            background-color: rgba(24, 135, 129, 0.05);
        }

        .auth-nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            background-color: transparent;
            font-weight: 600;
        }

        .auth-modal-body .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.9375rem;
        }

        .auth-modal-body .form-control {
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
            font-size: 0.9375rem;
        }

        .auth-modal-body .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(24, 135, 129, 0.1);
            outline: none;
        }

        .auth-modal-body .form-control.is-invalid {
            border-color: #dc3545;
        }

        .auth-modal-body .invalid-feedback {
            display: block;
            font-size: 0.8125rem;
            color: #dc3545;
            margin-top: 0.375rem;
            font-weight: 500;
        }

        .auth-modal-body .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.2s ease;
            border-radius: var(--radius-sm);
            font-size: 1rem;
            margin-top: var(--spacing-sm);
        }

        .auth-modal-body .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(24, 135, 129, 0.3);
        }

        .auth-modal-body .btn-primary:active {
            transform: translateY(0);
        }

        .auth-modal-body .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .auth-modal-body .form-check {
            margin-top: var(--spacing-sm);
        }

        .auth-modal-body .form-check-input {
            border: 2px solid var(--border-color);
            cursor: pointer;
        }

        .auth-modal-body .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .auth-modal-body .form-check-label {
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 0.9375rem;
        }

        .auth-modal-body .alert-danger {
            border-radius: var(--radius-sm);
            border: 1px solid #dc3545;
            font-size: 0.875rem;
            padding: 0.75rem 1rem;
            margin-bottom: var(--spacing-sm);
        }

        @media (max-width: 576px) {
            .auth-modal-header {
                padding: var(--spacing-md) var(--spacing-sm) var(--spacing-sm);
            }

            .auth-modal-body {
                padding: var(--spacing-md);
            }

            .auth-modal-header .modal-title {
                font-size: 1.125rem;
            }

            .auth-nav-tabs .nav-link {
                padding: var(--spacing-xs) var(--spacing-sm);
                font-size: 0.875rem;
            }
        }

        .dropdown-menu {
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: var(--bg-tertiary);
            color: var(--primary-color);
        }

        /* Google Button */
        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: white;
            color: #757575;
            border: 1px solid #dadce0;
            padding: 0.75rem 1rem;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            gap: 0.75rem;
        }

        .btn-google:hover {
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            color: #757575;
        }

        .btn-google svg {
            flex-shrink: 0;
        }

        /* Password Input Wrapper */
        .password-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-wrapper .form-control {
            padding-right: 3rem;
            direction: rtl;
            text-align: right;
        }

        .password-toggle-btn {
            position: absolute;
            right: 0.75rem;
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
            z-index: 10;
        }

        .password-toggle-btn:hover {
            color: var(--primary-color);
        }

        .password-toggle-btn i {
            font-size: 1.125rem;
        }

        /* Divider */
        .divider-with-text {
            position: relative;
            text-align: center;
            margin: 1rem 0;
        }

        .divider-with-text::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: var(--border-color);
        }

        .divider-with-text span {
            position: relative;
            background-color: var(--bg-primary);
            padding: 0 1rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-4">
            <button class="navbar-menu-btn" id="navbarMenuBtn" onclick="toggleSidebar()" title="إظهار/إخفاء القائمة">
                <i class="bi bi-list"></i>
            </button>
            <a class="navbar-brand" href="{{ route('home') }}">
                <img src="{{ asset('images/logo.png') }}" alt="المناجاة" class="navbar-logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
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
                                    <input type="email" class="form-control" id="loginEmail" name="email" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="loginPassword" class="form-label">كلمة المرور</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" class="form-control" id="loginPassword" name="password" required>
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
                                    <input type="text" class="form-control" id="registerName" name="name" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="registerEmail" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="registerEmail" name="email" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="registerPassword" class="form-label">كلمة المرور</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" class="form-control" id="registerPassword" name="password" required>
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
                
                // Clear errors
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
                document.querySelectorAll('.alert-danger').forEach(el => el.classList.add('d-none'));
                
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
    </script>
</body>
</html>
