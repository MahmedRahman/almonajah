@extends('layouts.public')

@section('title', 'من نحن - المناجاة')
@section('meta_description', 'تعرف على منصة المناجاة الرقمية — مبادرة من اقرأ للمحتوى الإسلامي الموثوق من أدعية وابتهالات وفيديوهات وصوتيات.')

@php
    $socialLinks = \App\Support\SocialLinks::all();
    $socialPlatforms = [
        'youtube' => ['label' => 'YouTube', 'icon' => 'bi-youtube', 'class' => 'is-youtube'],
        'instagram' => ['label' => 'Instagram', 'icon' => 'bi-instagram', 'class' => 'is-instagram'],
        'facebook' => ['label' => 'Facebook', 'icon' => 'bi-facebook', 'class' => 'is-facebook'],
        'twitter' => ['label' => 'X', 'icon' => 'bi-twitter-x', 'class' => 'is-twitter'],
        'tiktok' => ['label' => 'TikTok', 'icon' => 'bi-tiktok', 'class' => 'is-tiktok'],
        'telegram' => ['label' => 'Telegram', 'icon' => 'bi-telegram', 'class' => 'is-telegram'],
        'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'bi-whatsapp', 'class' => 'is-whatsapp'],
        'linkedin' => ['label' => 'LinkedIn', 'icon' => 'bi-linkedin', 'class' => 'is-linkedin'],
    ];
    $visibleSocialLinks = collect($socialPlatforms)
        ->map(function ($meta, $key) use ($socialLinks) {
            $url = \App\Support\SocialLinks::url($key, $socialLinks[$key] ?? null);
            return $url ? array_merge($meta, ['url' => $url, 'key' => $key]) : null;
        })
        ->filter()
        ->values();
@endphp

