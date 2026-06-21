@php
    $socialLinks = $socialLinks ?? \App\Support\SocialLinks::all();
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

<div class="sidebar-divider"></div>
<a href="{{ route('public.about') }}" class="sidebar-item {{ request()->routeIs('public.about') ? 'active' : '' }}">
    <img src="{{ asset('images/logo.png') }}" alt="من نحن" class="sidebar-item-icon-img" width="24" height="24">
    <span class="sidebar-item-text">من نحن</span>
</a>

@if($visibleSocialLinks->isNotEmpty())
    <div class="sidebar-divider"></div>
    <div class="sidebar-section-header">
        <h3 class="sidebar-section-title">تابعنا</h3>
    </div>
    <div class="sidebar-social-links" aria-label="روابط السوشيال ميديا">
        @foreach($visibleSocialLinks as $link)
            <a href="{{ $link['url'] }}"
               class="sidebar-social-link {{ $link['class'] }}"
               target="_blank"
               rel="noopener noreferrer"
               title="{{ $link['label'] }}"
               aria-label="{{ $link['label'] }}">
                <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
            </a>
        @endforeach
    </div>
@endif
