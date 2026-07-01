@php
    $visibleSocialLinks = \App\Support\SocialLinks::visible();
@endphp

<div class="sidebar-divider"></div>
<a href="{{ route('public.about') }}" class="sidebar-item {{ request()->routeIs('public.about') ? 'active' : '' }}">
    <img src="{{ asset('images/logo_min.png') }}" alt="من نحن" class="sidebar-item-icon-img" width="24" height="24">
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
                @include('partials.social-link-icon', [
                    'icon' => $link['icon'] ?? null,
                    'customIcon' => $link['custom_icon'] ?? null,
                ])
            </a>
        @endforeach
    </div>
@endif
