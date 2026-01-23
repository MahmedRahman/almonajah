@extends('layouts.public')

@section('title', 'ملف الشخصي - المناجاة')

@section('content')
<div class="home-layout">
    <!-- Sidebar -->
    <aside class="sidebar-menu" id="sidebarMenu">
        <div class="sidebar-content">
            <nav class="sidebar-nav">
                <!-- Main Navigation -->
                <a href="{{ route('home') }}" class="sidebar-item {{ request()->routeIs('home') && !request('content_category') ? 'active' : '' }}">
                    <i class="bi bi-house-door"></i>
                    <span class="sidebar-item-text">الرئيسية</span>
                </a>
                <a href="{{ route('shorts') }}" class="sidebar-item {{ request()->routeIs('shorts') ? 'active' : '' }}">
                    <i class="bi bi-play-circle"></i>
                    <span class="sidebar-item-text">فيديوهات قصيرة</span>
                </a>
                
                <!-- Divider -->
                @if(isset($contentCategories) && $contentCategories->count() > 0)
                <div class="sidebar-divider"></div>
                
                <!-- Categories Section -->
                <div class="sidebar-section-header">
                    <h3 class="sidebar-section-title">استكشاف</h3>
                </div>
                @foreach($contentCategories as $category)
                <a href="{{ route('home', ['content_category' => $category]) }}" 
                   class="sidebar-item {{ request('content_category') == $category ? 'active' : '' }}">
                    <i class="bi bi-tag"></i>
                    <span class="sidebar-item-text">{{ $category }}</span>
                </a>
                @endforeach
                @endif
                
                <!-- User Section -->
                <div class="sidebar-divider"></div>
                
                <div class="sidebar-section-header">
                    <h3 class="sidebar-section-title">حسابي</h3>
                </div>
                <a href="{{ route('profile') }}" class="sidebar-item {{ request()->routeIs('profile') ? 'active' : '' }}">
                    <i class="bi bi-person-circle"></i>
                    <span class="sidebar-item-text">ملف الشخصي</span>
                </a>
                <a href="{{ route('favorites') }}" class="sidebar-item {{ request()->routeIs('favorites') ? 'active' : '' }}">
                    <i class="bi bi-bookmark-heart"></i>
                    <span class="sidebar-item-text">المفضلة</span>
                </a>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span class="sidebar-item-text">لوحة التحكم</span>
                </a>
                @endif
                <a href="#" class="sidebar-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-left"></i>
                    <span class="sidebar-item-text">تسجيل الخروج</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </nav>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content-wrapper">
        <div class="container-main">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="bi bi-person-circle"></i>
                    ملف الشخصي
                </h1>
            </div>
            
            <div class="profile-container">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <div class="avatar-circle">
                            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                        </div>
                    </div>
                    <div class="profile-info">
                        <h2 class="profile-name">{{ $user->name }}</h2>
                        <p class="profile-email">{{ $user->email }}</p>
                        <div class="profile-role">
                            <span class="role-badge role-{{ $user->role }}">
                                @if($user->role === 'admin')
                                    مسؤول
                                @elseif($user->role === 'editor')
                                    محرر
                                @else
                                    مستخدم
                                @endif
                            </span>
                        </div>
                        <div class="profile-date">
                            <i class="bi bi-calendar"></i>
                            <span>تاريخ الانضمام: {{ $user->created_at->format('Y/m/d') }}</span>
                        </div>
                    </div>
                </div>

                <div class="profile-stats">
                    <h3 class="stats-title">إحصائياتي</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon stat-likes">
                                <i class="bi bi-heart-fill"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value">{{ $stats['likes_count'] }}</div>
                                <div class="stat-label">إعجابات</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon stat-favorites">
                                <i class="bi bi-bookmark-heart-fill"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value">{{ $stats['favorites_count'] }}</div>
                                <div class="stat-label">مفضلة</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon stat-comments">
                                <i class="bi bi-chat-dots-fill"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value">{{ $stats['comments_count'] }}</div>
                                <div class="stat-label">تعليقات</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Home Layout */
.home-layout {
    display: flex;
    position: relative;
    min-height: calc(100vh - 200px);
    margin-top: 0;
}

/* Sidebar Menu */
.sidebar-menu {
    position: relative;
    width: 260px;
    min-height: calc(100vh - 60px);
    background-color: var(--bg-primary);
    border-left: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    z-index: 1;
    transition: width 0.3s ease, opacity 0.3s ease;
    overflow-y: auto;
    overflow-x: hidden;
    flex-shrink: 0;
}

