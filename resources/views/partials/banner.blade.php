@php
    $url = $banner->link ?: '#';
    $imgUrl = $banner->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($banner->image_path)
        ? asset('storage/' . $banner->image_path)
        : null;
    $sizeClass = 'banner-size--' . $banner->size;
@endphp
@if($imgUrl)
<a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="banner-link banner-wrap {{ $sizeClass }}" @if($url !== '#') title="إعلان" @endif>
    <img src="{{ $imgUrl }}" alt="إعلان" class="banner-img" loading="lazy">
</a>
@endif