@section('content')
<div class="home-layout">
    @include('partials.public-sidebar')

    <div class="main-content-wrapper">
        <div class="container-main">
            <div class="page-header">
                <h1 class="page-title">
                    <img src="{{ asset('images/logo_min.png') }}" alt="من نحن" class="page-title-icon" width="32" height="32">
                    من نحن
                </h1>
            </div>

            <article class="about-page">
                <section class="about-hero">
                    <img src="{{ asset('images/logo_min.png') }}" alt="المناجاة" class="about-hero__logo" width="120" height="120">
                    <div class="about-hero__body">
                        <h2 class="about-hero__name">منصة المناجاة الرقمية</h2>
                        <p class="about-hero__lead">
                            الخيار الإعلامي الرقمي للباحثين عن طمأنينة الروح، والمصدر الموثوق للأدعية والابتهالات
                            الواردة في القرآن الكريم وصحيح السنة النبوية.
                        </p>
                        <p class="about-hero__badge">
                            <i class="bi bi-patch-check-fill" aria-hidden="true"></i>
                            مبادرة من <strong>اقرأ @iqraa</strong>
                        </p>
                    </div>
                </section>

                <div class="about-cards">
                    <section class="about-card">
                        <div class="about-card__icon" aria-hidden="true">
                            <i class="bi bi-bullseye"></i>
                        </div>
                        <h3>رسالتنا</h3>
                        <p>
                            إتاحة محتوى إسلامي موثوق بجودة عالية عبر الفيديو والصوت، مع تنظيمه في قوائم تشغيل
                            وتصنيفات يسهل الوصول إليها من أي جهاز وفي أي وقت.
                        </p>
                    </section>

                    <section class="about-card">
                        <div class="about-card__icon" aria-hidden="true">
                            <i class="bi bi-collection-play"></i>
                        </div>
                        <h3>ماذا نقدّم؟</h3>
                        <ul class="about-card__list">
                            <li>فيديوهات ومحتوى مرئي منشور بعناية</li>
                            <li>منصة صوتية للاستماع إلى المحاضرات والابتهالات</li>
                            <li>قوائم تشغيل منظمة حسب البرامج والمواسم</li>
                            <li>تصنيفات متنوعة: رمضان، ابتهالات، بودكاست، وغيرها</li>
                        </ul>
                    </section>

                    <section class="about-card about-card--wide">
                        <div class="about-card__icon" aria-hidden="true">
                            <i class="bi bi-compass"></i>
                        </div>
                        <h3>استكشف المنصة</h3>
                        <div class="about-links">
                            <a href="{{ route('home') }}" class="about-link">
                                <img src="{{ asset('images/home-icon.png') }}" alt="" width="22" height="22">
                                <span>الرئيسية</span>
                            </a>
                            <a href="{{ route('audio.home') }}" class="about-link">
                                <img src="{{ asset('images/audio-icon.png') }}" alt="" width="22" height="22">
                                <span>المنصة الصوتية</span>
                            </a>
                            <a href="{{ route('shorts') }}" class="about-link">
                                <img src="{{ asset('images/shorts-icon.png') }}" alt="" width="22" height="22">
                                <span>فيديوهات قصيرة</span>
                            </a>
                            <a href="{{ route('public.playlists') }}" class="about-link">
                                <img src="{{ asset('images/playlists-icon.png') }}" alt="" width="22" height="22">
                                <span>قوائم التشغيل</span>
                            </a>
                            <a href="{{ route('live') }}" class="about-link">
                                <img src="{{ asset('images/live-icon.png') }}" alt="" width="22" height="22">
                                <span>بث مباشر</span>
                            </a>
                        </div>
                    </section>

                    @if($visibleSocialLinks->isNotEmpty())
                        <section class="about-card about-card--wide">
                            <div class="about-card__icon" aria-hidden="true">
                                <i class="bi bi-share"></i>
                            </div>
                            <h3>تابعنا</h3>
                            <p class="about-card__sub">تواصل معنا عبر منصات التواصل الاجتماعي الرسمية.</p>
                            <div class="about-social-links" aria-label="روابط السوشيال ميديا">
                                @foreach($visibleSocialLinks as $link)
                                    <a href="{{ $link['url'] }}"
                                       class="about-social-link {{ $link['class'] }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       title="{{ $link['label'] }}"
                                       aria-label="{{ $link['label'] }}">
                                        <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
                                        <span>{{ $link['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            </article>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.home-layout {
    display: flex;
    position: relative;
    min-height: calc(100vh - 200px);
    margin-top: 0;
}

.sidebar-menu {
    position: relative;
    width: 240px;
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

.sidebar-item-icon-img {
    width: 24px;
    height: 24px;
    object-fit: contain;
    flex-shrink: 0;
}

.sidebar-category-image {
    width: 24px;
    height: 24px;
    object-fit: cover;
    border-radius: 4px;
    flex-shrink: 0;
}

.sidebar-item-text {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-category-count {
    font-size: 0.8rem;
    color: var(--text-secondary, #6b7280);
    margin-right: 4px;
    flex-shrink: 0;
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

.main-content-wrapper {
    flex: 1;
    margin-right: 0;
    transition: margin-right 0.3s ease;
    width: 100%;
    min-width: 0;
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

.page-title-icon {
    width: 2rem;
    height: 2rem;
    object-fit: contain;
    flex-shrink: 0;
}

.about-page {
    max-width: 960px;
}

.about-hero {
    display: flex;
    align-items: center;
    gap: var(--spacing-lg);
    padding: var(--spacing-xl);
    margin-bottom: var(--spacing-lg);
    background: linear-gradient(135deg, rgba(24, 135, 129, 0.08) 0%, var(--bg-primary) 55%);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
}

.about-hero__logo {
    width: 120px;
    height: auto;
    flex-shrink: 0;
}

.about-hero__body {
    min-width: 0;
}

.about-hero__name {
    font-size: clamp(1.25rem, 3vw, 1.6rem);
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.65rem;
}

.about-hero__lead {
    font-size: 1.02rem;
    line-height: 1.85;
    color: var(--text-secondary);
    margin: 0 0 0.85rem;
}

.about-hero__badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    margin: 0;
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    background: rgba(24, 135, 129, 0.12);
    color: var(--primary-color);
    font-size: 0.9rem;
    font-weight: 600;
}

.about-hero__badge i {
    font-size: 1rem;
}

.about-cards {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--spacing-md);
}

.about-card {
    background-color: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
}

.about-card--wide {
    grid-column: 1 / -1;
}

.about-card__icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: var(--radius-sm);
    background: rgba(24, 135, 129, 0.12);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-bottom: 0.75rem;
}

.about-card h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.6rem;
}

.about-card p,
.about-card__sub {
    line-height: 1.85;
    color: var(--text-secondary);
    margin: 0;
}

.about-card__sub {
    margin-bottom: 0.85rem;
}

.about-card__list {
    margin: 0;
    padding-right: 1.15rem;
    line-height: 1.9;
    color: var(--text-primary);
}

.about-card__list li {
    margin-bottom: 0.35rem;
}

.about-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
}

.about-link {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.55rem 0.85rem;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
    background: var(--bg-secondary, var(--bg-tertiary));
    color: var(--text-primary);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.about-link:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
    background: rgba(24, 135, 129, 0.08);
}

.about-link img {
    flex-shrink: 0;
}

.about-social-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
}

.about-social-link {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.55rem 0.9rem;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
    background: var(--bg-secondary, var(--bg-tertiary));
    color: var(--text-primary);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.about-social-link:hover {
    color: #fff;
    border-color: transparent;
}

.about-social-link.is-youtube:hover { background: #ff0000; }
.about-social-link.is-instagram:hover { background: #e4405f; }
.about-social-link.is-facebook:hover { background: #1877f2; }
.about-social-link.is-twitter:hover { background: #000; }
.about-social-link.is-tiktok:hover { background: #010101; }
.about-social-link.is-telegram:hover { background: #229ed9; }
.about-social-link.is-whatsapp:hover { background: #25d366; }
.about-social-link.is-linkedin:hover { background: #0a66c2; }

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
        width: 240px;
        opacity: 1;
    }

    .sidebar-menu.collapsed {
        transform: translateX(100%);
        width: 240px;
    }
}

@media (max-width: 768px) {
    .about-hero {
        flex-direction: column;
        text-align: center;
        padding: var(--spacing-lg);
    }

    .about-hero__badge {
        justify-content: center;
    }

    .about-cards {
        grid-template-columns: 1fr;
    }

    .sidebar-menu {
        top: 56px;
        height: calc(100vh - 56px);
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
</style>
@endpush