.sidebar-menu.collapsed {
    width: 0;
    opacity: 0;
    overflow: hidden;
    border: none;
}

.sidebar-content {
    padding: var(--spacing-md);
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.sidebar-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: 0.75rem var(--spacing-sm);
    border-radius: var(--radius-sm);
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.2s ease;
    font-size: 0.9375rem;
    font-weight: 500;
}

.sidebar-item:hover {
    background-color: var(--bg-tertiary);
    color: var(--primary-color);
}

.sidebar-item.active {
    background-color: rgba(24, 135, 129, 0.1);
    color: var(--primary-color);
    font-weight: 600;
}

.sidebar-item i {
    font-size: 1.25rem;
    width: 24px;
    text-align: center;
    flex-shrink: 0;
}

.sidebar-item-text {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-divider {
    height: 1px;
    background-color: var(--border-color);
    margin: var(--spacing-sm) 0;
}

.sidebar-section-header {
    padding: var(--spacing-sm) var(--spacing-sm) var(--spacing-xs);
    margin-top: var(--spacing-xs);
}

.sidebar-section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

/* Main Content Wrapper */
.main-content-wrapper {
    flex: 1;
    margin-right: 0;
    transition: margin-right 0.3s ease;
    width: 100%;
    min-width: 0;
}

/* Container Main */
.container-main {
    max-width: 1400px;
    margin: 0 auto;
    padding: var(--spacing-lg) var(--spacing-md);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .sidebar-menu {
        position: fixed;
        top: 60px;
        right: 0;
        height: calc(100vh - 60px);
        box-shadow: var(--shadow-lg);
        z-index: 1000;
        transform: translateX(100%);
    }
    
    .sidebar-menu:not(.collapsed) {
        transform: translateX(0);
        width: 260px;
        opacity: 1;
    }
    
    .sidebar-menu.collapsed {
        transform: translateX(100%);
        width: 260px;
    }
}

@media (max-width: 768px) {
    .sidebar-menu {
        width: 240px;
        top: 56px;
        height: calc(100vh - 56px);
    }
    
    .sidebar-menu:not(.collapsed) {
        width: 240px;
    }
    
    .sidebar-item {
        padding: 0.625rem var(--spacing-xs);
        font-size: 0.875rem;
    }
    
    .sidebar-item i {
        font-size: 1.125rem;
        width: 20px;
    }
}

/* Overlay for mobile when sidebar is open */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar-overlay.active {
    display: block;
    opacity: 1;
}

@media (max-width: 1024px) {
    .sidebar-overlay.active {
        display: block;
    }
}

.page-header {
    margin-bottom: var(--spacing-lg);
    padding-bottom: var(--spacing-md);
    border-bottom: 2px solid var(--border-color);
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin: 0;
}

.page-title i {
    color: var(--primary-color);
    font-size: 2rem;
}

.profile-container {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg);
}

.profile-card {
    background-color: var(--bg-primary);
    border-radius: var(--radius-md);
    padding: var(--spacing-xl);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: var(--spacing-md);
}

.profile-avatar {
    margin-bottom: var(--spacing-sm);
}

.avatar-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: 700;
    box-shadow: var(--shadow-md);
}

.profile-info {
    width: 100%;
}

.profile-name {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: var(--spacing-xs);
}

.profile-email {
    font-size: 1rem;
    color: var(--text-secondary);
    margin-bottom: var(--spacing-sm);
}

.profile-role {
    margin-bottom: var(--spacing-sm);
}

.role-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-lg);
    font-size: 0.875rem;
    font-weight: 600;
}

.role-admin {
    background-color: #dc3545;
    color: white;
}

.role-editor {
    background-color: #ffc107;
    color: #000;
}

.role-user {
    background-color: var(--bg-tertiary);
    color: var(--text-primary);
}

.profile-date {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.profile-stats {
    background-color: var(--bg-primary);
    border-radius: var(--radius-md);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.stats-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: var(--spacing-md);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
}

.stat-card {
    background-color: var(--bg-secondary);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    border: 1px solid var(--border-color);
    transition: all 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    color: white;
    flex-shrink: 0;
}

.stat-likes {
    background: linear-gradient(135deg, #e91e63 0%, #c2185b 100%);
}

.stat-favorites {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
}

.stat-comments {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 500;
}

@media (max-width: 768px) {
    .profile-card {
        padding: var(--spacing-lg);
    }
    
    .avatar-circle {
        width: 100px;
        height: 100px;
        font-size: 2.5rem;
    }
    
    .profile-name {
        font-size: 1.5rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush
