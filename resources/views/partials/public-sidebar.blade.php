<aside class="sidebar-menu" id="sidebarMenu">
    <div class="sidebar-content">
        <nav class="sidebar-nav">
            <a href="{{ route('home') }}" class="sidebar-item {{ request()->routeIs('home') && !request('content_category') && !request('scholar_id') ? 'active' : '' }}">
                <img src="{{ asset('images/home-icon.png') }}" alt="الرئيسية" class="sidebar-item-icon-img" width="24" height="24">
                <span class="sidebar-item-text">الرئيسية</span>
            </a>
            <a href="{{ route('audio.home') }}" class="sidebar-item {{ request()->routeIs('audio.*') ? 'active' : '' }}">
                <img src="{{ asset('images/audio-icon.png') }}" alt="المنصة الصوتية" class="sidebar-item-icon-img" width="24" height="24">
                <span class="sidebar-item-text">المنصة الصوتية</span>
            </a>
            <a href="{{ route('shorts') }}" class="sidebar-item {{ request()->routeIs('shorts') ? 'active' : '' }}">
                <img src="{{ asset('images/shorts-icon.png') }}" alt="فيديوهات قصيرة" class="sidebar-item-icon-img" width="24" height="24">
                <span class="sidebar-item-text">فيديوهات قصيرة</span>
            </a>
            <a href="{{ route('public.playlists') }}" class="sidebar-item {{ request()->routeIs('public.playlists') || request()->routeIs('public.playlist.show') ? 'active' : '' }}">
                <img src="{{ asset('images/playlists-icon.png') }}" alt="قوائم التشغيل" class="sidebar-item-icon-img" width="24" height="24">
                <span class="sidebar-item-text">قوائم التشغيل</span>
            </a>
            <a href="{{ route('public.scholars') }}" class="sidebar-item {{ \App\Support\SiteSettings::showScholarsInSidebar() ? '' : 'd-none' }} {{ request()->routeIs('public.scholars') || request()->routeIs('public.scholar.show') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i>
                <span class="sidebar-item-text">الشيوخ</span>
            </a>
            <a href="{{ route('live') }}" class="sidebar-item {{ request()->routeIs('live') ? 'active' : '' }}">
                <img src="{{ asset('images/live-icon.png') }}" alt="بث مباشر" class="sidebar-item-icon-img" width="24" height="24">
                <span class="sidebar-item-text">بث مباشر</span>
            </a>

            @if(isset($categories) && $categories->count() > 0)
                <div class="sidebar-divider"></div>
                <div class="sidebar-section-header">
                    <h3 class="sidebar-section-title">استكشاف</h3>
                </div>
                @foreach($categories as $category)
                    <a href="{{ route('home', ['content_category' => $category->name]) }}"
                       class="sidebar-item {{ request('content_category') == $category->name ? 'active' : '' }}">
                        @if($category->image_path)
                            <img src="{{ asset('storage/' . $category->image_path) }}"
                                 alt="{{ $category->name }}"
                                 class="sidebar-category-image"
                                 style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px; margin-left: 8px;">
                        @else
                            <i class="bi bi-tag"></i>
                        @endif
                        <span class="sidebar-item-text">{{ $category->name }}</span>
                        <span class="sidebar-category-count">({{ $category->assets_count ?? 0 }})</span>
                    </a>
                @endforeach
            @endif

            <div class="sidebar-divider"></div>
            <div class="sidebar-section-header">
                <h3 class="sidebar-section-title">حسابي</h3>
            </div>
            @guest
                <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#authModal" data-bs-mode="login" onclick="if(window.closeSidebar) closeSidebar();">
                    <img src="{{ asset('images/profile-icon.png') }}" alt="تسجيل الدخول" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">تسجيل الدخول</span>
                </a>
            @endguest
            @auth
                <a href="{{ route('profile') }}" class="sidebar-item {{ request()->routeIs('profile') ? 'active' : '' }}">
                    <img src="{{ asset('images/profile-icon.png') }}" alt="الملف الشخصي" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">الملف الشخصي</span>
                </a>
                <a href="{{ route('favorites') }}" class="sidebar-item {{ request()->routeIs('favorites') ? 'active' : '' }}">
                    <img src="{{ asset('images/favorites-icon.png') }}" alt="المفضلة" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">المفضلة</span>
                </a>
                <a href="#" class="sidebar-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <img src="{{ asset('images/logout-icon.png') }}" alt="تسجيل الخروج" class="sidebar-item-icon-img" width="24" height="24">
                    <span class="sidebar-item-text">تسجيل الخروج</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            @endauth
            @include('partials.sidebar-social-links')
        </nav>
    </div>
</aside>
