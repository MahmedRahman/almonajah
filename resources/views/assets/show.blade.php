@extends('layouts.app')

@section('title', $asset->file_name)

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">{{ \Illuminate\Support\Str::limit($asset->file_name, 60) }}</h2>
    <div>
        <a href="{{ route('assets.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-right me-1"></i>رجوع
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">معلومات الملف</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="200">ID:</th>
                        <td>
                            <a href="{{ route('assets.show.public', $asset) }}" 
                               target="_blank"
                               rel="noopener noreferrer"
                               class="badge bg-secondary fs-6 text-decoration-none" 
                               title="فتح رابط الفيديو في تاب جديد">
                                {{ $asset->id }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th width="200">اسم الملف:</th>
                        <td>{{ $asset->file_name }}</td>
                    </tr>
                    <tr>
                        <th>العنوان:</th>
                        <td>
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    @if($asset->title)
                                        <strong class="fs-5" id="titleText">{{ $asset->title }}</strong>
                                    @else
                                        <span class="text-muted" id="titleText">غير محدد</span>
                                    @endif
                                    <input type="text" class="form-control d-none" id="titleInput" value="{{ $asset->title ?? '' }}" style="max-width: 500px;">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="editTitleBtn" onclick="toggleEditTitle()">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>
                            <div class="mt-2 d-none" id="titleActions">
                                <button type="button" class="btn btn-sm btn-success" onclick="saveTitle({{ $asset->id }})">
                                    <i class="bi bi-check me-1"></i>حفظ
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditTitle()">
                                    <i class="bi bi-x me-1"></i>إلغاء
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>وصف الموقع:</th>
                        <td>
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    @if($asset->site_description)
                                        <p class="mb-0" id="siteDescriptionText">{{ $asset->site_description }}</p>
                                    @else
                                        <span class="text-muted" id="siteDescriptionText">غير محدد</span>
                                    @endif
                                    <textarea class="form-control d-none" id="siteDescriptionTextarea" rows="3">{{ $asset->site_description ?? '' }}</textarea>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="editSiteDescriptionBtn" onclick="toggleEditSiteDescription()">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>
                            <div class="mt-2 d-none" id="siteDescriptionActions">
                                <button type="button" class="btn btn-sm btn-success" onclick="saveSiteDescription({{ $asset->id }})">
                                    <i class="bi bi-check me-1"></i>حفظ
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditSiteDescription()">
                                    <i class="bi bi-x me-1"></i>إلغاء
                                </button>
                            </div>
                        </td>
                    </tr>
                    @if($asset->original_path)
                    <tr>
                        <th>المسار النسبي (الأصلي):</th>
                        <td><code>{{ $asset->original_path }}</code></td>
                    </tr>
                    @endif
                    @if($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0)
                    <tr>
                        <th>المسار الفعلي:</th>
                        <td>
                            <code>{{ $asset->relative_path }}</code>
                            <br>
                            <small class="text-muted">
                                <a href="{{ asset('storage/' . $asset->relative_path) }}" target="_blank" class="text-decoration-none">
                                    {{ asset('storage/' . $asset->relative_path) }}
                                </a>
                            </small>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <th>اسم المتحدث (الشيخ):</th>
                        <td>
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div id="speakerBadge">
                                        @php
                                            $speakerDisplay = $asset->scholar?->name ?? $asset->speaker_name;
                                        @endphp
                                        @if($speakerDisplay)
                                            <span class="badge bg-primary fs-6 px-3 py-2">{{ $speakerDisplay }}</span>
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </div>
                                    <div id="speakerSelectWrap" class="d-none mt-2">
                                        <select class="form-select form-select-sm" id="speakerSelect" style="max-width: 280px;">
                                            <option value="">— غير محدد —</option>
                                            @foreach($scholars as $scholar)
                                                <option value="{{ $scholar->id }}" {{ ($asset->scholar_id == $scholar->id) ? 'selected' : '' }}>{{ $scholar->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-success ms-2" id="editSpeakerBtn" onclick="toggleEditSpeaker()">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>
                            <div class="mt-2 d-none" id="speakerActions">
                                <button type="button" class="btn btn-sm btn-success" onclick="saveSpeaker({{ $asset->id }})">
                                    <i class="bi bi-check me-1"></i>حفظ
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditSpeaker()">
                                    <i class="bi bi-x me-1"></i>إلغاء
                                </button>
                            </div>
                        </td>
                    </tr>
                            <tr>
                                <th>تصنيفات المحتوى:</th>
                                <td>
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <!-- عرض التصنيفات المحددة -->
                                            <div id="contentCategoryBadge">
                                                @php
                                                    $assetCategories = $asset->categories;
                                                @endphp
                                                @if($assetCategories && $assetCategories->count() > 0)
                                                    @foreach($assetCategories as $cat)
                                                        <span class="badge bg-success me-1 mb-1">{{ $cat->name }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </div>
                                            <!-- كروت التصنيفات (مخفية في البداية) -->
                                            <div id="contentCategoryCards" class="d-none mt-2">
                                                <div class="row g-2" id="categoryCardsContainer">
                                                    @php
                                                        $allCategories = \App\Models\Category::orderBy('name')->get();
                                                        $selectedCategoryIds = $asset->categories->pluck('id')->toArray();
                                                    @endphp
                                                    @foreach($allCategories as $category)
                                                        <div class="col-auto">
                                                            <div class="category-card-selectable {{ in_array($category->id, $selectedCategoryIds) ? 'selected' : '' }}" 
                                                                 data-category-id="{{ $category->id }}"
                                                                 onclick="toggleCategoryCard(this)">
                                                                @if($category->image_path)
                                                                    <img src="{{ asset('storage/' . $category->image_path) }}" 
                                                                         alt="{{ $category->name }}" 
                                                                         class="category-card-image">
                                                                @else
                                                                    <div class="category-card-icon">
                                                                        <i class="bi bi-tag"></i>
                                                                    </div>
                                                                @endif
                                                                <div class="category-card-name">{{ $category->name }}</div>
                                                                <div class="category-card-check">
                                                                    <i class="bi bi-check-circle-fill"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-success ms-2" id="editContentCategoryBtn" onclick="toggleEditContentCategory()">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </div>
                                    <div class="mt-2 d-none" id="contentCategoryActions">
                                        <button type="button" class="btn btn-sm btn-success" onclick="saveContentCategory({{ $asset->id }})">
                                            <i class="bi bi-check me-1"></i>حفظ
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditContentCategory()">
                                            <i class="bi bi-x me-1"></i>إلغاء
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>قوائم التشغيل:</th>
                                <td>
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div id="playlistBadge">
                                                @php
                                                    $assetPlaylists = $asset->playlists;
                                                @endphp
                                                @if($assetPlaylists && $assetPlaylists->count() > 0)
                                                    @foreach($assetPlaylists as $pl)
                                                        <span class="badge bg-primary me-1 mb-1">{{ $pl->title }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">غير مضاف لأي قائمة</span>
                                                @endif
                                            </div>
                                            <div id="playlistCards" class="d-none mt-2">
                                                <div class="row g-2" id="playlistCardsContainer">
                                                    @php
                                                        $allPlaylists = \App\Models\Playlist::orderBy('title')->get();
                                                        $selectedPlaylistIds = $asset->playlists->pluck('id')->toArray();
                                                    @endphp
                                                    @foreach($allPlaylists as $playlist)
                                                        <div class="col-auto">
                                                            <div class="playlist-card-selectable {{ in_array($playlist->id, $selectedPlaylistIds) ? 'selected' : '' }}"
                                                                 data-playlist-id="{{ $playlist->id }}"
                                                                 onclick="togglePlaylistCard(this)">
                                                                @if($playlist->image_path)
                                                                    <img src="{{ asset('storage/' . $playlist->image_path) }}"
                                                                         alt="{{ $playlist->title }}"
                                                                         class="playlist-card-image">
                                                                @else
                                                                    <div class="playlist-card-icon">
                                                                        <i class="bi bi-collection-play"></i>
                                                                    </div>
                                                                @endif
                                                                <div class="playlist-card-title">{{ $playlist->title }}</div>
                                                                <div class="playlist-card-check">
                                                                    <i class="bi bi-check-circle-fill"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="editPlaylistBtn" onclick="toggleEditPlaylists()">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </div>
                                    <div class="mt-2 d-none" id="playlistActions">
                                        <button type="button" class="btn btn-sm btn-success" onclick="savePlaylists({{ $asset->id }})">
                                            <i class="bi bi-check me-1"></i>حفظ
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditPlaylists()">
                                            <i class="bi bi-x me-1"></i>إلغاء
                                        </button>
                                    </div>
                                </td>
                            </tr>
                    {{-- @if($asset->year)
                    <tr>
                        <th>السنة الهجرية:</th>
                        <td>
                            <span class="badge bg-warning text-dark">{{ $asset->year }}</span>
                        </td>
                    </tr>
                    @endif --}}
                    <tr>
                        <th>السنة الميلادية:</th>
                        <td>
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <span id="gregorianYearText">
                                        @if($asset->gregorian_year)
                                            <span class="badge bg-success">{{ $asset->gregorian_year }}</span>
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </span>
                                    <input type="number" class="form-control form-control-sm d-none" id="gregorianYearInput" value="{{ $asset->gregorian_year ?? '' }}" min="1900" max="2100" step="1" placeholder="مثال: 2025" style="max-width: 120px;">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="editGregorianYearBtn" onclick="toggleEditGregorianYear()">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>
                            <div class="mt-2 d-none" id="gregorianYearActions">
                                <button type="button" class="btn btn-sm btn-success" onclick="saveGregorianYear({{ $asset->id }})">
                                    <i class="bi bi-check me-1"></i>حفظ
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditGregorianYear()">
                                    <i class="bi bi-x me-1"></i>إلغاء
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>تاريخ الإنتاج:</th>
                        <td>
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <span id="productionDateText">
                                        @if($asset->production_date)
                                            <span class="badge bg-info">{{ $asset->production_date->format('d/m/Y') }}</span>
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </span>
                                    <input type="date" class="form-control form-control-sm d-none" id="productionDateInput" value="{{ $asset->production_date?->format('Y-m-d') ?? '' }}" style="max-width: 160px;">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="editProductionDateBtn" onclick="toggleEditProductionDate()">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>
                            <div class="mt-2 d-none" id="productionDateActions">
                                <button type="button" class="btn btn-sm btn-success" onclick="saveProductionDate({{ $asset->id }})">
                                    <i class="bi bi-check me-1"></i>حفظ
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditProductionDate()">
                                    <i class="bi bi-x me-1"></i>إلغاء
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>الامتداد:</th>
                        <td><span class="badge bg-secondary">{{ strtoupper($asset->extension) }}</span></td>
                    </tr>
                    <tr>
                        <th>الحجم:</th>
                        <td>
                            @if($asset->size_bytes)
                                @php
                                    $sizeBytes = $asset->size_bytes;
                                    if ($sizeBytes >= 1073741824) {
                                        // GB
                                        $size = round($sizeBytes / 1073741824, 2);
                                        $unit = 'GB';
                                    } elseif ($sizeBytes >= 1048576) {
                                        // MB
                                        $size = round($sizeBytes / 1048576, 2);
                                        $unit = 'MB';
                                    } elseif ($sizeBytes >= 1024) {
                                        // KB
                                        $size = round($sizeBytes / 1024, 2);
                                        $unit = 'KB';
                                    } else {
                                        // Bytes
                                        $size = $sizeBytes;
                                        $unit = 'بايت';
                                    }
                                @endphp
                                <strong class="text-primary">{{ number_format($size, $unit === 'بايت' ? 0 : 2) }} {{ $unit }}</strong>
                                <small class="text-muted ms-2">({{ number_format($sizeBytes) }} بايت)</small>
                            @else
                                <span class="text-muted">غير متوفر</span>
                            @endif
                        </td>
                    </tr>
                    @if($asset->width && $asset->height)
                    <tr>
                        <th>الأبعاد:</th>
                        <td>{{ $asset->width }} × {{ $asset->height }} بكسل</td>
                    </tr>
                    @endif
                    @if($asset->duration_seconds)
                    <tr>
                        <th>المدة:</th>
                        <td>{{ $asset->duration_formatted }}</td>
                    </tr>
                    @endif
                    @if($asset->orientation)
                    <tr>
                        <th>الاتجاه:</th>
                        <td>
                            @if($asset->orientation == 'portrait')
                                <span class="badge bg-info">عمودي</span>
                            @elseif($asset->orientation == 'landscape')
                                <span class="badge bg-success">أفقي</span>
                            @else
                                <span class="badge bg-secondary">مربع</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                    @if($asset->aspect_ratio)
                    <tr>
                        <th>نسبة العرض:</th>
                        <td>{{ $asset->aspect_ratio }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th>تاريخ التعديل:</th>
                        <td>
                            @if($asset->modified_at)
                                @if(is_string($asset->modified_at))
                                    {{ \Carbon\Carbon::parse($asset->modified_at)->format('Y-m-d H:i:s') }}
                                @else
                                    {{ $asset->modified_at->format('Y-m-d H:i:s') }}
                                @endif
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @if($asset->sha256)
                    <tr>
                        <th>SHA256:</th>
                        <td><code class="small">{{ $asset->sha256 }}</code></td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">النشر</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" id="editPublishUrlsBtn" onclick="toggleEditPublishUrls()">
                    <i class="bi bi-pencil"></i> تعديل
                </button>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="200">رابط نشر اليوتيوب:</th>
                        <td>
                            <span id="youtubePublishUrlText">
                                @if($asset->youtube_publish_url)
                                    <a href="{{ $asset->youtube_publish_url }}" target="_blank" rel="noopener noreferrer">{{ $asset->youtube_publish_url }}</a>
                                @else
                                    <span class="text-muted">غير محدد</span>
                                @endif
                            </span>
                            <input type="url" class="form-control d-none" id="youtubePublishUrlInput" value="{{ $asset->youtube_publish_url ?? '' }}" data-original="{{ $asset->youtube_publish_url ?? '' }}" placeholder="https://youtube.com/...">
                        </td>
                    </tr>
                    <tr>
                        <th width="200">رابط نشر الساوند كلاود:</th>
                        <td>
                            <span id="soundcloudPublishUrlText">
                                @if($asset->soundcloud_publish_url)
                                    <a href="{{ $asset->soundcloud_publish_url }}" target="_blank" rel="noopener noreferrer">{{ $asset->soundcloud_publish_url }}</a>
                                @else
                                    <span class="text-muted">غير محدد</span>
                                @endif
                            </span>
                            <input type="url" class="form-control d-none" id="soundcloudPublishUrlInput" value="{{ $asset->soundcloud_publish_url ?? '' }}" data-original="{{ $asset->soundcloud_publish_url ?? '' }}" placeholder="https://soundcloud.com/...">
                        </td>
                    </tr>
                    <tr>
                        <th width="200">رابط الموقع:</th>
                        <td>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" id="siteVideoUrlInput" value="{{ url(route('assets.show.public', $asset)) }}" readonly>
                                <button type="button" class="btn btn-outline-secondary" onclick="copySiteVideoUrl()" title="نسخ الرابط">
                                    <i class="bi bi-clipboard"></i> نسخ
                                </button>
                            </div>
                        </td>
                    </tr>
                </table>
                <div class="d-none mt-2" id="publishUrlsActions">
                    <button type="button" class="btn btn-sm btn-success" onclick="savePublishUrls({{ $asset->id }})">
                        <i class="bi bi-check me-1"></i>حفظ
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditPublishUrls()">
                        <i class="bi bi-x me-1"></i>إلغاء
                    </button>
                </div>
            </div>
        </div>

        @php
            // التحقق من وجود الملف في storage (يجب تعريفه قبل استخدامه)
            $fileUrl = null;
            $streamUrl = null;
            $fileInStorage = false;
            if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
                // الملف موجود في storage
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($asset->relative_path)) {
                    $fileUrl = asset('storage/' . $asset->relative_path);
                    $streamUrl = route('assets.stream', $asset);
                    $fileInStorage = true;
                }
            }
        @endphp

        @if(!$fileInStorage && $asset->relative_path)
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>تنبيه: الفيديو لم يتم نقله إلى الموقع
                </h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>لا يمكن استخراج المحتوى النصي</strong> لأن الفيديو لم يتم نقله إلى الموقع بعد.
                </p>
                <p class="mb-0 text-muted small">
                    يرجى استخدام زر "نقل المحتوى" أولاً لنقل الفيديو إلى الموقع، ثم يمكنك استخراج المحتوى النصي.
                </p>
            </div>
        </div>
        @endif

        @if($fileUrl && $asset->transcription)
        <!-- الفيديو والمحتوى النصي بجانب بعضهما -->
        <div class="row mb-4">
            <!-- الفيديو -->
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">معاينة الملف</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openPreviewFullscreen()" title="شاشة منفصلة: معاينة الملف + المحتوى النصي">
                            <i class="bi bi-fullscreen me-1"></i>تكبير
                        </button>
                    </div>
                    <div class="card-body">
                        @if(in_array(strtolower($asset->extension), ['mp4', 'mov', 'mkv', 'm4v', 'webm', 'avi']))
                            @if(isset($transcriptionSegments) && $transcriptionSegments)
                            <div class="mb-2 d-flex flex-wrap align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleCaptionOverlayBtn" onclick="toggleCaptionOverlay()" title="إظهار/إخفاء النص فوق الفيديو">
                                    <i class="bi bi-subtitles"></i> إظهار النص فوق الفيديو
                                </button>
                                <span class="text-muted small">أو اذهب للتوقيت:</span>
                                <div class="input-group input-group-sm" style="width: auto;">
                                    <input type="text" id="seekTimeInput" class="form-control" placeholder="د:ث مثل 2:30" style="max-width: 90px; direction: ltr; text-align: left;" onkeydown="if(event.key==='Enter'){ event.preventDefault(); seekToTimeFromInput(); }">
                                    <button type="button" class="btn btn-outline-primary" onclick="seekToTimeFromInput()" title="انتقال الفيديو لهذا التوقيت">
                                        <i class="bi bi-play-fill"></i> اذهب
                                    </button>
                                </div>
                            </div>
                            @endif
                            <div class="position-relative" style="max-height: 500px; background: #000;">
                                <video 
                                    id="videoPlayer" 
                                    controls 
                                    preload="metadata"
                                    class="w-100" 
                                    style="max-height: 500px; display: block;"
                                    @if(isset($transcriptionSegments) && $transcriptionSegments) ontimeupdate="updateTranscriptionHighlight()" @endif>
                                    <source src="{{ $streamUrl ?? $fileUrl }}" type="video/{{ $asset->extension }}">
                                    متصفحك لا يدعم تشغيل الفيديو.
                                </video>
                                @if(isset($transcriptionSegments) && $transcriptionSegments)
                                <div id="captionOverlay" class="position-absolute start-0 end-0 bottom-0 p-3 d-none" style="background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.5) 60%, transparent 100%); pointer-events: none;">
                                    <p id="captionOverlayText" class="mb-0 text-white text-center fw-bold" style="font-size: 1.1rem; text-shadow: 1px 1px 2px rgba(0,0,0,0.8); direction: rtl;"></p>
                                </div>
                                @endif
                            </div>
                        @elseif(in_array(strtolower($asset->extension), ['mp3', 'wav', 'ogg', 'm4a', 'aac']))
                            <audio controls class="w-100">
                                <source src="{{ $streamUrl ?? $fileUrl }}" type="audio/{{ $asset->extension }}">
                                متصفحك لا يدعم تشغيل الصوت.
                            </audio>
                        @elseif(in_array(strtolower($asset->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                            <img src="{{ $fileUrl }}" alt="{{ $asset->file_name }}" class="img-fluid" style="max-height: 500px;">
                        @else
                            <div class="alert alert-info">
                                <i class="bi bi-file-earmark me-2"></i>
                                <a href="{{ $fileUrl }}" target="_blank" class="btn btn-primary">
                                    <i class="bi bi-download me-1"></i>تحميل الملف
                                </a>
                            </div>
                        @endif
                        <div class="mt-3">
                            <small class="text-muted">رابط الملف:</small>
                            <div class="input-group mt-1">
                                <input type="text" class="form-control" value="{{ $fileUrl }}" id="fileUrlInput" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyFileUrl()">
                                    <i class="bi bi-clipboard"></i> نسخ
                                </button>
                                <a href="{{ $fileUrl }}" download class="btn btn-primary" type="button">
                                    <i class="bi bi-download"></i> تحميل
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- المحتوى النصي -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0">المحتوى النصي</h5>
                        <div class="d-flex gap-1 flex-wrap align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="downloadTranscriptionText()" title="تحميل المحتوى النصي (صيغة SBV عند توفر التوقيت، وإلا TXT)">
                                <i class="bi bi-download me-1"></i>تحميل
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="document.getElementById('srtFileInput').click()" title="رفع ملف SRT واستبدال المحتوى والتوقيت">
                                <i class="bi bi-upload me-1"></i>رفع
                            </button>
                            <input type="file" id="srtFileInput" accept=".srt,.txt" class="d-none" name="srt_file">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="editTranscriptionBtn" onclick="toggleEditTranscription()">
                                <i class="bi bi-pencil me-1"></i>تعديل
                            </button>
                            @if(isset($translationLanguages) && ($asset->transcription || (isset($transcriptionSegments) && $transcriptionSegments)))
                            <a href="{{ url('/video/' . $asset->id . '/download-transcription-all') }}" class="btn btn-sm btn-outline-info" download title="تنزيل ملف ZIP يحتوي على العربي وكل اللغات المترجمة">
                                <i class="bi bi-file-zip me-1"></i>تحميل كل اللغات (ZIP)
                            </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="bg-light p-3 rounded flex-grow-1" id="transcriptionContainer" style="max-height: 500px; overflow-y: auto; text-align: right; direction: rtl;">
                            <!-- محتوى العربية (يُخفى عند اختيار لغة أخرى) -->
                            <div id="adminTranscriptionContentAr" class="admin-transcription-lang-content" data-lang="ar">
                            @if(isset($transcriptionSegments) && $transcriptionSegments && $fileUrl)
                                <div id="transcriptionSegmentsView">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th style="width: 140px;">التوقيت</th>
                                                    <th>الجملة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($transcriptionSegments as $index => $segment)
                                                @php
                                                    $start = $segment['start'] ?? 0;
                                                    $end = $segment['end'] ?? 0;
                                                    $mins = floor($start / 60);
                                                    $secs = floor($start % 60);
                                                    $startFmt = sprintf('%d:%02d', $mins, $secs);
                                                    $minsE = floor($end / 60);
                                                    $secsE = floor($end % 60);
                                                    $endFmt = sprintf('%d:%02d', $minsE, $secsE);
                                                @endphp
                                                <tr class="transcription-segment-row" data-start="{{ $start }}" data-index="{{ $index }}" style="cursor: pointer;" onclick="event.preventDefault(); event.stopPropagation(); seekToTime(this.getAttribute('data-start')); return false;" title="انقر للانتقال لهذا الموضع في الفيديو">
                                                    <td class="text-nowrap align-top" style="vertical-align: top;">
                                                        <span class="text-muted small">{{ $startFmt }} – {{ $endFmt }}</span>
                                                    </td>
                                                    <td>
                                                        <span 
                                                            class="transcription-segment" 
                                                            data-start="{{ $start }}" 
                                                            data-end="{{ $end }}"
                                                            data-index="{{ $index }}"
                                                            style="transition: background-color 0.3s;">
                                                            {{ trim($segment['text'] ?? '') }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <!-- وضع التعديل: كل جملة في input -->
                                <div id="transcriptionSegmentsEdit" class="d-none">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 140px;">التوقيت</th>
                                                    <th>الجملة</th>
                                                </tr>
                                            </thead>
                                            <tbody id="transcriptionSegmentsEditBody">
                                                @foreach($transcriptionSegments as $index => $segment)
                                                @php
                                                    $start = $segment['start'] ?? 0;
                                                    $end = $segment['end'] ?? 0;
                                                    $mins = floor($start / 60);
                                                    $secs = floor($start % 60);
                                                    $startFmt = sprintf('%d:%02d', $mins, $secs);
                                                    $minsE = floor($end / 60);
                                                    $secsE = floor($end % 60);
                                                    $endFmt = sprintf('%d:%02d', $minsE, $secsE);
                                                @endphp
                                                <tr data-index="{{ $index }}" data-start="{{ $start }}" data-end="{{ $end }}">
                                                    <td class="text-nowrap align-top text-muted small">{{ $startFmt }} – {{ $endFmt }}</td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm segment-text-input" value="{{ trim($segment['text'] ?? '') }}" data-index="{{ $index }}" style="direction: rtl; text-align: right;">
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <p class="mb-0" id="transcriptionTextView" style="white-space: pre-wrap; text-align: right; direction: rtl;">{{ $asset->transcription }}</p>
                            @endif
                            <textarea class="form-control d-none" id="transcriptionTextarea" rows="15" style="text-align: right; direction: rtl; font-family: 'Courier New', monospace; font-size: 13px;">{{ $asset->transcription }}</textarea>
                            </div>
                            <!-- محتوى اللغات المترجمة -->
                            @foreach($translationLanguages as $code => $name)
                            @php $langSegs = ($asset->translation_segments ?? [])[$code] ?? []; @endphp
                            @if(!empty($langSegs))
                            <div id="adminTranscriptionContent{{ $code }}" class="admin-transcription-lang-content d-none" data-lang="{{ $code }}" style="text-align: left; direction: ltr;">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th style="width: 140px;">التوقيت</th>
                                                <th>الجملة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($langSegs as $seg)
                                            @php
                                                $start = $seg['start'] ?? 0;
                                                $end = $seg['end'] ?? 0;
                                                $startFmt = sprintf('%d:%02d:%02d', floor($start/3600), floor(fmod($start,3600)/60), floor($start%60));
                                                $endFmt = sprintf('%d:%02d:%02d', floor($end/3600), floor(fmod($end,3600)/60), floor($end%60));
                                            @endphp
                                            <tr>
                                                <td class="text-nowrap align-top text-muted small">{{ $startFmt }} – {{ $endFmt }}</td>
                                                <td>{{ trim($seg['text'] ?? '') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                        @if(isset($translationLanguages) && ($asset->transcription || (isset($transcriptionSegments) && $transcriptionSegments)))
                        <div class="mt-2 pt-2 border-top">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <span class="text-muted small">ترجمة:</span>
                                <div class="d-flex flex-wrap gap-1" id="adminTranscriptionLangTabs">
                                    <button type="button" class="btn btn-sm btn-outline-secondary admin-lang-tab active" data-lang="ar" onclick="adminSetTranscriptionLang('ar')">العربية</button>
                                    @foreach($translationLanguages as $code => $name)
                                    @if(!empty(($asset->translation_segments ?? [])[$code]))
                                    <button type="button" class="btn btn-sm btn-outline-secondary admin-lang-tab" data-lang="{{ $code }}" onclick="adminSetTranscriptionLang('{{ $code }}')">{{ $name }}</button>
                                    @endif
                                    @endforeach
                                </div>
                                @foreach($translationLanguages as $code => $name)
                                @if(empty(($asset->translation_segments ?? [])[$code]))
                                <button type="button" class="btn btn-sm btn-outline-primary admin-translate-btn" data-lang="{{ $code }}" data-name="{{ $name }}" onclick="adminTranslateTranscription({{ $asset->id }}, this)">ترجمة إلى {{ $name }}</button>
                                @endif
                                @endforeach
                                <button type="button" class="btn btn-sm btn-primary admin-translate-all-btn" onclick="adminTranslateAllLanguages()"><i class="bi bi-translate me-1"></i>ترجمة جميع اللغات</button>
                            </div>
                            <div id="adminTranscriptionTranslateLoading" class="d-none small text-muted">جاري الترجمة...</div>
                        </div>
                        <div id="translateLoadingModal" class="translate-loading-modal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
                            <div style="background: #fff; padding: 2rem; border-radius: 12px; text-align: center; min-width: 260px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
                                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">جاري التحميل...</span>
                                </div>
                                <p class="mb-0 fw-medium" id="adminTranslateLoadingModalTitle">جاري الترجمة...</p>
                                <p class="small text-muted mt-1 mb-0" id="adminTranslateLoadingModalSubtitle">قد يستغرق ذلك دقيقة</p>
                            </div>
                        </div>
                        @endif
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                عدد الأحرف: <span id="transcriptionCharCount">{{ number_format(strlen($asset->transcription)) }}</span>
                                @if(isset($transcriptionSegments) && $transcriptionSegments && $fileUrl)
                                    <span class="badge bg-info ms-2">مزامنة نشطة</span>
                                @endif
                            </div>
                            <div class="d-none" id="transcriptionActions">
                                <button type="button" class="btn btn-sm btn-success" onclick="saveTranscription({{ $asset->id }})">
                                    <i class="bi bi-check me-1"></i>حفظ
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditTranscription()">
                                    <i class="bi bi-x me-1"></i>إلغاء
                                </button>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="card border-secondary bg-light">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="form-check form-switch mb-0 flex-shrink-0">
                                            <input class="form-check-input" type="checkbox" id="showTranslationCheck" style="width: 2.5rem; height: 1.25rem;" {{ ($asset->show_translation ?? true) ? 'checked' : '' }} onchange="saveShowTranslation({{ $asset->id }}, this.checked)">
                                        </div>
                                        <div>
                                            <label class="form-check-label fw-semibold mb-1 d-block" for="showTranslationCheck">إظهار الترجمة على صفحة الفيديو العامة</label>
                                            <small class="text-muted">عند التفعيل يظهر للزائر شريط لغة الترجمة والإعدادات ونمط الترجمة أسفل الفيديو.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="card border-secondary bg-light">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="form-check form-switch mb-0 flex-shrink-0">
                                            <input class="form-check-input" type="checkbox" id="showCommentsCheck" style="width: 2.5rem; height: 1.25rem;" {{ ($asset->show_comments ?? true) ? 'checked' : '' }} onchange="saveShowComments({{ $asset->id }}, this.checked)">
                                        </div>
                                        <div>
                                            <label class="form-check-label fw-semibold mb-1 d-block" for="showCommentsCheck">إظهار التعليقات على صفحة الفيديو العامة</label>
                                            <small class="text-muted">عند التفعيل يظهر للزائر قسم التعليقات وإمكانية إضافة تعليق.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- شاشة منفصلة: معاينة الملف + المحتوى النصي (تكبير) -->
        <div class="modal fade" id="previewFullscreenModal" tabindex="-1" aria-labelledby="previewFullscreenModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header bg-white">
                        <h5 class="modal-title" id="previewFullscreenModalLabel">معاينة الملف + المحتوى النصي</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body p-0 d-flex flex-column">
                        <div class="row g-0 flex-grow-1 overflow-hidden">
                            <div class="col-md-6 d-flex flex-column border-end" style="min-height: 50vh;">
                                <div class="p-2 bg-light border-bottom">
                                    <strong>معاينة الملف</strong>
                                </div>
                                <div class="flex-grow-1 p-2 d-flex align-items-center justify-content-center bg-dark">
                                    @if(in_array(strtolower($asset->extension), ['mp4', 'mov', 'mkv', 'm4v', 'webm', 'avi']))
                                    <video id="videoPlayerFullscreen" controls class="w-100" style="max-height: calc(100vh - 120px);">
                                        <source src="{{ $streamUrl ?? $fileUrl }}" type="video/{{ $asset->extension }}">
                                        متصفحك لا يدعم تشغيل الفيديو.
                                    </video>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 d-flex flex-column" style="min-height: 50vh;">
                                <div class="p-2 bg-light border-bottom d-flex justify-content-between align-items-center">
                                    <strong>المحتوى النصي</strong>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="downloadTranscriptionText()">
                                        <i class="bi bi-download me-1"></i>تحميل
                                    </button>
                                </div>
                                <div id="transcriptionFullscreenContent" class="flex-grow-1 overflow-auto p-3" style="max-height: calc(100vh - 120px); text-align: right; direction: rtl; background: #f8f9fa;">
                                    @if(isset($transcriptionSegments) && $transcriptionSegments && $fileUrl)
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th style="width: 140px;">التوقيت</th>
                                                    <th>الجملة</th>
                                                </tr>
                                            </thead>
                                            <tbody id="transcriptionFullscreenBody">
                                                @foreach($transcriptionSegments as $index => $segment)
                                                @php
                                                    $start = $segment['start'] ?? 0;
                                                    $end = $segment['end'] ?? 0;
                                                    $mins = floor($start / 60);
                                                    $secs = floor($start % 60);
                                                    $startFmt = sprintf('%d:%02d', $mins, $secs);
                                                    $minsE = floor($end / 60);
                                                    $secsE = floor($end % 60);
                                                    $endFmt = sprintf('%d:%02d', $minsE, $secsE);
                                                @endphp
                                                <tr class="transcription-segment-row-fullscreen" data-start="{{ $start }}" data-end="{{ $end }}" data-index="{{ $index }}" style="cursor: pointer; transition: background-color 0.3s;">
                                                    <td class="text-nowrap align-top"><span class="text-muted small">{{ $startFmt }} – {{ $endFmt }}</span></td>
                                                    <td><span class="transcription-segment-fullscreen">{{ trim($segment['text'] ?? '') }}</span></td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @else
                                    <p class="mb-0" style="white-space: pre-wrap;">{{ $asset->transcription }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @elseif($fileUrl)
        <!-- الفيديو فقط (بدون محتوى نصي) -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">معاينة الملف</h5>
            </div>
            <div class="card-body">
                @if(in_array(strtolower($asset->extension), ['mp4', 'mov', 'mkv', 'm4v', 'webm', 'avi']))
                    <video 
                        id="videoPlayer" 
                        controls 
                        class="w-100" 
                        style="max-height: 500px;">
                        <source src="{{ $streamUrl ?? $fileUrl }}" type="video/{{ $asset->extension }}">
                        متصفحك لا يدعم تشغيل الفيديو.
                    </video>
                @elseif(in_array(strtolower($asset->extension), ['mp3', 'wav', 'ogg', 'm4a', 'aac']))
                    <audio controls class="w-100">
                        <source src="{{ $streamUrl ?? $fileUrl }}" type="audio/{{ $asset->extension }}">
                        متصفحك لا يدعم تشغيل الصوت.
                    </audio>
                @elseif(in_array(strtolower($asset->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                    <img src="{{ $fileUrl }}" alt="{{ $asset->file_name }}" class="img-fluid" style="max-height: 500px;">
                @else
                    <div class="alert alert-info">
                        <i class="bi bi-file-earmark me-2"></i>
                        <a href="{{ $fileUrl }}" target="_blank" class="btn btn-primary">
                            <i class="bi bi-download me-1"></i>تحميل الملف
                        </a>
                    </div>
                @endif
                <div class="mt-3">
                    <small class="text-muted">رابط الملف:</small>
                    <div class="input-group mt-1">
                        <input type="text" class="form-control" value="{{ $fileUrl }}" id="fileUrlInput" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyFileUrl()">
                            <i class="bi bi-clipboard"></i> نسخ
                        </button>
                        <a href="{{ $fileUrl }}" download class="btn btn-primary" type="button">
                            <i class="bi bi-download"></i> تحميل
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @elseif($asset->transcription)
        <!-- المحتوى النصي فقط (بدون فيديو) -->
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">المحتوى النصي</h5>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="downloadTranscriptionText()" title="تحميل المحتوى النصي (صيغة SBV عند توفر التوقيت، وإلا TXT)">
                        <i class="bi bi-download me-1"></i>تحميل
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="editTranscriptionBtn2" onclick="toggleEditTranscription()">
                        <i class="bi bi-pencil me-1"></i>تعديل
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="bg-light p-3 rounded" id="transcriptionContainer2" style="max-height: 400px; overflow-y: auto; text-align: right; direction: rtl;">
                    <p class="mb-0" id="transcriptionTextView2" style="white-space: pre-wrap; text-align: right; direction: rtl;">{{ $asset->transcription }}</p>
                    <textarea class="form-control d-none" id="transcriptionTextarea2" rows="15" style="text-align: right; direction: rtl;">{{ $asset->transcription }}</textarea>
                </div>
                <div class="mt-2 d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        عدد الأحرف: <span id="transcriptionCharCount2">{{ number_format(strlen($asset->transcription)) }}</span>
                    </div>
                    <div class="d-none" id="transcriptionActions2">
                        <button type="button" class="btn btn-sm btn-success" onclick="saveTranscription({{ $asset->id }})">
                            <i class="bi bi-check me-1"></i>حفظ
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditTranscription()">
                            <i class="bi bi-x me-1"></i>إلغاء
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($asset->topics || $asset->emotions || $asset->intent || $asset->audience || ($asset->categories && $asset->categories->count() > 0))
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">تحليل المحتوى</h5>
            </div>
            <div class="card-body">
                @if($asset->categories && $asset->categories->count() > 0)
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">تصنيفات المحتوى:</small>
                    @foreach($asset->categories as $cat)
                        <span class="badge bg-success fs-6 me-1">{{ $cat->name }}</span>
                    @endforeach
                </div>
                @endif
                @if($asset->topics)
                <div class="mb-3">
                    <small class="text-muted d-block mb-2">المواضيع (Topics):</small>
                    <div class="d-flex flex-wrap gap-2" style="direction: rtl;">
                        @foreach(explode("\n", $asset->topics) as $topic)
                            @if(trim($topic))
                                <span class="badge bg-primary fs-6">{{ trim($topic) }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                @if($asset->emotions)
                <div class="mb-3">
                    <small class="text-muted d-block mb-2">المشاعر (Emotions):</small>
                    <div class="d-flex flex-wrap gap-2" style="direction: rtl;">
                        @foreach(explode("\n", $asset->emotions) as $emotion)
                            @if(trim($emotion))
                                <span class="badge bg-info text-dark fs-6">{{ trim($emotion) }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                @if($asset->intent)
                <div class="mb-3">
                    <small class="text-muted d-block mb-2">النية/الهدف (Intent):</small>
                    <div class="d-flex flex-wrap gap-2" style="direction: rtl;">
                        @foreach(explode("\n", $asset->intent) as $intentItem)
                            @if(trim($intentItem))
                                <span class="badge bg-success fs-6">{{ trim($intentItem) }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                @if($asset->audience)
                <div class="mb-3">
                    <small class="text-muted d-block mb-2">الجمهور المستهدف (Audience):</small>
                    <div class="d-flex flex-wrap gap-2" style="direction: rtl;">
                        @foreach(explode("\n", $asset->audience) as $audienceItem)
                            @if(trim($audienceItem))
                                <span class="badge bg-warning text-dark fs-6">{{ trim($audienceItem) }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- ملفات الفيديو المتاحة: الأصلي + النسخ المحسّنة + نسخ HLS (الجودة والمساحة) --}}
        @php
            $hasVideoFile = $asset->relative_path && strpos($asset->relative_path, 'assets/') === 0 && \Illuminate\Support\Facades\Storage::disk('public')->exists($asset->relative_path);
            $hasOptimizedVersions = $asset->optimizedVersions && $asset->optimizedVersions->count() > 0;
            $hasHlsVersions = $asset->hlsVersions && $asset->hlsVersions->count() > 0;
            $formatSize = function($bytes) {
                if (!$bytes) return '-';
                if ($bytes >= 1073741824) return number_format(round($bytes / 1073741824, 2), 2) . ' GB';
                if ($bytes >= 1048576) return number_format(round($bytes / 1048576, 2), 2) . ' MB';
                if ($bytes >= 1024) return number_format(round($bytes / 1024, 2), 2) . ' KB';
                return $bytes . ' بايت';
            };
        @endphp
        @php
            $currentWebPath = $asset->web_video_relative_path ?? $asset->relative_path;
        @endphp
        @if($hasVideoFile || $hasOptimizedVersions || $hasHlsVersions)
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">ملفات الفيديو المتاحة</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>النسخة</th>
                                <th>الجودة (الأبعاد)</th>
                                <th>المساحة</th>
                                <th>عرض على الويب</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- الفيديو الأصلي --}}
                            @if($hasVideoFile)
                            <tr>
                                <td>
                                    <span class="badge bg-secondary fs-6">الفيديو الأصلي</span>
                                </td>
                                <td>
                                    @if($asset->width && $asset->height)
                                        {{ $asset->width }} × {{ $asset->height }} بكسل
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $formatSize($asset->size_bytes) }}</td>
                                <td>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input web-video-radio" type="radio" name="web_video_path" id="web_video_orig" value="{{ $asset->relative_path }}" {{ $currentWebPath === $asset->relative_path ? 'checked' : '' }}>
                                        <label class="form-check-label" for="web_video_orig">عرض هذه النسخة</label>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ asset('storage/' . $asset->relative_path) }}" target="_blank" class="btn btn-sm btn-outline-primary" download>
                                        <i class="bi bi-download me-1"></i>تحميل
                                    </a>
                                    <a href="{{ asset('storage/' . $asset->relative_path) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-play-circle me-1"></i>تشغيل
                                    </a>
                                </td>
                            </tr>
                            @endif
                            {{-- النسخ المحسّنة (تقليل المساحة) — نسخة جديدة لا تستبدل الأصلي --}}
                            @if($hasOptimizedVersions)
                                @foreach($asset->optimizedVersions as $opt)
                                    @if(\Illuminate\Support\Facades\Storage::disk('public')->exists($opt->relative_path))
                                    <tr>
                                        <td>
                                            <span class="badge bg-info fs-6">{{ $opt->quality_label }}</span>
                                        </td>
                                        <td>
                                            @if($opt->width && $opt->height)
                                                {{ $opt->width }} × {{ $opt->height }} بكسل
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $formatSize($opt->size_bytes) }}</td>
                                        <td>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input web-video-radio" type="radio" name="web_video_path" id="web_video_opt_{{ $opt->id }}" value="{{ $opt->relative_path }}" {{ $currentWebPath === $opt->relative_path ? 'checked' : '' }}>
                                                <label class="form-check-label" for="web_video_opt_{{ $opt->id }}">عرض هذه النسخة</label>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ asset('storage/' . $opt->relative_path) }}" target="_blank" class="btn btn-sm btn-outline-primary" download>
                                                <i class="bi bi-download me-1"></i>تحميل
                                            </a>
                                            <a href="{{ asset('storage/' . $opt->relative_path) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-play-circle me-1"></i>تشغيل
                                            </a>
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach
                            @endif
                            {{-- نسخ HLS --}}
                            @if($hasHlsVersions)
                                @foreach($asset->hlsVersions as $version)
                                <tr>
                                    <td>
                                        <span class="badge bg-primary fs-6">{{ $version->resolution }}</span>
                                    </td>
                                    <td>
                                        @if($version->width && $version->height)
                                            {{ $version->width }} × {{ $version->height }} بكسل
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $formatSize($version->total_size_bytes) }}</td>
                                    <td><span class="text-muted small">—</span></td>
                                    <td>
                                        @if($version->playlist_path)
                                            <a href="{{ asset('storage/' . $version->playlist_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-play-circle me-1"></i>تشغيل
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                @if($hasHlsVersions && $asset->hlsVersions->first() && $asset->hlsVersions->first()->master_playlist_path)
                <div class="mt-3">
                    <strong>قائمة التشغيل الرئيسية (HLS):</strong>
                    <a href="{{ asset('storage/' . $asset->hlsVersions->first()->master_playlist_path) }}" target="_blank" class="btn btn-sm btn-success ms-2">
                        <i class="bi bi-list-ul me-1"></i>master.m3u8
                    </a>
                </div>
                @endif
            </div>
        </div>
        @endif

        @if($asset->audioFiles && $asset->audioFiles->count() > 0)
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">ملفات الصوت المتاحة</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>الصيغة</th>
                                <th>معدل البت</th>
                                <th>معدل العينة</th>
                                <th>القنوات</th>
                                <th>المدة</th>
                                <th>الحجم</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($asset->audioFiles as $audioFile)
                            <tr>
                                <td>
                                    <span class="badge bg-success fs-6">{{ strtoupper($audioFile->format) }}</span>
                                </td>
                                <td><code>{{ $audioFile->bitrate }}</code></td>
                                <td>
                                    @if($audioFile->sample_rate)
                                        {{ number_format($audioFile->sample_rate / 1000, 1) }} kHz
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($audioFile->channels)
                                        @if($audioFile->channels == 1)
                                            Mono
                                        @elseif($audioFile->channels == 2)
                                            Stereo
                                        @else
                                            {{ $audioFile->channels }}
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($audioFile->duration_seconds)
                                        @php
                                            $hours = floor($audioFile->duration_seconds / 3600);
                                            $minutes = floor(($audioFile->duration_seconds % 3600) / 60);
                                            $seconds = $audioFile->duration_seconds % 60;
                                            if ($hours > 0) {
                                                $duration = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
                                            } else {
                                                $duration = sprintf('%d:%02d', $minutes, $seconds);
                                            }
                                        @endphp
                                        {{ $duration }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($audioFile->file_size_bytes)
                                        @php
                                            $sizeMB = round($audioFile->file_size_bytes / (1024 * 1024), 2);
                                        @endphp
                                        {{ $sizeMB }} MB
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($audioFile->file_path)
                                        <a href="{{ asset('storage/' . $audioFile->file_path) }}" target="_blank" class="btn btn-sm btn-outline-success" download>
                                            <i class="bi bi-download me-1"></i>تحميل
                                        </a>
                                        <a href="{{ asset('storage/' . $audioFile->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-play-circle me-1"></i>تشغيل
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        @if(($asset->scholar?->name ?? $asset->speaker_name) || $asset->year || $asset->gregorian_year)
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h5 class="mb-0">معلومات المحتوى</h5>
            </div>
            <div class="card-body">
                @php $speakerDisplay = $asset->scholar?->name ?? $asset->speaker_name; @endphp
                @if($speakerDisplay)
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">اسم المتحدث (الشيخ):</small>
                    <strong class="d-block fs-5 text-primary">{{ $speakerDisplay }}</strong>
                </div>
                @else
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">اسم المتحدث (الشيخ):</small>
                    <span class="text-muted">غير محدد</span>
                </div>
                @endif
                <div class="row">
                    {{-- @if($asset->year)
                    <div class="col-6 mb-2">
                        <small class="text-muted d-block mb-1">السنة الهجرية:</small>
                        <span class="badge bg-warning text-dark">{{ $asset->year }}</span>
                    </div>
                    @endif --}}
                    @if($asset->gregorian_year)
                    <div class="col-6 mb-2">
                        <small class="text-muted d-block mb-1">السنة الميلادية:</small>
                        <span class="badge bg-success">{{ $asset->gregorian_year }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <div class="card mb-3">
            <div class="card-header bg-white">
                <h5 class="mb-0">إحصائيات سريعة</h5>
            </div>
            <div class="card-body">
                @if($asset->size_bytes)
                <div class="mb-2">
                    <small class="text-muted d-block">الحجم:</small>
                    @php
                        $sizeBytes = $asset->size_bytes;
                        if ($sizeBytes >= 1073741824) {
                            // GB
                            $size = round($sizeBytes / 1073741824, 2);
                            $unit = 'GB';
                        } elseif ($sizeBytes >= 1048576) {
                            // MB
                            $size = round($sizeBytes / 1048576, 2);
                            $unit = 'MB';
                        } elseif ($sizeBytes >= 1024) {
                            // KB
                            $size = round($sizeBytes / 1024, 2);
                            $unit = 'KB';
                        } else {
                            // Bytes
                            $size = $sizeBytes;
                            $unit = 'بايت';
                        }
                    @endphp
                    <strong class="text-primary fs-5">{{ number_format($size, $unit === 'بايت' ? 0 : 2) }} {{ $unit }}</strong>
                </div>
                @endif
                @if($asset->duration_seconds)
                <div class="mb-2">
                    <small class="text-muted d-block">المدة:</small>
                    <strong>{{ $asset->duration_formatted }}</strong>
                </div>
                @endif
                @if($asset->width && $asset->height)
                <div class="mb-2">
                    <small class="text-muted d-block">الأبعاد:</small>
                    <strong>{{ $asset->width }} × {{ $asset->height }}</strong>
                </div>
                @endif
                @if($asset->orientation)
                <div>
                    <small class="text-muted d-block">الاتجاه:</small>
                    @if($asset->orientation == 'portrait')
                        <span class="badge bg-info">عمودي</span>
                    @elseif($asset->orientation == 'landscape')
                        <span class="badge bg-success">أفقي</span>
                    @else
                        <span class="badge bg-secondary">مربع</span>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">إجراءات</h5>
            </div>
            <div class="card-body">
                <!-- 0. قابل للنشر -->
                <form action="{{ route('assets.toggle-publishable', $asset) }}" method="POST" class="mb-3">
                    @csrf
                    <button type="submit" class="btn {{ $asset->is_publishable ? 'btn-success' : 'btn-outline-secondary' }} w-100 d-flex justify-content-between align-items-center">
                        <span>قابل للنشر</span>
                        @if($asset->is_publishable)
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-check-circle"></i> مفعّل
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                <i class="bi bi-x-circle"></i> غير مفعّل
                            </span>
                        @endif
                    </button>
                </form>

                <!-- فيديو مميز: يظهر في أول ٨ فيديوهات بالصفحة الرئيسية -->
                <form action="{{ route('assets.toggle-featured', $asset) }}" method="POST" class="mb-3">
                    @csrf
                    <button type="submit" class="btn {{ $asset->is_featured ?? false ? 'btn-warning' : 'btn-outline-warning' }} w-100 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-star me-1"></i>فيديو مميز</span>
                        @if($asset->is_featured ?? false)
                            <span class="badge bg-dark">
                                <i class="bi bi-star-fill"></i> مميز
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                <i class="bi bi-star"></i> عادي
                            </span>
                        @endif
                    </button>
                </form>

                @if($asset->is_featured ?? false)
                <!-- ترتيب العرض ضمن المميزة: الرقم الأصغر يظهر أولاً (١ ثم ٢ ثم ٣...) -->
                <form action="{{ route('assets.update-featured-order', $asset) }}" method="POST" class="mb-3">
                    @csrf
                    <label for="featured_order" class="form-label small"><i class="bi bi-sort-numeric-down me-1"></i>ترتيب العرض (المميزة)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control" id="featured_order" name="featured_order" min="0" step="1" value="{{ old('featured_order', $asset->featured_order ?? '') }}" placeholder="اختياري">
                        <button type="submit" class="btn btn-outline-secondary">حفظ</button>
                    </div>
                    <p class="text-muted small mb-0 mt-1">الأصغر يظهر أولاً في الصفحة الرئيسية. اتركه فارغاً ليعتمد ترتيب تاريخ النشر.</p>
                </form>
                @endif

                <!-- جدولة النشر: اليوم والوقت -->
                <div class="mb-3">
                    <h6 class="mb-2"><i class="bi bi-calendar-event me-1"></i>جدولة النشر</h6>
                    @if($asset->scheduled_publish_at)
                        <p class="text-muted small mb-2">
                            سيتم النشر في: <strong>{{ $asset->scheduled_publish_at->format('Y-m-d H:i') }}</strong>
                            ({{ config('app.timezone', 'UTC') }})
                        </p>
                        <form action="{{ route('assets.schedule-publish', $asset) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="clear_schedule" value="1">
                            <button type="submit" class="btn btn-outline-warning btn-sm">
                                <i class="bi bi-x-circle me-1"></i>إلغاء الجدولة
                            </button>
                        </form>
                    @else
                        <form action="{{ route('assets.schedule-publish', $asset) }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <label for="scheduled_at" class="form-label small">اليوم والوقت (حسب {{ config('app.timezone', 'UTC') }})</label>
                                <input type="datetime-local" class="form-control form-control-sm" id="scheduled_at" name="scheduled_at"
                                       min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}"
                                       value="">
                            </div>
                            <button type="submit" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-calendar-check me-1"></i>حفظ الجدولة
                            </button>
                        </form>
                    @endif
                </div>

                <!-- نشر سريع: تشغيل مجموعة خطوات تلقائياً -->
                <div class="card border-primary mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-lightning-charge me-1"></i>نشر سريع</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">تشغيل الخطوات التالية تلقائياً بالترتيب:</p>
                        <ul class="list-unstyled small mb-3" id="quickPublishSteps">
                            <li id="qpStep1" class="d-flex align-items-center mb-2"><span class="qp-icon me-2">○</span>نقل المحتوى</li>
                            <li id="qpStep2" class="d-flex align-items-center mb-2"><span class="qp-icon me-2">○</span>استخراج البيانات من المسار</li>
                            <li id="qpStep3" class="d-flex align-items-center mb-2"><span class="qp-icon me-2">○</span>استخراج المحتوى النصي</li>
                            <li id="qpStep4" class="d-flex align-items-center mb-2"><span class="qp-icon me-2">○</span>تحليل المحتوى النصي</li>
                            <li id="qpStep5" class="d-flex align-items-center mb-2"><span class="qp-icon me-2">○</span>تقليل حجم الفيديو (جودة متوسطة)</li>
                            <li id="qpStep6" class="d-flex align-items-center mb-2"><span class="qp-icon me-2">○</span>استخراج ملف صوتي</li>
                        </ul>
                        <button type="button" class="btn btn-primary w-100" id="quickPublishBtn">
                            <i class="bi bi-play-circle me-1"></i>بدء النشر السريع
                        </button>
                    </div>
                </div>

                <!-- 1. نقل المحتوى -->
                <form action="{{ route('assets.move', $asset) }}" method="POST" class="mb-3" id="moveForm">
                    @csrf
                    <button type="submit" class="btn btn-warning w-100 d-flex justify-content-between align-items-center" id="moveBtn"
                            onclick="return confirm('سيتم نقل الملف إلى: السنة/ID/master.extension\nهل أنت متأكد من نقل الملف؟')">
                        <span>نقل المحتوى</span>
                        @if($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i>
                            </span>
                        @endif
                    </button>
                </form>

                <!-- 2. استخراج البيانات من المسار -->
                <form action="{{ route('assets.extract', $asset) }}" method="POST" class="mb-3" id="extractForm">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100 d-flex justify-content-between align-items-center" id="extractBtn">
                        <span>استخراج البيانات من المسار</span>
                        @if($asset->speaker_name || $asset->title)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i>
                            </span>
                        @endif
                    </button>
                </form>

                <!-- 2.5. إعادة استخراج Metadata (مخفى) -->
                <div style="display: none;">
                    <form action="{{ route('assets.re-extract-metadata', $asset) }}" method="POST" class="mb-3" id="reExtractMetadataForm">
                        @csrf
                        <button type="submit" class="btn btn-info w-100 d-flex justify-content-between align-items-center" id="reExtractMetadataBtn">
                            <span><i class="bi bi-arrow-clockwise me-1"></i>إعادة استخراج بيانات الفيديو</span>
                            @if($asset->width && $asset->height)
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i>
                                </span>
                            @endif
                        </button>
                    </form>
                </div>

                <!-- 3. استخراج المحتوى النصي -->
                @if($fileInStorage)
                <div class="mb-3">
                    <label class="form-label small text-muted">جودة النموذج (Whisper)</label>
                    <div class="d-flex flex-wrap gap-3 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="transcribe_quality" id="transcribe_base" value="base">
                            <label class="form-check-label" for="transcribe_base">base (أسرع)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="transcribe_quality" id="transcribe_small" value="small">
                            <label class="form-check-label" for="transcribe_small">small</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="transcribe_quality" id="transcribe_medium" value="medium" checked>
                            <label class="form-check-label" for="transcribe_medium">medium (أدق)</label>
                        </div>
                    </div>
                    <form id="transcribeForm" class="mb-0">
                        @csrf
                        <button type="button" class="btn btn-success w-100 d-flex justify-content-between align-items-center" id="transcribeBtn">
                            <span>استخراج المحتوى النصي</span>
                            @if($asset->transcription)
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i>
                                </span>
                            @endif
                        </button>
                    </form>
                </div>
                @else
                <div class="mb-3">
                    <button type="button" class="btn btn-success w-100 d-flex justify-content-between align-items-center" id="transcribeBtn" disabled title="يجب نقل الفيديو إلى الموقع أولاً">
                        <span>استخراج المحتوى النصي</span>
                        @if($asset->transcription)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i>
                            </span>
                        @endif
                    </button>
                    <small class="text-muted d-block mt-2">
                        <i class="bi bi-info-circle me-1"></i>غير متاح - يجب نقل الفيديو إلى الموقع أولاً
                    </small>
                </div>
                @endif

                <!-- 4. تحليل المحتوى النصي -->
                @if($asset->transcription)
                <form id="analyzeForm" class="mb-3">
                    @csrf
                    <button type="button" class="btn btn-info w-100 d-flex justify-content-between align-items-center" id="analyzeBtn">
                        <span>تحليل المحتوى النصي</span>
                        @if($asset->topics || $asset->emotions || $asset->intent || $asset->audience || ($asset->categories && $asset->categories->count() > 0) || $asset->site_description)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i>
                            </span>
                        @endif
                    </button>
                </form>
                @endif

                <!-- 4.5 تقليل مساحة الملف الأصلي (قبل HLS) -->
                @if($fileInStorage)
                <div class="mb-3">
                    <label class="form-label small text-muted">إعدادات تقليل المساحة (مناسب للنشر على الويب)</label>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="optimize_quality" id="optimize_high" value="high">
                            <label class="form-check-label" for="optimize_high">جودة عالية (حجم أكبر)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="optimize_quality" id="optimize_balanced" value="balanced" checked>
                            <label class="form-check-label" for="optimize_balanced">متوازن (مناسب للنشر)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="optimize_quality" id="optimize_small" value="small">
                            <label class="form-check-label" for="optimize_small">حجم أصغر (تحميل أسرع)</label>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary w-100 d-flex justify-content-between align-items-center" id="optimizeOriginalBtn">
                        <span><i class="bi bi-file-earmark-arrow-down me-1"></i>تقليل مساحة الملف الأصلي</span>
                    </button>
                    <small class="text-muted d-block mt-1">
                        <i class="bi bi-info-circle me-1"></i>ينشئ نسخة جديدة محسّنة (لا يغيّر الملف الأصلي). تظهر النسخة في جدول "ملفات الفيديو المتاحة".
                    </small>
                </div>
                <!-- Progress و Terminal لـ تقليل المساحة -->
                <div id="optimizeProgress" style="display: none;" class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted" id="optimizeProgressMessage">جاري المعالجة...</small>
                        <small class="text-muted" id="optimizeProgressPercent">0%</small>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="optimizeProgressBar" style="width: 0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <span id="optimizeProgressText">0%</span>
                        </div>
                    </div>
                </div>
                <div id="optimizeTerminalViewer" style="display: none;" class="mb-3">
                    <div class="card">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-terminal me-2"></i>سجل عملية تقليل المساحة</span>
                            <button type="button" class="btn btn-sm btn-outline-light" onclick="clearOptimizeTerminal()"><i class="bi bi-x-circle"></i></button>
                        </div>
                        <div class="card-body bg-dark text-light p-3" style="font-family: 'Courier New', monospace; font-size: 12px; max-height: 300px; overflow-y: auto;" id="optimizeTerminalContent">
                            <div class="text-success">$ بدء عملية تقليل المساحة...</div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- خطوة تحويل فيديو إلى HLS مخفية --}}
                @if(false)
                <!-- 5. تحويل فيديو إلى HLS -->
                @if($fileInStorage)
                <form id="convertHlsForm" class="mb-3">
                    @csrf
                    <button type="button" class="btn btn-purple w-100 d-flex justify-content-between align-items-center" id="convertHlsBtn" style="background-color: #6f42c1; color: white;">
                        <span>تحويل فيديو إلى HLS</span>
                        @if($asset->hlsVersions && $asset->hlsVersions->count() > 0)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i>
                            </span>
                        @endif
                    </button>
                </form>
                @else
                <div class="mb-3">
                    <button type="button" class="btn btn-purple w-100 d-flex justify-content-between align-items-center" id="convertHlsBtn" disabled title="يجب نقل الفيديو إلى الموقع أولاً" style="background-color: #6f42c1; color: white;">
                        <span>تحويل فيديو إلى HLS</span>
                        @if($asset->hlsVersions && $asset->hlsVersions->count() > 0)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i>
                            </span>
                        @endif
                    </button>
                    <small class="text-muted d-block mt-2">
                        <i class="bi bi-info-circle me-1"></i>غير متاح - يجب نقل الفيديو إلى الموقع أولاً
                    </small>
                </div>
                @endif
                @endif

                <!-- 6. استخراج الصوت من الفيديو -->
                @if($fileInStorage)
                <form id="extractAudioForm" class="mb-3">
                    @csrf
                    <button type="button" class="btn btn-success w-100 d-flex justify-content-between align-items-center" id="extractAudioBtn" style="background-color: #10b981; color: white;">
                        <span>تحويل الفيديو إلى ملف صوتي (MP3)</span>
                        @if($asset->audioFiles && $asset->audioFiles->count() > 0)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i>
                            </span>
                        @endif
                    </button>
                </form>
                @else
                <div class="mb-3">
                    <button type="button" class="btn btn-success w-100 d-flex justify-content-between align-items-center" id="extractAudioBtn" disabled title="يجب نقل الفيديو إلى الموقع أولاً" style="background-color: #10b981; color: white;">
                        <span>تحويل الفيديو إلى ملف صوتي (MP3)</span>
                        @if($asset->audioFiles && $asset->audioFiles->count() > 0)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i>
                            </span>
                        @endif
                    </button>
                    <small class="text-muted d-block mt-2">
                        <i class="bi bi-info-circle me-1"></i>غير متاح - يجب نقل الفيديو إلى الموقع أولاً
                    </small>
                </div>
                @endif
                
                <!-- Progress Bar for Transcription -->
                <div id="transcribeProgress" style="display: none;" class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted" id="progressMessage">جاري المعالجة...</small>
                        <small class="text-muted" id="progressPercent">0%</small>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             id="progressBar"
                             style="width: 0%"
                             aria-valuenow="0" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <span id="progressText">0%</span>
                        </div>
                    </div>
                </div>
                
                <!-- Terminal Log Viewer for Transcription -->
                <div id="terminalViewer" style="display: none;" class="mb-3">
                    <div class="card">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-terminal me-2"></i>سجل العملية</span>
                            <button type="button" class="btn btn-sm btn-outline-light" onclick="clearTerminal()">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                        <div class="card-body bg-dark text-light p-3" style="font-family: 'Courier New', monospace; font-size: 12px; max-height: 300px; overflow-y: auto;" id="terminalContent">
                            <div class="text-success">$ بدء عملية الاستخراج...</div>
                        </div>
                    </div>
                </div>

                {{-- شريط التقدم وسجل HLS مخفيان --}}
                @if(false)
                <div id="hlsProgress" style="display: none;" class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted" id="hlsProgressMessage">جاري التحويل...</small>
                        <small class="text-muted" id="hlsProgressPercent">0%</small>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-purple" 
                             role="progressbar" 
                             id="hlsProgressBar"
                             style="width: 0%; background-color: #6f42c1 !important;"
                             aria-valuenow="0" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <span id="hlsProgressText">0%</span>
                        </div>
                    </div>
                </div>
                <div id="hlsTerminalViewer" style="display: none;" class="mb-3">
                    <div class="card">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-terminal me-2"></i>سجل عملية التحويل</span>
                            <button type="button" class="btn btn-sm btn-outline-light" onclick="clearHlsTerminal()">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                        <div class="card-body bg-dark text-light p-3" style="font-family: 'Courier New', monospace; font-size: 12px; max-height: 300px; overflow-y: auto;" id="hlsTerminalContent">
                            <div class="text-success">$ بدء عملية التحويل...</div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Progress Bar for Audio Extraction -->
                <div id="audioProgress" style="display: none;" class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted" id="audioProgressMessage">جاري الاستخراج...</small>
                        <small class="text-muted" id="audioProgressPercent">0%</small>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             id="audioProgressBar"
                             style="width: 0%; background-color: #10b981 !important;"
                             aria-valuenow="0" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <span id="audioProgressText">0%</span>
                        </div>
                    </div>
                </div>
                
                <!-- Terminal Log Viewer for Audio Extraction -->
                <div id="audioTerminalViewer" style="display: none;" class="mb-3">
                    <div class="card">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-terminal me-2"></i>سجل عملية استخراج الصوت</span>
                            <button type="button" class="btn btn-sm btn-outline-light" onclick="clearAudioTerminal()">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                        <div class="card-body bg-dark text-light p-3" style="font-family: 'Courier New', monospace; font-size: 12px; max-height: 300px; overflow-y: auto;" id="audioTerminalContent">
                            <div class="text-success">$ بدء عملية استخراج الصوت...</div>
                        </div>
                    </div>
                </div>
                
                @if(session('extracted_speaker') || session('extracted_title'))
                <div class="alert alert-info mb-3">
                    <h6 class="alert-heading mb-2"><i class="bi bi-info-circle me-1"></i>النتائج المستخرجة:</h6>
                    @if(session('extracted_speaker'))
                    <div class="mb-2">
                        <strong>اسم المتحدث:</strong>
                        <span class="badge bg-primary ms-2">{{ session('extracted_speaker') }}</span>
                    </div>
                    @endif
                    @if(session('extracted_title'))
                    <div>
                        <strong>العنوان:</strong>
                        <span class="badge bg-info text-dark ms-2">{{ session('extracted_title') }}</span>
                    </div>
                    @endif
                </div>
                @endif

                <form action="{{ route('assets.destroy', $asset) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100" 
                            onclick="return confirm('هل أنت متأكد من حذف هذا الملف؟')">
                        حذف الملف
                    </button>
                </form>
            </div>
        </div>

        @if($asset->width && $asset->height)
        <div class="card mt-3">
            <div class="card-header bg-white">
                <h5 class="mb-0">معاينة الأبعاد</h5>
            </div>
            <div class="card-body text-center">
                @if($asset->thumbnail_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($asset->thumbnail_path))
                    <div class="mb-3">
                        <img src="{{ asset('storage/' . $asset->thumbnail_path) }}" 
                             alt="صورة مصغرة" 
                             class="img-fluid rounded" 
                             style="max-width: 300px; max-height: 300px; border: 2px solid #dee2e6;">
                    </div>
                @else
                    <div class="border rounded p-3 mb-3" style="background: linear-gradient(45deg, #f0f0f0 25%, transparent 25%), linear-gradient(-45deg, #f0f0f0 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #f0f0f0 75%), linear-gradient(-45deg, transparent 75%, #f0f0f0 75%); background-size: 20px 20px;">
                        @php
                            $maxWidth = 200;
                            $ratio = $asset->height / $asset->width;
                            $displayWidth = min($maxWidth, $asset->width);
                            $displayHeight = $displayWidth * $ratio;
                        @endphp
                        <div style="width: {{ $displayWidth }}px; height: {{ $displayHeight }}px; margin: 0 auto; background: #007bff; opacity: 0.7; border: 2px solid #0056b3;"></div>
                    </div>
                @endif
                <small class="text-muted mt-2 d-block mb-3">{{ $asset->width }} × {{ $asset->height }}</small>
                
                @if($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0)
                <form action="{{ route('assets.upload-thumbnail', $asset) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <input type="file" 
                               name="thumbnail" 
                               id="thumbnailInput" 
                               accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" 
                               class="form-control form-control-sm"
                               required>
                        <small class="text-muted d-block mt-1">الصيغ المدعومة: JPEG, PNG, JPG, GIF, WEBP (حد أقصى 2MB)</small>
                        <small class="text-muted d-block mt-1"><strong>نسبة العرض للارتفاع الموصى بها:</strong> 16:9 (أفقي) للفيديو العادي، أو 9:16 (عمودي) للفيديوهات القصيرة — لظهور أفضل في الكروت.</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-upload me-1"></i>رفع صورة مصغرة
                    </button>
                </form>
                @else
                <small class="text-muted d-block">يجب نقل الفيديو إلى الموقع أولاً لرفع صورة مصغرة</small>
                @endif

                {{-- صورة الغلاف (Cover) تحت الصورة المصغرة --}}
                <hr class="my-3">
                <h6 class="mb-2">صورة الغلاف (Cover)</h6>
                @if($asset->cover_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($asset->cover_path))
                    <div class="mb-3">
                        <img src="{{ asset('storage/' . $asset->cover_path) }}"
                             alt="صورة الغلاف"
                             class="img-fluid rounded"
                             style="max-width: 100%; max-height: 400px; border: 2px solid #dee2e6;">
                    </div>
                @else
                    <div class="border rounded p-3 mb-3 text-muted small" style="background: var(--bs-light);">
                        لا توجد صورة غلاف. يمكنك رفع صورة من الأسفل.
                    </div>
                @endif
                @if($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0)
                <form action="{{ route('assets.upload-cover', $asset) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <input type="file"
                               name="cover"
                               id="coverInput"
                               accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                               class="form-control form-control-sm"
                               required>
                        <small class="text-muted d-block mt-1">الصيغ: JPEG, PNG, JPG, GIF, WEBP (حد أقصى 5MB)</small>
                        <small class="text-muted d-block mt-1"><strong>صورة الغلاف أفقية فقط:</strong> يُفضّل نسبة 16:9 لظهور أفضل في الكروت.</small>
                    </div>
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-image me-1"></i>رفع صورة الغلاف
                    </button>
                </form>
                @else
                <small class="text-muted d-block">يجب نقل الفيديو إلى الموقع أولاً لرفع صورة الغلاف</small>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

@push('styles')
<style>
/* كروت التصنيفات القابلة للاختيار */
.category-card-selectable {
    position: relative;
    width: 120px;
    height: 140px;
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    background-color: var(--bg-primary);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    text-align: center;
    overflow: hidden;
}

.category-card-selectable:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.category-card-selectable.selected {
    border-color: var(--primary-color);
    background-color: rgba(24, 135, 129, 0.1);
    box-shadow: 0 0 0 3px rgba(24, 135, 129, 0.2);
}

.category-card-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: var(--radius-sm);
    margin-bottom: 0.5rem;
}

.category-card-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--bg-tertiary);
    border-radius: var(--radius-sm);
    margin-bottom: 0.5rem;
    font-size: 2rem;
    color: var(--text-secondary);
}

.category-card-selectable.selected .category-card-icon {
    background-color: var(--primary-color);
    color: white;
}

.category-card-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary);
    margin-top: 0.25rem;
}

.category-card-selectable.selected .category-card-name {
    color: var(--primary-color);
    font-weight: 600;
}

.category-card-check {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    width: 24px;
    height: 24px;
    display: none;
    align-items: center;
    justify-content: center;
    background-color: var(--primary-color);
    border-radius: 50%;
    color: white;
    font-size: 0.875rem;
}

.category-card-selectable.selected .category-card-check {
    display: flex;
}

/* كروت قوائم التشغيل (نفس فكرة التصنيفات) */
.playlist-card-selectable {
    position: relative;
    width: 120px;
    height: 140px;
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    background-color: var(--bg-primary);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    text-align: center;
    overflow: hidden;
}

.playlist-card-selectable:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.playlist-card-selectable.selected {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.1);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
}

.playlist-card-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: var(--radius-sm);
    margin-bottom: 0.5rem;
}

.playlist-card-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--bg-tertiary);
    border-radius: var(--radius-sm);
    margin-bottom: 0.5rem;
    font-size: 2rem;
    color: var(--text-secondary);
}

.playlist-card-selectable.selected .playlist-card-icon {
    background-color: #0d6efd;
    color: white;
}

.playlist-card-title {
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--text-primary);
    margin-top: 0.25rem;
    line-height: 1.2;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.playlist-card-selectable.selected .playlist-card-title {
    color: #0d6efd;
    font-weight: 600;
}

.playlist-card-check {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    width: 24px;
    height: 24px;
    display: none;
    align-items: center;
    justify-content: center;
    background-color: #0d6efd;
    border-radius: 50%;
    color: white;
    font-size: 0.875rem;
}

.playlist-card-selectable.selected .playlist-card-check {
    display: flex;
}
</style>
@endpush

@push('scripts')
<script>
var assetTranscriptionFilename = @json(pathinfo($asset->file_name ?? 'transcription', PATHINFO_FILENAME));

// وظائف تعديل وصف الموقع
let originalSiteDescription = '';

function toggleEditSiteDescription() {
    const textElement = document.getElementById('siteDescriptionText');
    const textareaElement = document.getElementById('siteDescriptionTextarea');
    const actionsDiv = document.getElementById('siteDescriptionActions');
    const editBtn = document.getElementById('editSiteDescriptionBtn');
    
    if (textareaElement && textareaElement.classList.contains('d-none')) {
        // بدء التعديل
        originalSiteDescription = textareaElement.value;
        if (textElement) textElement.classList.add('d-none');
        textareaElement.classList.remove('d-none');
        if (actionsDiv) actionsDiv.classList.remove('d-none');
        if (editBtn) editBtn.style.display = 'none';
        textareaElement.focus();
    }
}

function cancelEditSiteDescription() {
    const textElement = document.getElementById('siteDescriptionText');
    const textareaElement = document.getElementById('siteDescriptionTextarea');
    const actionsDiv = document.getElementById('siteDescriptionActions');
    const editBtn = document.getElementById('editSiteDescriptionBtn');
    
    if (textareaElement) {
        textareaElement.value = originalSiteDescription;
        if (textElement) textElement.classList.remove('d-none');
        textareaElement.classList.add('d-none');
        if (actionsDiv) actionsDiv.classList.add('d-none');
        if (editBtn) editBtn.style.display = 'inline-block';
    }
}

function saveSiteDescription(assetId) {
    const textareaElement = document.getElementById('siteDescriptionTextarea');
    if (!textareaElement) return;
    
    const siteDescription = textareaElement.value.trim();
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';
    
    fetch(`/assets/${assetId}/update-site-description`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            site_description: siteDescription
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const textElement = document.getElementById('siteDescriptionText');
            const actionsDiv = document.getElementById('siteDescriptionActions');
            const editBtn = document.getElementById('editSiteDescriptionBtn');
            
            if (siteDescription) {
                if (textElement) {
                    textElement.textContent = siteDescription;
                    textElement.classList.remove('text-muted');
                    textElement.classList.add('mb-0');
                }
            } else {
                if (textElement) {
                    textElement.textContent = 'غير محدد';
                    textElement.classList.add('text-muted');
                    textElement.classList.remove('mb-0');
                }
            }
            
            if (textElement) textElement.classList.remove('d-none');
            textareaElement.classList.add('d-none');
            if (actionsDiv) actionsDiv.classList.add('d-none');
            if (editBtn) editBtn.style.display = 'inline-block';
            
            showSuccessMessage('تم حفظ وصف الموقع بنجاح');
        } else {
            alert('خطأ: ' + (data.error || 'فشل حفظ وصف الموقع'));
        }
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء حفظ وصف الموقع');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// وظائف تعديل العنوان
let originalTitle = '';

function toggleEditTitle() {
    const titleText = document.getElementById('titleText');
    const titleInput = document.getElementById('titleInput');
    const titleActions = document.getElementById('titleActions');
    const editBtn = document.getElementById('editTitleBtn');
    
    if (titleInput && titleInput.classList.contains('d-none')) {
        // بدء التعديل
        originalTitle = titleInput.value;
        if (titleText) titleText.classList.add('d-none');
        titleInput.classList.remove('d-none');
        if (titleActions) titleActions.classList.remove('d-none');
        if (editBtn) editBtn.style.display = 'none';
        titleInput.focus();
    }
}

function cancelEditTitle() {
    const titleText = document.getElementById('titleText');
    const titleInput = document.getElementById('titleInput');
    const titleActions = document.getElementById('titleActions');
    const editBtn = document.getElementById('editTitleBtn');
    
    if (titleInput) {
        titleInput.value = originalTitle;
        titleInput.classList.add('d-none');
        if (titleText) titleText.classList.remove('d-none');
        if (titleActions) titleActions.classList.add('d-none');
        if (editBtn) editBtn.style.display = 'inline-block';
    }
}

function saveTitle(assetId) {
    const titleText = document.getElementById('titleText');
    const titleInput = document.getElementById('titleInput');
    const titleActions = document.getElementById('titleActions');
    const editBtn = document.getElementById('editTitleBtn');
    
    if (!titleInput) return;
    
    const title = titleInput.value.trim();
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        alert('خطأ: لم يتم العثور على CSRF token');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
        return;
    }
    
    fetch(`/assets/${assetId}/update-title`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            title: title
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (title) {
                if (titleText) {
                    titleText.textContent = title;
                    titleText.classList.remove('text-muted');
                    titleText.classList.add('fs-5');
                    if (!titleText.classList.contains('strong')) {
                        titleText.classList.add('strong');
                    }
                }
            } else {
                if (titleText) {
                    titleText.textContent = 'غير محدد';
                    titleText.classList.add('text-muted');
                    titleText.classList.remove('fs-5', 'strong');
                }
            }
            if (titleText) titleText.classList.remove('d-none');
            titleInput.classList.add('d-none');
            if (titleActions) titleActions.classList.add('d-none');
            if (editBtn) editBtn.style.display = 'inline-block';
            
            showSuccessMessage('تم حفظ العنوان بنجاح');
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('خطأ: ' + (data.error || 'فشل حفظ العنوان'));
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء حفظ العنوان: ' + error.message);
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

var gregorianYearEditActive = false;
var gregorianYearOriginal = '{{ $asset->gregorian_year ?? '' }}';
function toggleEditGregorianYear() {
    if (productionDateEditActive) cancelEditProductionDate();
    var textEl = document.getElementById('gregorianYearText');
    var inputEl = document.getElementById('gregorianYearInput');
    var actions = document.getElementById('gregorianYearActions');
    var editBtn = document.getElementById('editGregorianYearBtn');
    if (!gregorianYearEditActive) {
        if (textEl) textEl.classList.add('d-none');
        if (inputEl) { inputEl.classList.remove('d-none'); inputEl.focus(); }
        if (actions) actions.classList.remove('d-none');
        if (editBtn) editBtn.classList.add('d-none');
        gregorianYearEditActive = true;
    }
}
function cancelEditGregorianYear() {
    var textEl = document.getElementById('gregorianYearText');
    var inputEl = document.getElementById('gregorianYearInput');
    var actions = document.getElementById('gregorianYearActions');
    var editBtn = document.getElementById('editGregorianYearBtn');
    if (inputEl) inputEl.value = gregorianYearOriginal;
    if (textEl) textEl.classList.remove('d-none');
    if (inputEl) inputEl.classList.add('d-none');
    if (actions) actions.classList.add('d-none');
    if (editBtn) editBtn.classList.remove('d-none');
    gregorianYearEditActive = false;
}
function saveGregorianYear(assetId) {
    var inputEl = document.getElementById('gregorianYearInput');
    var val = inputEl ? inputEl.value.trim() : '';
    var year = val === '' ? null : parseInt(val, 10);
    if (year !== null && (year < 1900 || year > 2100)) {
        alert('السنة يجب أن تكون بين 1900 و 2100');
        return;
    }
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) { alert('خطأ: لم يتم العثور على CSRF token'); return; }
    fetch('/assets/' + assetId + '/update-gregorian-year', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken.getAttribute('content'), 'Accept': 'application/json' },
        body: JSON.stringify({ gregorian_year: year })
    })
    .then(function(r) {
        if (!r.ok) return r.json().then(function(d) { throw new Error(d.error || 'HTTP ' + r.status); });
        return r.json();
    })
    .then(function(data) {
        if (data.success) {
            gregorianYearOriginal = year ? String(year) : '';
            var textEl = document.getElementById('gregorianYearText');
            if (textEl) {
                textEl.innerHTML = year ? '<span class="badge bg-success">' + year + '</span>' : '<span class="text-muted">غير محدد</span>';
            }
            if (inputEl) inputEl.classList.add('d-none');
            document.getElementById('gregorianYearText').classList.remove('d-none');
            document.getElementById('gregorianYearActions').classList.add('d-none');
            document.getElementById('editGregorianYearBtn').classList.remove('d-none');
            gregorianYearEditActive = false;
            showSuccessMessage('تم حفظ السنة الميلادية بنجاح');
        } else {
            alert('خطأ: ' + (data.error || 'فشل الحفظ'));
        }
    })
    .catch(function(err) {
        alert('حدث خطأ: ' + err.message);
    });
}

function saveShowTranslation(assetId, checked) {
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) { alert('خطأ: لم يتم العثور على CSRF token'); return; }
    fetch('/assets/' + assetId + '/update-show-translation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken.getAttribute('content'), 'Accept': 'application/json' },
        body: JSON.stringify({ show_translation: !!checked })
    })
    .then(function(r) {
        if (!r.ok) return r.json().then(function(d) { throw new Error(d.error || 'HTTP ' + r.status); });
        return r.json();
    })
    .then(function(data) {
        if (data.success) {
            if (typeof showSuccessMessage === 'function') showSuccessMessage(data.message || (checked ? 'سيتم إظهار الترجمة على صفحة الفيديو' : 'تم إخفاء الترجمة'));
            else alert(data.message);
        } else {
            alert('خطأ: ' + (data.error || 'فشل الحفظ'));
        }
    })
    .catch(function(err) {
        alert('حدث خطأ: ' + err.message);
        document.getElementById('showTranslationCheck').checked = !checked;
    });
}

function saveShowComments(assetId, checked) {
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) { alert('خطأ: لم يتم العثور على CSRF token'); return; }
    fetch('/assets/' + assetId + '/update-show-comments', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken.getAttribute('content'), 'Accept': 'application/json' },
        body: JSON.stringify({ show_comments: !!checked })
    })
    .then(function(r) {
        if (!r.ok) return r.json().then(function(d) { throw new Error(d.error || 'HTTP ' + r.status); });
        return r.json();
    })
    .then(function(data) {
        if (data.success) {
            if (typeof showSuccessMessage === 'function') showSuccessMessage(data.message || (checked ? 'سيتم إظهار التعليقات على صفحة الفيديو' : 'تم إخفاء التعليقات'));
            else alert(data.message);
        } else {
            alert('خطأ: ' + (data.error || 'فشل الحفظ'));
        }
    })
    .catch(function(err) {
        alert('حدث خطأ: ' + err.message);
        document.getElementById('showCommentsCheck').checked = !checked;
    });
}

var productionDateEditActive = false;
var productionDateOriginal = '{{ $asset->production_date?->format("Y-m-d") ?? "" }}';
function toggleEditProductionDate() {
    if (gregorianYearEditActive) cancelEditGregorianYear();
    var textEl = document.getElementById('productionDateText');
    var inputEl = document.getElementById('productionDateInput');
    var actions = document.getElementById('productionDateActions');
    var editBtn = document.getElementById('editProductionDateBtn');
    if (!productionDateEditActive) {
        if (inputEl) { inputEl.value = productionDateOriginal; inputEl.classList.remove('d-none'); inputEl.focus(); }
        if (textEl) textEl.classList.add('d-none');
        if (actions) actions.classList.remove('d-none');
        if (editBtn) editBtn.classList.add('d-none');
        productionDateEditActive = true;
    }
}
function cancelEditProductionDate() {
    var textEl = document.getElementById('productionDateText');
    var inputEl = document.getElementById('productionDateInput');
    var actions = document.getElementById('productionDateActions');
    var editBtn = document.getElementById('editProductionDateBtn');
    if (inputEl) { inputEl.value = productionDateOriginal; inputEl.classList.add('d-none'); }
    if (textEl) textEl.classList.remove('d-none');
    if (actions) actions.classList.add('d-none');
    if (editBtn) editBtn.classList.remove('d-none');
    productionDateEditActive = false;
}
function saveProductionDate(assetId) {
    var inputEl = document.getElementById('productionDateInput');
    var val = inputEl ? inputEl.value.trim() : '';
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) { alert('خطأ: لم يتم العثور على CSRF token'); return; }
    fetch('/assets/' + assetId + '/update-production-date', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken.getAttribute('content'), 'Accept': 'application/json' },
        body: JSON.stringify({ production_date: val || null })
    })
    .then(function(r) {
        if (!r.ok) return r.json().then(function(d) { throw new Error(d.error || 'HTTP ' + r.status); });
        return r.json();
    })
    .then(function(data) {
        if (data.success) {
            productionDateOriginal = data.production_date || '';
            var formatted = data.production_date_formatted || (data.production_date ? formatDateForDisplay(data.production_date) : '');
            var prodTextEl = document.getElementById('productionDateText');
            if (prodTextEl) {
                prodTextEl.innerHTML = formatted ? '<span class="badge bg-info">' + formatted + '</span>' : '<span class="text-muted">غير محدد</span>';
            }
            if (inputEl) inputEl.classList.add('d-none');
            document.getElementById('productionDateText').classList.remove('d-none');
            document.getElementById('productionDateActions').classList.add('d-none');
            document.getElementById('editProductionDateBtn').classList.remove('d-none');
            productionDateEditActive = false;
            showSuccessMessage('تم حفظ تاريخ الإنتاج بنجاح');
        } else {
            alert('خطأ: ' + (data.error || 'فشل الحفظ'));
        }
    })
    .catch(function(err) {
        alert('حدث خطأ: ' + err.message);
    });
}
function formatDateForDisplay(ymd) {
    if (!ymd) return '';
    var p = ymd.split('-');
    if (p.length !== 3) return ymd;
    return p[2] + '/' + p[1] + '/' + p[0];
}

var publishUrlsEditActive = false;
function toggleEditPublishUrls() {
    var textY = document.getElementById('youtubePublishUrlText');
    var inputY = document.getElementById('youtubePublishUrlInput');
    var textS = document.getElementById('soundcloudPublishUrlText');
    var inputS = document.getElementById('soundcloudPublishUrlInput');
    var actions = document.getElementById('publishUrlsActions');
    var editBtn = document.getElementById('editPublishUrlsBtn');
    if (!publishUrlsEditActive) {
        if (textY) textY.classList.add('d-none');
        if (inputY) { inputY.classList.remove('d-none'); inputY.focus(); }
        if (textS) textS.classList.add('d-none');
        if (inputS) inputS.classList.remove('d-none');
        if (actions) actions.classList.remove('d-none');
        if (editBtn) editBtn.classList.add('d-none');
        publishUrlsEditActive = true;
    }
}
function cancelEditPublishUrls() {
    var textY = document.getElementById('youtubePublishUrlText');
    var inputY = document.getElementById('youtubePublishUrlInput');
    var textS = document.getElementById('soundcloudPublishUrlText');
    var inputS = document.getElementById('soundcloudPublishUrlInput');
    var actions = document.getElementById('publishUrlsActions');
    var editBtn = document.getElementById('editPublishUrlsBtn');
    if (inputY) { inputY.value = inputY.getAttribute('data-original') ?? ''; inputY.classList.add('d-none'); }
    if (inputS) { inputS.value = inputS.getAttribute('data-original') ?? ''; inputS.classList.add('d-none'); }
    if (textY) textY.classList.remove('d-none');
    if (textS) textS.classList.remove('d-none');
    if (actions) actions.classList.add('d-none');
    if (editBtn) editBtn.classList.remove('d-none');
    publishUrlsEditActive = false;
}
function savePublishUrls(assetId) {
    var inputY = document.getElementById('youtubePublishUrlInput');
    var inputS = document.getElementById('soundcloudPublishUrlInput');
    var youtube = inputY ? inputY.value.trim() : '';
    var soundcloud = inputS ? inputS.value.trim() : '';
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) { alert('خطأ: لم يتم العثور على CSRF token'); return; }
    fetch('/assets/' + assetId + '/update-publish-urls', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken.getAttribute('content'), 'Accept': 'application/json' },
        body: JSON.stringify({ youtube_publish_url: youtube || null, soundcloud_publish_url: soundcloud || null })
    })
    .then(function(r) {
        if (!r.ok) return r.json().then(function(d) { throw new Error(d.error || 'HTTP ' + r.status); });
        return r.json();
    })
    .then(function(data) {
        if (data.success) {
            var textY = document.getElementById('youtubePublishUrlText');
            var textS = document.getElementById('soundcloudPublishUrlText');
            if (textY) {
                textY.innerHTML = youtube ? '<a href="' + escapeHtml(youtube) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(youtube) + '</a>' : '<span class="text-muted">غير محدد</span>';
            }
            if (textS) {
                textS.innerHTML = soundcloud ? '<a href="' + escapeHtml(soundcloud) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(soundcloud) + '</a>' : '<span class="text-muted">غير محدد</span>';
            }
            if (inputY) { inputY.setAttribute('data-original', youtube); inputY.classList.add('d-none'); }
            if (inputS) { inputS.setAttribute('data-original', soundcloud); inputS.classList.add('d-none'); }
            document.getElementById('youtubePublishUrlText').classList.remove('d-none');
            document.getElementById('soundcloudPublishUrlText').classList.remove('d-none');
            document.getElementById('publishUrlsActions').classList.add('d-none');
            document.getElementById('editPublishUrlsBtn').classList.remove('d-none');
            publishUrlsEditActive = false;
            showSuccessMessage('تم حفظ روابط النشر بنجاح');
        } else {
            alert('خطأ: ' + (data.error || 'فشل الحفظ'));
        }
    })
    .catch(function(err) {
        alert('حدث خطأ: ' + err.message);
    });
}

function showSuccessMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="bi bi-check-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
    `;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 3000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
function showErrorMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 320px; max-width: 90vw;';
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        <i class="bi bi-exclamation-triangle me-2"></i><span class="error-message-text">${escapeHtml(message)}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
    `;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 6000);
}

// زر تحليل المحتوى النصي
document.getElementById('analyzeBtn')?.addEventListener('click', function(e) {
    const btn = this;
    const originalText = btn.innerHTML;
    
    if (!confirm('سيتم إرسال المحتوى النصي إلى DeepSeek API لتحليله. هل تريد المتابعة؟')) {
        return false;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري التحليل...';
    
    // استخدام مسار نسبي لتجنب مشكلة Mixed Content
    const analyzeUrl = '{{ route("assets.analyze", $asset) }}';
    const analyzeUrlRelative = analyzeUrl.replace(/^https?:\/\/[^\/]+/, '');
    fetch(analyzeUrlRelative, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            showErrorMessage(data.error);
            btn.disabled = false;
            btn.innerHTML = originalText;
            return;
        }
        
        if (data.success) {
            // إعادة تحميل الصفحة لعرض النتائج مباشرة بدون رسالة
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('حدث خطأ أثناء تحليل المحتوى. تحقق من الاتصال وحاول مرة أخرى.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});

document.getElementById('extractForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('extractBtn');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الاستخراج...';
    
    // إعادة تفعيل الزر بعد 10 ثوانٍ في حالة فشل الطلب
    setTimeout(function() {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }, 10000);
});

document.getElementById('reExtractMetadataForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('reExtractMetadataBtn');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الاستخراج...';
    
    // إعادة تفعيل الزر بعد 30 ثانية في حالة فشل الطلب
    setTimeout(function() {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }, 30000);
});

document.getElementById('moveForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('moveBtn');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري النقل...';
    
    // إعادة تفعيل الزر بعد 30 ثانية في حالة فشل الطلب
    setTimeout(function() {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }, 30000);
});

// زر تقليل مساحة الملف الأصلي
let optimizeInterval = null;

function addOptimizeTerminalLine(text, className) {
    const el = document.getElementById('optimizeTerminalContent');
    if (!el) return;
    const line = document.createElement('div');
    line.className = (className || 'text-light');
    line.textContent = text;
    el.appendChild(line);
    el.scrollTop = el.scrollHeight;
}
function clearOptimizeTerminal() {
    const el = document.getElementById('optimizeTerminalContent');
    if (el) { el.innerHTML = ''; }
}

document.getElementById('optimizeOriginalBtn')?.addEventListener('click', function(e) {
    const btn = this;
    const qualityEl = document.querySelector('input[name="optimize_quality"]:checked');
    const quality = qualityEl ? qualityEl.value : 'balanced';
    
    if (!confirm('سيتم إنشاء نسخة جديدة محسّنة (لا تُستبدل النسخة الأصلية). النسخة الجديدة ستظهر في جدول "ملفات الفيديو المتاحة". العملية قد تستغرق دقائق. هل تريد المتابعة؟')) {
        return false;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري البدء...';
    
    const progressDiv = document.getElementById('optimizeProgress');
    const terminalDiv = document.getElementById('optimizeTerminalViewer');
    if (progressDiv) progressDiv.style.display = 'block';
    if (terminalDiv) terminalDiv.style.display = 'block';
    
    const progressBar = document.getElementById('optimizeProgressBar');
    const progressText = document.getElementById('optimizeProgressText');
    const progressPercent = document.getElementById('optimizeProgressPercent');
    const progressMessage = document.getElementById('optimizeProgressMessage');
    if (progressBar) progressBar.style.width = '0%';
    if (progressText) progressText.textContent = '0%';
    if (progressPercent) progressPercent.textContent = '0%';
    if (progressMessage) progressMessage.textContent = 'جاري البدء...';
    
    clearOptimizeTerminal();
    addOptimizeTerminalLine('$ بدء عملية تقليل مساحة الملف...', 'text-success');
    
    const optimizeUrl = '{{ route("assets.optimize-original", $asset) }}'.replace(/^https?:\/\/[^\/]+/, '');
    fetch(optimizeUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ quality: quality })
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            showErrorMessage(data.error);
            btn.disabled = false;
            btn.innerHTML = '<span><i class="bi bi-file-earmark-arrow-down me-1"></i>تقليل مساحة الملف الأصلي</span>';
            if (progressDiv) progressDiv.style.display = 'none';
            if (terminalDiv) terminalDiv.style.display = 'none';
            return;
        }
        if (data.success) {
            optimizeInterval = setInterval(checkOptimizeStatus, 2000);
        }
    })
    .catch(err => {
        console.error(err);
        showErrorMessage('حدث خطأ أثناء بدء العملية');
        btn.disabled = false;
        btn.innerHTML = '<span><i class="bi bi-file-earmark-arrow-down me-1"></i>تقليل مساحة الملف الأصلي</span>';
        if (progressDiv) progressDiv.style.display = 'none';
        if (terminalDiv) terminalDiv.style.display = 'none';
    });
});

function checkOptimizeStatus() {
    const btn = document.getElementById('optimizeOriginalBtn');
    const progressDiv = document.getElementById('optimizeProgress');
    const terminalDiv = document.getElementById('optimizeTerminalViewer');
    const statusUrl = '{{ route("assets.optimize-original-status", $asset) }}'.replace(/^https?:\/\/[^\/]+/, '');
    fetch(statusUrl)
        .then(r => r.json())
        .then(data => {
            const progressBar = document.getElementById('optimizeProgressBar');
            const progressText = document.getElementById('optimizeProgressText');
            const progressPercent = document.getElementById('optimizeProgressPercent');
            const progressMessage = document.getElementById('optimizeProgressMessage');
            
            const progress = data.progress || 0;
            if (progressBar) progressBar.style.width = progress + '%';
            if (progressText) progressText.textContent = progress + '%';
            if (progressPercent) progressPercent.textContent = progress + '%';
            if (progressMessage) progressMessage.textContent = data.message || 'جاري المعالجة...';
            
            if (data.log) {
                const el = document.getElementById('optimizeTerminalContent');
                if (el) {
                    el.innerHTML = data.log.split('\n').map(l => '<div class="text-light">' + (l.trim() ? escapeHtml(l) : '&nbsp;') + '</div>').join('');
                    el.scrollTop = el.scrollHeight;
                }
            }
            
            if (data.status === 'completed') {
                clearInterval(optimizeInterval);
                if (progressBar) progressBar.classList.remove('progress-bar-animated');
                addOptimizeTerminalLine('$ تم تقليل مساحة الملف بنجاح', 'text-success');
                setTimeout(() => {
                    if (progressDiv) progressDiv.style.display = 'none';
                    if (terminalDiv) terminalDiv.style.display = 'none';
                    window.location.reload();
                }, 2000);
            }
            if (data.status === 'error') {
                clearInterval(optimizeInterval);
                showErrorMessage(data.message || 'فشلت العملية');
                if (btn) { btn.disabled = false; btn.innerHTML = '<span><i class="bi bi-file-earmark-arrow-down me-1"></i>تقليل مساحة الملف الأصلي</span>'; }
                if (progressDiv) progressDiv.style.display = 'none';
                if (terminalDiv) terminalDiv.style.display = 'none';
            }
        })
        .catch(err => {
            clearInterval(optimizeInterval);
            if (btn) { btn.disabled = false; btn.innerHTML = '<span><i class="bi bi-file-earmark-arrow-down me-1"></i>تقليل مساحة الملف الأصلي</span>'; }
            if (progressDiv) progressDiv.style.display = 'none';
            if (terminalDiv) terminalDiv.style.display = 'none';
        });
}

// تحديد النسخة المعروضة على الويب (ملفات الفيديو المتاحة)
document.querySelectorAll('.web-video-radio').forEach(function(radio) {
    radio.addEventListener('change', function() {
        const relativePath = this.value;
        const setWebVideoUrl = '{{ route("assets.set-web-video", $asset) }}'.replace(/^https?:\/\/[^\/]+/, '');
        const csrf = document.querySelector('meta[name="csrf-token"]');
        if (!csrf) {
            showErrorMessage('خطأ: رمز الأمان غير متوفر');
            return;
        }
        fetch(setWebVideoUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf.getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ relative_path: relativePath })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) {
                showErrorMessage(data.error);
                return;
            }
            const msg = document.createElement('div');
            msg.className = 'alert alert-success alert-dismissible fade show position-fixed';
            msg.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 320px; max-width: 90vw;';
            msg.setAttribute('role', 'alert');
            msg.innerHTML = '<i class="bi bi-check-circle me-2"></i>' + (data.message || 'تم التحديث') + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>';
            document.body.appendChild(msg);
            setTimeout(function() { msg.remove(); }, 4000);
        })
        .catch(function(err) {
            console.error(err);
            showErrorMessage('حدث خطأ أثناء تحديث النسخة المعروضة على الويب');
        });
    });
});

// نشر سريع: تشغيل الخطوات بالترتيب
function setQuickPublishStep(stepNum, state) {
    const el = document.getElementById('qpStep' + stepNum);
    if (!el) return;
    const iconSpan = el.querySelector('.qp-icon');
    if (!iconSpan) return;
    iconSpan.innerHTML = '';
    iconSpan.className = 'qp-icon me-2';
    if (state === 'pending') {
        iconSpan.textContent = '○';
        iconSpan.style.color = '';
    } else if (state === 'running') {
        iconSpan.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
        iconSpan.classList.add('text-primary');
    } else if (state === 'done') {
        iconSpan.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
    } else if (state === 'error') {
        iconSpan.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
    }
}

function rel(url) {
    return (url || '').replace(/^https?:\/\/[^\/]+/, '');
}

document.getElementById('quickPublishBtn')?.addEventListener('click', function() {
    const btn = this;
    if (!confirm('سيتم تشغيل 6 خطوات تلقائياً بالترتيب (نقل المحتوى → استخراج البيانات → استخراج النص → تحليل النص → تقليل حجم الفيديو → استخراج الصوت). العملية قد تستغرق وقتاً طويلاً. هل تريد المتابعة؟')) {
        return;
    }
    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (!csrf) {
        showErrorMessage('خطأ: رمز الأمان غير متوفر');
        return;
    }
    const token = csrf.getAttribute('content');
    const headers = { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' };
    const headersJson = { ...headers, 'Content-Type': 'application/json' };
    btn.disabled = true;

    function fail(stepNum, errMsg) {
        setQuickPublishStep(stepNum, 'error');
        showErrorMessage(errMsg || 'حدث خطأ');
        btn.disabled = false;
    }

    function step1() {
        setQuickPublishStep(1, 'running');
        const fd1 = new FormData();
        fd1.append('_token', token);
        fetch(rel('{{ route("assets.move", $asset) }}'), { method: 'POST', headers: { ...headers, 'X-Requested-With': 'XMLHttpRequest' }, body: fd1 })
            .then(r => r.json().catch(() => ({})))
            .then(data => {
                if (data.error && !data.success && !data.already_moved) {
                    fail(1, data.error);
                    return;
                }
                setQuickPublishStep(1, 'done');
                step2();
            })
            .catch(e => fail(1, e.message));
    }

    function step2() {
        setQuickPublishStep(2, 'running');
        const fd2 = new FormData();
        fd2.append('_token', token);
        fetch(rel('{{ route("assets.extract", $asset) }}'), { method: 'POST', headers: { ...headers, 'X-Requested-With': 'XMLHttpRequest' }, body: fd2 })
            .then(r => r.json().catch(() => ({})))
            .then(data => {
                if (data.error && !data.success) {
                    fail(2, data.error);
                    return;
                }
                setQuickPublishStep(2, 'done');
                step3();
            })
            .catch(e => fail(2, e.message));
    }

    function step3() {
        setQuickPublishStep(3, 'running');
        fetch(rel('{{ route("assets.transcribe", $asset) }}'), { method: 'POST', headers: headersJson, body: JSON.stringify({ model: 'base' }) })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    fail(3, data.error);
                    return;
                }
                pollTranscribe();
            })
            .catch(e => fail(3, e.message));
    }
    function pollTranscribe() {
        fetch(rel('{{ route("assets.transcribe-status", $asset) }}'))
            .then(r => r.json())
            .then(data => {
                if (data.status === 'completed') {
                    setQuickPublishStep(3, 'done');
                    step4();
                } else if (data.status === 'error') {
                    fail(3, data.message || data.error || 'فشل استخراج النص');
                } else {
                    setTimeout(pollTranscribe, 2500);
                }
            })
            .catch(e => fail(3, e.message));
    }

    function step4() {
        setQuickPublishStep(4, 'running');
        fetch(rel('{{ route("assets.analyze", $asset) }}'), { method: 'POST', headers: headersJson, body: '{}' })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    fail(4, data.error);
                    return;
                }
                setQuickPublishStep(4, 'done');
                step5();
            })
            .catch(e => fail(4, e.message));
    }

    function step5() {
        setQuickPublishStep(5, 'running');
        fetch(rel('{{ route("assets.optimize-original", $asset) }}'), { method: 'POST', headers: headersJson, body: JSON.stringify({ quality: 'balanced' }) })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    fail(5, data.error);
                    return;
                }
                pollOptimize();
            })
            .catch(e => fail(5, e.message));
    }
    function pollOptimize() {
        fetch(rel('{{ route("assets.optimize-original-status", $asset) }}'))
            .then(r => r.json())
            .then(data => {
                if (data.status === 'completed') {
                    setQuickPublishStep(5, 'done');
                    step6();
                } else if (data.status === 'error' || data.error) {
                    fail(5, data.message || data.error || 'فشل تقليل الحجم');
                } else {
                    setTimeout(pollOptimize, 2500);
                }
            })
            .catch(e => fail(5, e.message));
    }

    function step6() {
        setQuickPublishStep(6, 'running');
        fetch(rel('{{ route("assets.extract-audio", $asset) }}'), { method: 'POST', headers: headersJson, body: '{}' })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    fail(6, data.error);
                    return;
                }
                pollExtractAudio();
            })
            .catch(e => fail(6, e.message));
    }
    function pollExtractAudio() {
        fetch(rel('{{ route("assets.extract-audio-status", $asset) }}'))
            .then(r => r.json())
            .then(data => {
                if (data.status === 'completed') {
                    setQuickPublishStep(6, 'done');
                    btn.disabled = false;
                    if (typeof showErrorMessage !== 'undefined') {
                        const msg = document.createElement('div');
                        msg.className = 'alert alert-success alert-dismissible fade show position-fixed';
                        msg.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 320px;';
                        msg.innerHTML = '<i class="bi bi-check-circle me-2"></i>تم النشر السريع بنجاح.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        document.body.appendChild(msg);
                        setTimeout(function() { msg.remove(); }, 6000);
                    }
                    setTimeout(function() { window.location.reload(); }, 2000);
                } else if (data.status === 'error' || data.error) {
                    fail(6, data.message || data.error || 'فشل استخراج الصوت');
                } else {
                    setTimeout(pollExtractAudio, 2500);
                }
            })
            .catch(e => fail(6, e.message));
    }

    step1();
});

// زر تحويل إلى HLS
let hlsInterval = null;
let lastHlsLogLineCount = 0;

document.getElementById('convertHlsBtn')?.addEventListener('click', function(e) {
    const btn = this;
    const originalText = btn.innerHTML;
    
    if (!confirm('سيتم تحويل الفيديو إلى HLS بمساحات مختلفة (360p, 480p, 720p). هذه العملية قد تستغرق وقتاً طويلاً. هل تريد المتابعة؟')) {
        return false;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري البدء...';
    
    // إظهار progress bar و Terminal
    document.getElementById('hlsProgress').style.display = 'block';
    document.getElementById('hlsTerminalViewer').style.display = 'block';
    document.getElementById('hlsProgressBar').style.width = '0%';
    document.getElementById('hlsProgressText').textContent = '0%';
    document.getElementById('hlsProgressPercent').textContent = '0%';
    document.getElementById('hlsProgressMessage').textContent = 'جاري البدء...';
    
    // مسح Terminal وإعادة تعيين العداد
    lastHlsLogLineCount = 0;
    clearHlsTerminal();
    addHlsTerminalLine('$ بدء عملية التحويل إلى HLS...', 'text-success');
    
    // استخدام مسار نسبي لتجنب مشكلة Mixed Content
    const convertHlsUrl = '{{ route("assets.convert-hls", $asset) }}';
    const convertHlsUrlRelative = convertHlsUrl.replace(/^https?:\/\/[^\/]+/, '');
    fetch(convertHlsUrlRelative, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('خطأ: ' + data.error);
            btn.disabled = false;
            btn.innerHTML = originalText;
            document.getElementById('hlsProgress').style.display = 'none';
            document.getElementById('hlsTerminalViewer').style.display = 'none';
            return;
        }
        
        if (data.success) {
            // بدء التحقق من الحالة كل ثانيتين
            hlsInterval = setInterval(checkHlsStatus, 2000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء بدء العملية');
        btn.disabled = false;
        btn.innerHTML = originalText;
        document.getElementById('hlsProgress').style.display = 'none';
        document.getElementById('hlsTerminalViewer').style.display = 'none';
    });
});

function checkHlsStatus() {
    // استخدام مسار نسبي لتجنب مشكلة Mixed Content
    const hlsStatusUrl = '{{ route("assets.hls-status", $asset) }}';
    const hlsStatusUrlRelative = hlsStatusUrl.replace(/^https?:\/\/[^\/]+/, '');
    fetch(hlsStatusUrlRelative)
        .then(response => response.json())
        .then(data => {
            const progressBar = document.getElementById('hlsProgressBar');
            const progressText = document.getElementById('hlsProgressText');
            const progressPercent = document.getElementById('hlsProgressPercent');
            const progressMessage = document.getElementById('hlsProgressMessage');
            
            // تحديث Progress Bar
            const progress = data.progress || 0;
            progressBar.style.width = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);
            progressText.textContent = progress + '%';
            progressPercent.textContent = progress + '%';
            progressMessage.textContent = data.message || 'جاري التحويل...';
            
            // عرض Logs في Terminal
            if (data.log_lines && Array.isArray(data.log_lines)) {
                const newLines = data.log_lines.slice(lastHlsLogLineCount);
                newLines.forEach(line => {
                    if (line.trim()) {
                        let className = 'text-light';
                        const lineLower = line.toLowerCase();
                        if (line.includes('ERROR') || line.includes('خطأ') || line.includes('error')) {
                            className = 'text-danger';
                        } else if (line.includes('SUCCESS') || line.includes('✅') || line.includes('تم') || line.includes('success')) {
                            className = 'text-success';
                        } else if (line.includes('INFO') || line.includes('info') || line.includes('جاري') || line.includes('معالجة')) {
                            className = 'text-info';
                        } else if (line.includes('frame=') || line.includes('time=')) {
                            className = 'text-warning';
                        }
                        addHlsTerminalLine(line, className);
                    }
                });
                lastHlsLogLineCount = data.log_lines.length;
                
                // التمرير للأسفل تلقائياً
                const terminalContent = document.getElementById('hlsTerminalContent');
                terminalContent.scrollTop = terminalContent.scrollHeight;
            }
            
            if (data.status === 'completed') {
                clearInterval(hlsInterval);
                progressBar.classList.remove('progress-bar-animated');
                progressBar.style.backgroundColor = '#28a745';
                
                addHlsTerminalLine('✅ تم الانتهاء بنجاح!', 'text-success');
                addHlsTerminalLine('$ تم تحويل الفيديو إلى HLS بنجاح', 'text-success');
                
                // إخفاء progress bar و terminal بعد ثانيتين
                setTimeout(() => {
                    document.getElementById('hlsProgress').style.display = 'none';
                    document.getElementById('hlsTerminalViewer').style.display = 'none';
                }, 2000);
                
                // حذف Cache بعد الانتهاء - استخدام مسار نسبي
                const hlsClearStatusUrl = '{{ route("assets.hls-status", $asset) }}';
                const hlsClearStatusUrlRelative = hlsClearStatusUrl.replace(/^https?:\/\/[^\/]+/, '');
                fetch(hlsClearStatusUrlRelative + '?clear=1')
                    .catch(err => console.error('Error clearing cache:', err));
                
                // إعادة تحميل الصفحة بعد 3 ثوانٍ لعرض الجدول
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else if (data.status === 'error') {
                clearInterval(hlsInterval);
                progressBar.classList.remove('progress-bar-animated');
                progressBar.style.backgroundColor = '#dc3545';
                
                addHlsTerminalLine('❌ حدث خطأ: ' + (data.error || data.message), 'text-danger');
                
                // إخفاء progress bar و terminal بعد 5 ثوانٍ
                setTimeout(() => {
                    document.getElementById('hlsProgress').style.display = 'none';
                    document.getElementById('hlsTerminalViewer').style.display = 'none';
                }, 5000);
                
                // حذف Cache عند الخطأ - استخدام مسار نسبي
                const hlsErrorStatusUrl = '{{ route("assets.hls-status", $asset) }}';
                const hlsErrorStatusUrlRelative = hlsErrorStatusUrl.replace(/^https?:\/\/[^\/]+/, '');
                fetch(hlsErrorStatusUrlRelative + '?clear=1')
                    .catch(err => console.error('Error clearing cache:', err));
                
                const btn = document.getElementById('convertHlsBtn');
                btn.disabled = false;
                btn.innerHTML = 'تحويل فيديو إلى HLS';
            }
        })
        .catch(error => {
            console.error('Error checking status:', error);
            addHlsTerminalLine('❌ خطأ في الاتصال: ' + error.message, 'text-danger');
        });
}

function addHlsTerminalLine(text, className) {
    const terminalContent = document.getElementById('hlsTerminalContent');
    const line = document.createElement('div');
    line.className = (className || 'text-light') + (className && !className.includes('text-') ? ' text-light' : '');
    line.textContent = text;
    terminalContent.appendChild(line);
    
    // التمرير للأسفل تلقائياً
    terminalContent.scrollTop = terminalContent.scrollHeight;
}

function clearHlsTerminal() {
    const terminalContent = document.getElementById('hlsTerminalContent');
    terminalContent.innerHTML = '';
    lastHlsLogLineCount = 0;
}

// زر استخراج الصوت
let audioInterval = null;
let lastAudioLogLineCount = 0;

document.getElementById('extractAudioBtn')?.addEventListener('click', function(e) {
    const btn = this;
    const originalText = btn.innerHTML;
    
    if (!confirm('سيتم استخراج الصوت من الفيديو بصيغة MP3 (مناسبة لـ SoundCloud و Spotify). هذه العملية قد تستغرق وقتاً طويلاً. هل تريد المتابعة؟')) {
        return false;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري البدء...';
    
    // إظهار progress bar و Terminal
    document.getElementById('audioProgress').style.display = 'block';
    document.getElementById('audioTerminalViewer').style.display = 'block';
    document.getElementById('audioProgressBar').style.width = '0%';
    document.getElementById('audioProgressText').textContent = '0%';
    document.getElementById('audioProgressPercent').textContent = '0%';
    document.getElementById('audioProgressMessage').textContent = 'جاري البدء...';
    
    // مسح Terminal وإعادة تعيين العداد
    lastAudioLogLineCount = 0;
    clearAudioTerminal();
    addAudioTerminalLine('$ بدء عملية استخراج الصوت...', 'text-success');
    
    // استخدام مسار نسبي لتجنب مشكلة Mixed Content
    const extractAudioUrl = '{{ route("assets.extract-audio", $asset) }}';
    const extractAudioUrlRelative = extractAudioUrl.replace(/^https?:\/\/[^\/]+/, '');
    fetch(extractAudioUrlRelative, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('خطأ: ' + data.error);
            btn.disabled = false;
            btn.innerHTML = originalText;
            document.getElementById('audioProgress').style.display = 'none';
            document.getElementById('audioTerminalViewer').style.display = 'none';
            return;
        }
        
        if (data.success) {
            // بدء التحقق من الحالة كل ثانيتين
            audioInterval = setInterval(checkAudioStatus, 2000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء بدء العملية');
        btn.disabled = false;
        btn.innerHTML = originalText;
        document.getElementById('audioProgress').style.display = 'none';
        document.getElementById('audioTerminalViewer').style.display = 'none';
    });
});

function checkAudioStatus() {
    // استخدام مسار نسبي لتجنب مشكلة Mixed Content
    const audioStatusUrl = '{{ route("assets.extract-audio-status", $asset) }}';
    const audioStatusUrlRelative = audioStatusUrl.replace(/^https?:\/\/[^\/]+/, '');
    fetch(audioStatusUrlRelative)
        .then(response => response.json())
        .then(data => {
            const progressBar = document.getElementById('audioProgressBar');
            const progressText = document.getElementById('audioProgressText');
            const progressPercent = document.getElementById('audioProgressPercent');
            const progressMessage = document.getElementById('audioProgressMessage');
            
            // تحديث Progress Bar
            const progress = data.progress || 0;
            progressBar.style.width = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);
            progressText.textContent = progress + '%';
            progressPercent.textContent = progress + '%';
            progressMessage.textContent = data.message || 'جاري استخراج الصوت...';
            
            // عرض Logs في Terminal
            if (data.log_lines && Array.isArray(data.log_lines)) {
                const newLines = data.log_lines.slice(lastAudioLogLineCount);
                newLines.forEach(line => {
                    if (line.trim()) {
                        let className = 'text-light';
                        if (line.includes('ERROR') || line.includes('خطأ') || line.includes('error')) {
                            className = 'text-danger';
                        } else if (line.includes('SUCCESS') || line.includes('✅') || line.includes('تم') || line.includes('success')) {
                            className = 'text-success';
                        } else if (line.includes('INFO') || line.includes('info') || line.includes('جاري') || line.includes('معالجة')) {
                            className = 'text-info';
                        } else if (line.includes('frame=') || line.includes('time=') || line.includes('size=')) {
                            className = 'text-warning';
                        }
                        addAudioTerminalLine(line, className);
                    }
                });
                lastAudioLogLineCount = data.log_lines.length;
                
                // التمرير للأسفل تلقائياً
                const terminalContent = document.getElementById('audioTerminalContent');
                terminalContent.scrollTop = terminalContent.scrollHeight;
            }
            
            if (data.status === 'completed') {
                clearInterval(audioInterval);
                progressBar.classList.remove('progress-bar-animated');
                progressBar.style.backgroundColor = '#28a745';
                
                addAudioTerminalLine('✅ تم الانتهاء بنجاح!', 'text-success');
                addAudioTerminalLine('$ تم استخراج الصوت بنجاح', 'text-success');
                
                if (data.audio_size_mb) {
                    addAudioTerminalLine('$ حجم الملف: ' + data.audio_size_mb + ' MB', 'text-info');
                }
                
                if (data.audio_url) {
                    addAudioTerminalLine('$ رابط الملف: ' + data.audio_url, 'text-info');
                    addAudioTerminalLine('$ يمكنك تحميل الملف الآن', 'text-success');
                }
                
                // حذف Cache بعد الانتهاء - استخدام مسار نسبي
                const audioClearStatusUrl = '{{ route("assets.extract-audio-status", $asset) }}';
                const audioClearStatusUrlRelative = audioClearStatusUrl.replace(/^https?:\/\/[^\/]+/, '');
                fetch(audioClearStatusUrlRelative + '?clear=1')
                    .catch(err => console.error('Error clearing cache:', err));
                
                const btn = document.getElementById('extractAudioBtn');
                btn.disabled = false;
                btn.innerHTML = 'تحويل الفيديو إلى ملف صوتي (MP3)';
                
                // إخفاء progress bar و terminal بعد 2 ثانية ثم عمل refresh للصفحة
                setTimeout(() => {
                    document.getElementById('audioProgress').style.display = 'none';
                    document.getElementById('audioTerminalViewer').style.display = 'none';
                    
                    // عمل refresh للصفحة بعد إخفاء progress bar
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }, 2000);
            } else if (data.status === 'error') {
                clearInterval(audioInterval);
                progressBar.classList.remove('progress-bar-animated');
                progressBar.style.backgroundColor = '#dc3545';
                
                addAudioTerminalLine('❌ حدث خطأ: ' + (data.error || data.message), 'text-danger');
                
                // إخفاء progress bar و terminal بعد 5 ثوانٍ
                setTimeout(() => {
                    document.getElementById('audioProgress').style.display = 'none';
                    document.getElementById('audioTerminalViewer').style.display = 'none';
                }, 5000);
                
                // حذف Cache عند الخطأ - استخدام مسار نسبي
                const audioErrorStatusUrl = '{{ route("assets.extract-audio-status", $asset) }}';
                const audioErrorStatusUrlRelative = audioErrorStatusUrl.replace(/^https?:\/\/[^\/]+/, '');
                fetch(audioErrorStatusUrlRelative + '?clear=1')
                    .catch(err => console.error('Error clearing cache:', err));
                
                const btn = document.getElementById('extractAudioBtn');
                btn.disabled = false;
                btn.innerHTML = 'تحويل الفيديو إلى ملف صوتي (MP3)';
            }
        })
        .catch(error => {
            console.error('Error checking status:', error);
            addAudioTerminalLine('❌ خطأ في الاتصال: ' + error.message, 'text-danger');
        });
}

function addAudioTerminalLine(text, className) {
    const terminalContent = document.getElementById('audioTerminalContent');
    const line = document.createElement('div');
    line.className = (className || 'text-light') + (className && !className.includes('text-') ? ' text-light' : '');
    line.textContent = text;
    terminalContent.appendChild(line);
    
    // التمرير للأسفل تلقائياً
    terminalContent.scrollTop = terminalContent.scrollHeight;
}

function clearAudioTerminal() {
    const terminalContent = document.getElementById('audioTerminalContent');
    terminalContent.innerHTML = '';
    lastAudioLogLineCount = 0;
}

let transcriptionInterval = null;

document.getElementById('transcribeBtn').addEventListener('click', function(e) {
    const btn = this;
    const originalText = btn.innerHTML;
    
    if (!confirm('هذه العملية قد تستغرق وقتاً طويلاً. هل تريد المتابعة؟')) {
        return false;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري البدء...';
    
    // إظهار progress bar و Terminal
    document.getElementById('transcribeProgress').style.display = 'block';
    document.getElementById('terminalViewer').style.display = 'block';
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressText').textContent = '0%';
    document.getElementById('progressPercent').textContent = '0%';
    document.getElementById('progressMessage').textContent = 'جاري البدء...';
    
    // مسح Terminal وإعادة تعيين العداد
    lastLogLineCount = 0;
    clearTerminal();
    addTerminalLine('$ بدء عملية الاستخراج...', 'text-success');
    
    // جودة النموذج المختارة (base / small / medium)
    const transcribeQualityEl = document.querySelector('input[name="transcribe_quality"]:checked');
    const transcribeModel = transcribeQualityEl ? transcribeQualityEl.value : 'medium';
    const transcribeUrl = '{{ route("assets.transcribe", $asset) }}';
    const transcribeUrlRelative = transcribeUrl.replace(/^https?:\/\/[^\/]+/, '');
    fetch(transcribeUrlRelative, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ model: transcribeModel })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            let errorMessage = data.error;
            if (data.can_clear) {
                errorMessage += '\n\nهل تريد إعادة تعيين الحالة والبدء من جديد؟';
                if (confirm(errorMessage)) {
                    // حذف الـ cache وإعادة المحاولة - استخدام مسار نسبي
                    const statusUrl = '{{ route("assets.transcribe-status", $asset) }}';
                    const statusUrlRelative = statusUrl.replace(/^https?:\/\/[^\/]+/, '');
                    fetch(statusUrlRelative + '?clear=1')
                        .then(() => {
                            // إعادة المحاولة بعد حذف الـ cache
                            btn.click();
                        })
                        .catch(err => {
                            console.error('Error clearing cache:', err);
                            alert('فشل في إعادة تعيين الحالة. يرجى المحاولة مرة أخرى.');
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        });
                    return;
                }
            } else {
                alert('خطأ: ' + data.error);
            }
            btn.disabled = false;
            btn.innerHTML = originalText;
            document.getElementById('transcribeProgress').style.display = 'none';
            return;
        }
        
        // بدء التحقق من الحالة كل ثانيتين
        transcriptionInterval = setInterval(checkTranscriptionStatus, 2000);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء بدء العملية');
        btn.disabled = false;
        btn.innerHTML = originalText;
        document.getElementById('transcribeProgress').style.display = 'none';
    });
});

let lastLogLineCount = 0;

function checkTranscriptionStatus() {
    // استخدام مسار نسبي لتجنب مشكلة Mixed Content
    const statusUrl = '{{ route("assets.transcribe-status", $asset) }}';
    const statusUrlRelative = statusUrl.replace(/^https?:\/\/[^\/]+/, '');
    fetch(statusUrlRelative)
        .then(response => response.json())
        .then(data => {
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            const progressPercent = document.getElementById('progressPercent');
            const progressMessage = document.getElementById('progressMessage');
            
            // تحديث Progress Bar
            const progress = data.progress || 0;
            progressBar.style.width = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);
            progressText.textContent = progress + '%';
            progressPercent.textContent = progress + '%';
            progressMessage.textContent = data.message || 'جاري المعالجة...';
            
            // عرض Logs في Terminal
            if (data.log_lines && Array.isArray(data.log_lines)) {
                const newLines = data.log_lines.slice(lastLogLineCount);
                newLines.forEach(line => {
                    if (line.trim()) {
                        let className = 'text-light';
                        const lineLower = line.toLowerCase();
                        if (line.includes('ERROR') || line.includes('خطأ') || line.includes('error')) {
                            className = 'text-danger';
                        } else if (line.includes('SUCCESS') || line.includes('✅') || line.includes('تم') || line.includes('success')) {
                            className = 'text-success';
                        } else if (line.includes('INFO') || line.includes('info') || line.includes('جاري') || line.includes('معالجة')) {
                            className = 'text-info';
                        } else if (line.includes('🔄') || line.includes('تحميل')) {
                            className = 'text-warning';
                        }
                        addTerminalLine(line, className);
                    }
                });
                lastLogLineCount = data.log_lines.length;
                
                // التمرير للأسفل تلقائياً
                const terminalContent = document.getElementById('terminalContent');
                terminalContent.scrollTop = terminalContent.scrollHeight;
            }
            
            if (data.status === 'completed') {
                clearInterval(transcriptionInterval);
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.add('bg-success');
                
                addTerminalLine('✅ تم الانتهاء بنجاح!', 'text-success');
                addTerminalLine('$ تم استخراج المحتوى النصي بنجاح', 'text-success');
                if (data.transcription_length) {
                    addTerminalLine('$ عدد الأحرف: ' + data.transcription_length.toLocaleString(), 'text-info');
                }
                addTerminalLine('$ جاري تحديث البيانات...', 'text-info');
                
                // إخفاء progress bar و terminal بعد ثانيتين
                setTimeout(() => {
                    document.getElementById('transcribeProgress').style.display = 'none';
                    document.getElementById('terminalViewer').style.display = 'none';
                }, 2000);
                
                // حذف Cache بعد الانتهاء - استخدام مسار نسبي
                const clearStatusUrl = '{{ route("assets.transcribe-status", $asset) }}';
                const clearStatusUrlRelative = clearStatusUrl.replace(/^https?:\/\/[^\/]+/, '');
                fetch(clearStatusUrlRelative + '?clear=1')
                    .catch(err => console.error('Error clearing cache:', err));
                
                // إعادة تحميل الصفحة بعد 3 ثوانٍ لعرض النص
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else if (data.status === 'error') {
                clearInterval(transcriptionInterval);
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.add('bg-danger');
                
                addTerminalLine('❌ حدث خطأ: ' + (data.error || data.message), 'text-danger');
                
                // إخفاء progress bar و terminal بعد 5 ثوانٍ
                setTimeout(() => {
                    document.getElementById('transcribeProgress').style.display = 'none';
                    document.getElementById('terminalViewer').style.display = 'none';
                }, 5000);
                
                // حذف Cache عند الخطأ - استخدام مسار نسبي
                const errorStatusUrl = '{{ route("assets.transcribe-status", $asset) }}';
                const errorStatusUrlRelative = errorStatusUrl.replace(/^https?:\/\/[^\/]+/, '');
                fetch(errorStatusUrlRelative + '?clear=1')
                    .catch(err => console.error('Error clearing cache:', err));
                
                const btn = document.getElementById('transcribeBtn');
                btn.disabled = false;
                btn.innerHTML = 'استخراج المحتوى النصي';
            }
        })
        .catch(error => {
            console.error('Error checking status:', error);
            addTerminalLine('❌ خطأ في الاتصال: ' + error.message, 'danger');
        });
}

function addTerminalLine(text, className) {
    const terminalContent = document.getElementById('terminalContent');
    const line = document.createElement('div');
    // إضافة className مع text-light كأساس
    line.className = (className || 'text-light') + (className && !className.includes('text-') ? ' text-light' : '');
    line.textContent = text;
    terminalContent.appendChild(line);
    
    // التمرير للأسفل تلقائياً
    terminalContent.scrollTop = terminalContent.scrollHeight;
}

function clearTerminal() {
    const terminalContent = document.getElementById('terminalContent');
    terminalContent.innerHTML = '';
    lastLogLineCount = 0;
}

function copyFolderPath(relativePath, button) {
    // المسار الثابت للمجلد الرئيسي
    const BASE_PATH = '/Users/mohamedabdelrahman/Desktop/2025';
    
    // إزالة اسم الملف من المسار (أخذ المجلد فقط)
    const folderPath = getFolderPath(relativePath);
    
    // بناء المسار الكامل
    const fullPath = BASE_PATH + '/' + folderPath;
    
    // نسخ المسار
    copyToClipboard(fullPath, button);
}

function getFolderPath(relativePath) {
    // إزالة اسم الملف من المسار
    // مثال: "اخري 1447 - 2025/ريلز.الشيخ بدر اليماني عدد13سنة2025/اللهم بحق الذي بيني وبينك.mp4" 
    // -> "اخري 1447 - 2025/ريلز.الشيخ بدر اليماني عدد13سنة2025"
    const parts = relativePath.split('/');
    if (parts.length > 1) {
        // إزالة آخر جزء (اسم الملف)
        parts.pop();
        return parts.join('/');
    }
    // إذا كان الملف في الجذر، نعيد string فارغ
    return '';
}

function copyToClipboard(text, button) {
    // إنشاء input مؤقت لنسخ النص
    const tempInput = document.createElement('input');
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    tempInput.setSelectionRange(0, 99999); // للجوالات
    
    try {
        // نسخ النص
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        
        // تغيير الأيقونة مؤقتاً لإظهار النجاح
        const icon = button.querySelector('i');
        const originalClass = icon.className;
        icon.className = 'bi bi-check-circle-fill';
        button.classList.remove('btn-outline-info');
        button.classList.add('btn-info');
        
        // إظهار رسالة نجاح
        showToast('تم نسخ مسار المجلد بنجاح!', 'success');
        
        // إعادة الأيقونة الأصلية بعد ثانيتين
        setTimeout(() => {
            icon.className = originalClass;
            button.classList.remove('btn-info');
            button.classList.add('btn-outline-info');
        }, 2000);
    } catch (err) {
        document.body.removeChild(tempInput);
        // استخدام Clipboard API كبديل
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('تم نسخ مسار المجلد بنجاح!', 'success');
            }).catch(() => {
                showToast('فشل نسخ المسار', 'error');
            });
        } else {
            showToast('المتصفح لا يدعم نسخ النص', 'error');
        }
    }
}

function showToast(message, type) {
    // إنشاء toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    // إزالة Toast بعد 3 ثواني
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function copyFileUrl() {
    const input = document.getElementById('fileUrlInput');
    if (input) {
        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand('copy');
        showToast('تم نسخ رابط الملف بنجاح!', 'success');
    }
}

function copySiteVideoUrl() {
    const input = document.getElementById('siteVideoUrlInput');
    if (!input) return;
    const text = input.value;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showToast('تم نسخ رابط الموقع بنجاح!', 'success');
        }).catch(function() {
            fallbackCopySiteVideoUrl(input);
        });
    } else {
        fallbackCopySiteVideoUrl(input);
    }
}
function fallbackCopySiteVideoUrl(input) {
    try {
        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand('copy');
        showToast('تم نسخ رابط الموقع بنجاح!', 'success');
    } catch (e) {
        showToast('فشل نسخ الرابط', 'error');
    }
}

// مزامنة الفيديو مع المحتوى النصي
@if(isset($transcriptionSegments) && $transcriptionSegments && $fileUrl)
const transcriptionSegments = @json($transcriptionSegments);
let currentHighlightedIndex = -1;
let captionOverlayEnabled = false;

function toggleCaptionOverlay() {
    const overlay = document.getElementById('captionOverlay');
    const btn = document.getElementById('toggleCaptionOverlayBtn');
    if (!overlay || !btn) return;
    captionOverlayEnabled = !captionOverlayEnabled;
    if (captionOverlayEnabled) {
        overlay.classList.remove('d-none');
        btn.innerHTML = '<i class="bi bi-subtitles"></i> إخفاء النص فوق الفيديو';
        btn.classList.add('active');
        updateCaptionOverlayText();
    } else {
        overlay.classList.add('d-none');
        btn.innerHTML = '<i class="bi bi-subtitles"></i> إظهار النص فوق الفيديو';
        btn.classList.remove('active');
    }
}

function updateCaptionOverlayText() {
    const video = document.getElementById('videoPlayer');
    const overlayEl = document.getElementById('captionOverlay');
    const textEl = document.getElementById('captionOverlayText');
    if (!video || !textEl || !overlayEl || overlayEl.classList.contains('d-none')) return;
    const currentTime = video.currentTime;
    let text = '';
    for (let i = 0; i < transcriptionSegments.length; i++) {
        const seg = transcriptionSegments[i];
        if (currentTime >= seg.start && currentTime <= seg.end) {
            text = (seg.text || '').trim();
            break;
        }
    }
    textEl.textContent = text;
}

function updateTranscriptionHighlight() {
    const video = document.getElementById('videoPlayer');
    if (!video) return;
    
    const currentTime = video.currentTime;
    
    let activeIndex = -1;
    for (let i = 0; i < transcriptionSegments.length; i++) {
        const segment = transcriptionSegments[i];
        if (currentTime >= segment.start && currentTime <= segment.end) {
            activeIndex = i;
            break;
        }
    }
    
    if (captionOverlayEnabled) {
        updateCaptionOverlayText();
    }
    
    if (activeIndex !== currentHighlightedIndex) {
        document.querySelectorAll('.transcription-segment').forEach(seg => {
            seg.style.backgroundColor = '';
            seg.style.color = '';
            seg.style.fontWeight = 'normal';
            seg.style.padding = '';
            seg.style.borderRadius = '';
        });
        
        if (activeIndex >= 0) {
            const activeSegment = document.querySelector(`.transcription-segment[data-index="${activeIndex}"]`);
            if (activeSegment) {
                activeSegment.style.backgroundColor = '#ffc107';
                activeSegment.style.color = '#000';
                activeSegment.style.fontWeight = 'bold';
                activeSegment.style.padding = '2px 4px';
                activeSegment.style.borderRadius = '3px';
                activeSegment.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        currentHighlightedIndex = activeIndex;
    }
}

function seekToTime(time) {
    var video = document.getElementById('videoPlayer');
    if (!video) return;
    var t = parseFloat(String(time).replace(',', '.'), 10);
    if (isNaN(t) || t < 0) t = 0;
    
    function runSeek() {
        video.pause();
        video.currentTime = t;
        var seeked = false;
        function onSeeked() {
            if (seeked) return;
            seeked = true;
            video.currentTime = t;
            video.play().catch(function() {});
        }
        video.addEventListener('seeked', onSeeked, { once: true });
        setTimeout(function() {
            if (!seeked) {
                seeked = true;
                video.currentTime = t;
                video.play().catch(function() {});
            }
        }, 400);
    }
    
    if (video.readyState >= 1) {
        runSeek();
    } else {
        video.addEventListener('loadedmetadata', runSeek, { once: true });
        video.addEventListener('loadeddata', runSeek, { once: true });
        setTimeout(runSeek, 500);
    }
}

@endif
// شاشة منفصلة: معاينة الملف + المحتوى النصي (تكبير) — متاحة عند وجود فيديو ومحتوى نصي
@if($fileUrl && $asset->transcription)
function openPreviewFullscreen() {
    var modalEl = document.getElementById('previewFullscreenModal');
    if (!modalEl) return;
    var modal = window.bootstrap && bootstrap.Modal ? new bootstrap.Modal(modalEl) : null;
    if (modal) {
        modalEl.addEventListener('shown.bs.modal', function onShown() {
            modalEl.removeEventListener('shown.bs.modal', onShown);
            var videoFs = document.getElementById('videoPlayerFullscreen');
            if (videoFs) {
                videoFs.ontimeupdate = updateTranscriptionHighlightFullscreen;
            }
            document.querySelectorAll('#previewFullscreenModal .transcription-segment-row-fullscreen').forEach(function(row) {
                row.onclick = function() {
                    var start = parseFloat(row.getAttribute('data-start'), 10);
                    if (!isNaN(start)) seekToTimeFullscreen(start);
                };
            });
        }, { once: true });
        modal.show();
    }
}

function seekToTimeFullscreen(seconds) {
    var video = document.getElementById('videoPlayerFullscreen');
    if (!video) return;
    var t = parseFloat(String(seconds).replace(',', '.'), 10);
    if (isNaN(t) || t < 0) t = 0;
    video.currentTime = t;
    video.play().catch(function() {});
}

var currentHighlightedIndexFullscreen = -1;
function updateTranscriptionHighlightFullscreen() {
    var video = document.getElementById('videoPlayerFullscreen');
    if (!video) return;
    var currentTime = video.currentTime;
    var rows = document.querySelectorAll('.transcription-segment-row-fullscreen');
    var activeIndex = -1;
    rows.forEach(function(row, i) {
        var start = parseFloat(row.getAttribute('data-start'), 10);
        var end = parseFloat(row.getAttribute('data-end'), 10);
        if (!isNaN(start) && !isNaN(end) && currentTime >= start && currentTime <= end) {
            activeIndex = i;
        }
    });
    if (activeIndex !== currentHighlightedIndexFullscreen) {
        rows.forEach(function(row) {
            row.style.backgroundColor = '';
            row.style.color = '';
        });
        if (activeIndex >= 0 && rows[activeIndex]) {
            rows[activeIndex].style.backgroundColor = '#ffc107';
            rows[activeIndex].style.color = '#000';
            rows[activeIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        currentHighlightedIndexFullscreen = activeIndex;
    }
}
@endif
@if(isset($transcriptionSegments) && $transcriptionSegments && $fileUrl)

/**
 * قراءة التوقيت من حقل "اذهب للتوقيت" (مثل 2:30 أو 1:02:30 أو 150 ثانية) وتحويله لثواني ثم الانتقال.
 */
function seekToTimeFromInput() {
    var input = document.getElementById('seekTimeInput');
    if (!input) return;
    var raw = (input.value || '').trim().replace(/,/g, '.');
    if (!raw) return;
    var parts = raw.split(/[:\u060C]/).map(function(p) { return parseFloat(p, 10); });
    var seconds = NaN;
    if (parts.length === 1) {
        seconds = parts[0];
    } else if (parts.length === 2) {
        seconds = (parts[0] || 0) * 60 + (parts[1] || 0);
    } else if (parts.length >= 3) {
        seconds = (parts[0] || 0) * 3600 + (parts[1] || 0) * 60 + (parts[2] || 0);
    }
    if (isNaN(seconds) || seconds < 0) return;
    seekToTime(seconds);
}

// دالة لتحويل الوقت من ثواني إلى تنسيق mm:ss.ms
function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    const milliseconds = Math.floor((seconds % 1) * 100);
    return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}.${milliseconds.toString().padStart(2, '0')}`;
}

// تنسيق الوقت لصيغة SBV: H:MM:SS.mmm (مثال: 0:00:00.040)
function formatTimeSBV(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    const ms = Math.round((seconds % 1) * 1000);
    return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}.${String(ms).padStart(3, '0')}`;
}

// بناء محتوى SBV: سطر توقيت (بداية،نهاية) ثم النص ثم سطر فارغ
function buildTranscriptionSBV() {
    if (typeof transcriptionSegments === 'undefined' || !transcriptionSegments || transcriptionSegments.length === 0) {
        return null;
    }
    const lines = [];
    transcriptionSegments.forEach((segment) => {
        const startStr = formatTimeSBV(segment.start);
        const endStr = formatTimeSBV(segment.end);
        lines.push(startStr + ',' + endStr);
        const text = (segment.text || '').trim();
        if (text) lines.push(text);
        lines.push('');
    });
    return lines.join('\n').trim();
}

// دالة لتحويل transcriptionSegments إلى نص مع الوقت
function buildTranscriptionWithTimestamps() {
    if (typeof transcriptionSegments === 'undefined' || !transcriptionSegments || transcriptionSegments.length === 0) {
        return null;
    }
    
    let textWithTimestamps = '';
    transcriptionSegments.forEach((segment, index) => {
        const startTime = formatTime(segment.start);
        const endTime = formatTime(segment.end);
        const text = (segment.text || '').trim();
        textWithTimestamps += `[${startTime} - ${endTime}] ${text}\n`;
    });
    
    return textWithTimestamps.trim();
}

function downloadTranscriptionText() {
    var content = '';
    var filename = (typeof assetTranscriptionFilename === 'string' ? assetTranscriptionFilename : 'transcription');
    var ext = 'txt';
    if (typeof transcriptionSegments !== 'undefined' && transcriptionSegments && transcriptionSegments.length > 0) {
        content = buildTranscriptionSBV();
        ext = 'sbv';
        if (!content) content = buildTranscriptionWithTimestamps() || '';
    }
    if (!content) {
        var view1 = document.getElementById('transcriptionTextView');
        var view2 = document.getElementById('transcriptionTextView2');
        var el = view1 || view2;
        if (el) content = (el.innerText || el.textContent || '').trim();
    }
    if (!content) {
        alert('لا يوجد محتوى نصي لتحميله.');
        return;
    }
    filename = filename + (ext === 'sbv' ? '_نص.sbv' : '_نص.txt');
    var blob = new Blob(['\ufeff' + content], { type: 'text/plain; charset=utf-8' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    a.click();
    URL.revokeObjectURL(a.href);
}

@if(isset($translationLanguages) && ($asset->transcription || (isset($transcriptionSegments) && $transcriptionSegments)))
var adminTranscriptionAssetId = {{ $asset->id }};
var adminTranslateTranscriptionUrlTemplate = '{{ route("assets.translate-transcription", ["asset" => ":id"]) }}';
var adminDownloadTranscriptionBase = '{{ url("/video/" . $asset->id . "/download-transcription") }}';
var adminTranslationLanguagesToTranslate = [
    @foreach($translationLanguages as $code => $name)
    @if(empty(($asset->translation_segments ?? [])[$code]))
    { code: '{{ $code }}', name: '{{ addslashes($name) }}' },
    @endif
    @endforeach
];
function adminSetTranscriptionLang(lang) {
    document.querySelectorAll('.admin-lang-tab').forEach(function(btn) {
        btn.classList.toggle('active', btn.getAttribute('data-lang') === lang);
    });
    var link = document.getElementById('adminDownloadTranscriptionLink');
    if (link) link.href = adminDownloadTranscriptionBase + '?lang=' + encodeURIComponent(lang);
    var container = document.getElementById('transcriptionContainer');
    document.querySelectorAll('.admin-transcription-lang-content').forEach(function(el) {
        var isActive = el.getAttribute('data-lang') === lang;
        el.classList.toggle('d-none', !isActive);
    });
    if (container) {
        container.style.direction = (lang === 'ar' || lang === 'ur') ? 'rtl' : 'ltr';
        container.style.textAlign = (lang === 'ar' || lang === 'ur') ? 'right' : 'left';
    }
}
function adminApplyTranslationUI(lang, name, segments) {
    var tabs = document.getElementById('adminTranscriptionLangTabs');
    if (tabs) {
        var newBtn = document.createElement('button');
        newBtn.type = 'button';
        newBtn.className = 'btn btn-sm btn-outline-secondary admin-lang-tab';
        newBtn.setAttribute('data-lang', lang);
        newBtn.textContent = name;
        newBtn.onclick = function() { adminSetTranscriptionLang(lang); };
        tabs.appendChild(newBtn);
    }
    var segs = segments || [];
    if (segs.length) {
        var container = document.getElementById('transcriptionContainer');
        if (container) {
            var div = document.createElement('div');
            div.id = 'adminTranscriptionContent' + lang;
            div.className = 'admin-transcription-lang-content d-none';
            div.setAttribute('data-lang', lang);
            div.style.textAlign = 'left';
            div.style.direction = 'ltr';
            var table = document.createElement('table');
            table.className = 'table table-sm table-hover mb-0';
            table.innerHTML = '<thead class="table-light sticky-top"><tr><th style="width:140px">التوقيت</th><th>الجملة</th></tr></thead><tbody></tbody>';
            var tbody = table.querySelector('tbody');
            segs.forEach(function(seg) {
                var start = seg.start || 0, end = seg.end || 0, text = (seg.text || '').trim();
                var fmt = function(s) {
                    var h = Math.floor(s/3600), m = Math.floor((s%3600)/60), sec = Math.floor(s%60);
                    return h + ':' + String(m).padStart(2,'0') + ':' + String(sec).padStart(2,'0');
                };
                var tr = document.createElement('tr');
                var td1 = document.createElement('td');
                td1.className = 'text-nowrap align-top text-muted small';
                td1.textContent = fmt(start) + ' – ' + fmt(end);
                var td2 = document.createElement('td');
                td2.textContent = text;
                tr.appendChild(td1);
                tr.appendChild(td2);
                tbody.appendChild(tr);
            });
            var wrap = document.createElement('div');
            wrap.className = 'table-responsive';
            wrap.appendChild(table);
            div.appendChild(wrap);
            container.appendChild(div);
        }
    }
    adminSetTranscriptionLang(lang);
    var triggerBtn = document.querySelector('.admin-translate-btn[data-lang="' + lang + '"]');
    if (triggerBtn) triggerBtn.remove();
}

function adminTranslateOne(assetId, lang, name) {
    var formData = new FormData();
    formData.append('lang', lang);
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var token = csrf ? csrf.getAttribute('content') : '';
    formData.append('_token', token);
    var url = (typeof adminTranslateTranscriptionUrlTemplate !== 'undefined')
        ? adminTranslateTranscriptionUrlTemplate.replace(':id', String(assetId))
        : ('/video/' + assetId + '/translate-transcription');
    return fetch(url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token
        }
    })
    .then(function(r) {
        var ct = r.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            if (r.status === 401 || r.status === 419 || r.status === 302) {
                throw new Error('انتهت الجلسة أو تحتاج لتسجيل الدخول. يرجى تحديث الصفحة وتسجيل الدخول ثم المحاولة.');
            }
            if (r.status >= 500) {
                throw new Error('خطأ من الخادم. جرّب لاحقاً.');
            }
            throw new Error('استجابة غير متوقعة من الخادم. تأكد من تسجيل الدخول وحاول مرة أخرى.');
        }
        return r.json();
    })
    .then(function(data) {
        if (data.success) {
            adminApplyTranslationUI(lang, name, data.segments || []);
            return;
        }
        throw new Error(data.error || 'فشل في الترجمة');
    });
}

function adminTranslateTranscription(assetId, btnEl) {
    var lang = btnEl.getAttribute('data-lang');
    var name = btnEl.getAttribute('data-name') || lang;
    var loadingEl = document.getElementById('adminTranscriptionTranslateLoading');
    var modalEl = document.getElementById('translateLoadingModal');
    var modalTitle = document.getElementById('adminTranslateLoadingModalTitle');
    var modalSub = document.getElementById('adminTranslateLoadingModalSubtitle');
    var btns = document.querySelectorAll('.admin-translate-btn');
    var btnAll = document.querySelector('.admin-translate-all-btn');
    if (loadingEl) loadingEl.classList.remove('d-none');
    if (modalEl) modalEl.style.display = 'flex';
    if (modalTitle) modalTitle.textContent = 'جاري الترجمة...';
    if (modalSub) modalSub.textContent = 'قد يستغرق ذلك دقيقة';
    btns.forEach(function(b) { b.disabled = true; });
    if (btnAll) btnAll.disabled = true;
    adminTranslateOne(assetId, lang, name)
    .then(function() {
        if (loadingEl) loadingEl.classList.add('d-none');
        if (modalEl) modalEl.style.display = 'none';
        btns.forEach(function(b) { b.disabled = false; });
        if (btnAll) btnAll.disabled = false;
        if (typeof showSuccessMessage === 'function') showSuccessMessage('تمت الترجمة بنجاح');
        else alert('تمت الترجمة بنجاح');
    })
    .catch(function(err) {
        if (loadingEl) loadingEl.classList.add('d-none');
        if (modalEl) modalEl.style.display = 'none';
        btns.forEach(function(b) { b.disabled = false; });
        if (btnAll) btnAll.disabled = false;
        alert(err.message || 'فشل في الترجمة');
    });
}

async function adminTranslateAllLanguages() {
    var list = adminTranslationLanguagesToTranslate || [];
    if (list.length === 0) {
        alert('جميع اللغات مترجمة بالفعل');
        return;
    }
    var assetId = adminTranscriptionAssetId;
    var modalEl = document.getElementById('translateLoadingModal');
    var modalTitle = document.getElementById('adminTranslateLoadingModalTitle');
    var modalSub = document.getElementById('adminTranslateLoadingModalSubtitle');
    var btns = document.querySelectorAll('.admin-translate-btn');
    var btnAll = document.querySelector('.admin-translate-all-btn');
    btns.forEach(function(b) { b.disabled = true; });
    if (btnAll) btnAll.disabled = true;
    if (modalEl) modalEl.style.display = 'flex';
    var total = list.length;
    for (var i = 0; i < list.length; i++) {
        var item = list[i];
        if (modalTitle) modalTitle.textContent = 'جاري الترجمة...';
        if (modalSub) modalSub.textContent = 'جاري الترجمة إلى ' + item.name + ' (' + (i + 1) + '/' + total + ')';
        try {
            await adminTranslateOne(assetId, item.code, item.name);
            adminTranslationLanguagesToTranslate = adminTranslationLanguagesToTranslate.filter(function(x) { return x.code !== item.code; });
        } catch (err) {
            alert(err.message || 'فشل في الترجمة إلى ' + item.name);
            break;
        }
    }
    if (modalEl) modalEl.style.display = 'none';
    btns.forEach(function(b) { b.disabled = false; });
    if (btnAll) btnAll.disabled = false;
}
@endif

(function() {
    var srtInput = document.getElementById('srtFileInput');
    if (!srtInput) return;
    srtInput.addEventListener('change', function() {
        var file = this.files && this.files[0];
        if (!file) return;
        var formData = new FormData();
        formData.append('srt_file', file);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        var url = '{{ route("assets.upload-transcription-srt", $asset) }}';
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.onload = function() {
            srtInput.value = '';
            var res = null;
            try { res = JSON.parse(xhr.responseText); } catch (e) {}
            if (xhr.status >= 200 && xhr.status < 300 && res && res.success) {
                if (typeof showSuccessMessage === 'function') showSuccessMessage(res.message || 'تم رفع الملف بنجاح.');
                setTimeout(function() { window.location.reload(); }, 800);
            } else {
                alert(res && res.error ? res.error : 'فشل رفع الملف.');
            }
        };
        xhr.onerror = function() {
            srtInput.value = '';
            alert('حدث خطأ أثناء الرفع.');
        };
        xhr.send(formData);
    });
})();

function toggleEditTranscription() {
    const textView1 = document.getElementById('transcriptionTextView');
    const textView2 = document.getElementById('transcriptionTextView2');
    const textarea1 = document.getElementById('transcriptionTextarea');
    const textarea2 = document.getElementById('transcriptionTextarea2');
    const segmentsView = document.getElementById('transcriptionSegmentsView');
    const segmentsEdit = document.getElementById('transcriptionSegmentsEdit');
    const actions1 = document.getElementById('transcriptionActions');
    const actions2 = document.getElementById('transcriptionActions2');
    const editBtn1 = document.getElementById('editTranscriptionBtn');
    const editBtn2 = document.getElementById('editTranscriptionBtn2');
    
    const textarea = textarea1 || textarea2;
    const textView = textView1 || textView2;
    const actions = actions1 || actions2;
    const editBtn = editBtn1 || editBtn2;
    
    if (segmentsEdit && !segmentsEdit.classList.contains('d-none')) {
        return;
    }
    if (segmentsView && segmentsEdit) {
        segmentsView.classList.add('d-none');
        segmentsEdit.classList.remove('d-none');
        if (actions) actions.classList.remove('d-none');
        if (editBtn) editBtn.style.display = 'none';
        const firstInput = segmentsEdit.querySelector('.segment-text-input');
        if (firstInput) firstInput.focus();
        return;
    }
    
    if (textarea && textarea.classList.contains('d-none')) {
        const textWithTimestamps = buildTranscriptionWithTimestamps();
        if (textWithTimestamps) {
            originalTranscription = textarea.value;
            textarea.value = textWithTimestamps;
        } else {
            originalTranscription = textarea.value;
        }
        
        if (textView) textView.classList.add('d-none');
        if (segmentsView) segmentsView.classList.add('d-none');
        textarea.classList.remove('d-none');
        if (actions) actions.classList.remove('d-none');
        if (editBtn) editBtn.style.display = 'none';
        textarea.focus();
        updateTranscriptionCharCount();
    }
}

function cancelEditTranscription() {
    const textarea1 = document.getElementById('transcriptionTextarea');
    const textarea2 = document.getElementById('transcriptionTextarea2');
    const textView1 = document.getElementById('transcriptionTextView');
    const textView2 = document.getElementById('transcriptionTextView2');
    const segmentsView = document.getElementById('transcriptionSegmentsView');
    const segmentsEdit = document.getElementById('transcriptionSegmentsEdit');
    const actions1 = document.getElementById('transcriptionActions');
    const actions2 = document.getElementById('transcriptionActions2');
    const editBtn1 = document.getElementById('editTranscriptionBtn');
    const editBtn2 = document.getElementById('editTranscriptionBtn2');
    
    const textarea = textarea1 || textarea2;
    const textView = textView1 || textView2;
    const actions = actions1 || actions2;
    const editBtn = editBtn1 || editBtn2;
    
    if (segmentsEdit && !segmentsEdit.classList.contains('d-none')) {
        segmentsEdit.classList.add('d-none');
        if (segmentsView) segmentsView.classList.remove('d-none');
        if (actions) actions.classList.add('d-none');
        if (editBtn) editBtn.style.display = 'inline-block';
        return;
    }
    
    if (textarea) {
        textarea.value = originalTranscription;
        textarea.classList.add('d-none');
        if (textView) textView.classList.remove('d-none');
        if (segmentsView) segmentsView.classList.remove('d-none');
        if (actions) actions.classList.add('d-none');
        if (editBtn) editBtn.style.display = 'inline-block';
        updateTranscriptionCharCount();
    }
}

function updateTranscriptionCharCount() {
    const textarea1 = document.getElementById('transcriptionTextarea');
    const textarea2 = document.getElementById('transcriptionTextarea2');
    const charCount1 = document.getElementById('transcriptionCharCount');
    const charCount2 = document.getElementById('transcriptionCharCount2');
    
    const textarea = textarea1 || textarea2;
    const charCount = charCount1 || charCount2;
    
    if (textarea && charCount) {
        const count = textarea.value.length;
        charCount.textContent = count.toLocaleString();
    }
}

function saveTranscription(assetId) {
    const textarea1 = document.getElementById('transcriptionTextarea');
    const textarea2 = document.getElementById('transcriptionTextarea2');
    const textView1 = document.getElementById('transcriptionTextView');
    const textView2 = document.getElementById('transcriptionTextView2');
    const segmentsView = document.getElementById('transcriptionSegmentsView');
    const segmentsEdit = document.getElementById('transcriptionSegmentsEdit');
    const actions1 = document.getElementById('transcriptionActions');
    const actions2 = document.getElementById('transcriptionActions2');
    const editBtn1 = document.getElementById('editTranscriptionBtn');
    const editBtn2 = document.getElementById('editTranscriptionBtn2');
    const charCount1 = document.getElementById('transcriptionCharCount');
    const charCount2 = document.getElementById('transcriptionCharCount2');
    
    const textarea = textarea1 || textarea2;
    const textView = textView1 || textView2;
    const actions = actions1 || actions2;
    const editBtn = editBtn1 || editBtn2;
    
    const saveBtn = event && event.target ? event.target : document.querySelector('#transcriptionActions button.btn-success, #transcriptionActions2 button.btn-success');
    const originalText = saveBtn ? saveBtn.innerHTML : '';
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        alert('خطأ: لم يتم العثور على CSRF token');
        if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = originalText; }
        return;
    }
    
    // حفظ من وضع التعديل بالـ segments (جدول التوقيت + الجملة)
    if (segmentsEdit && !segmentsEdit.classList.contains('d-none')) {
        const rows = segmentsEdit.querySelectorAll('#transcriptionSegmentsEditBody tr');
        const segments = [];
        rows.forEach(tr => {
            const start = parseFloat(tr.getAttribute('data-start'));
            const end = parseFloat(tr.getAttribute('data-end'));
            const input = tr.querySelector('.segment-text-input');
            const text = input ? input.value.trim() : '';
            segments.push({ start: start, end: end, text: text });
        });
        
        fetch(`/assets/${assetId}/update-transcription-segments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ segments: segments })
        })
        .then(response => {
            if (!response.ok) return response.json().then(data => { throw new Error(data.error || response.status); });
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('تم حفظ المحتوى النصي بنجاح');
                window.location.reload();
            } else throw new Error(data.error || 'فشل الحفظ');
        })
        .catch(err => {
            alert(err.message || 'فشل حفظ المحتوى النصي');
            if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = originalText; }
        });
        return;
    }
    
    if (!textarea) {
        if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = originalText; }
        alert('خطأ: لم يتم العثور على حقل النص');
        return;
    }
    
    let transcription = textarea.value;
    if (!transcription || transcription.trim().length === 0) {
        if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = originalText; }
        alert('المحتوى النصي فارغ');
        return;
    }
    
    const timestampPattern = /\[\d{1,2}:\d{2}\.\d{2}\s*-\s*\d{1,2}:\d{2}\.\d{2}\]\s*/g;
    const hasTimestamps = timestampPattern.test(transcription);
    if (hasTimestamps) {
        transcription = transcription.replace(timestampPattern, '');
    }
    transcription = transcription.replace(/\n{3,}/g, '\n\n').trim();
    
    fetch(`/assets/${assetId}/update-transcription`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            transcription: transcription
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // تحديث النص في الواجهة فوراً قبل إعادة التحميل
            if (textView) {
                textView.textContent = transcription;
                textView.classList.remove('d-none');
            }
            if (segmentsView) {
                segmentsView.classList.add('d-none');
            }
            textarea.classList.add('d-none');
            if (actions) actions.classList.add('d-none');
            if (editBtn) editBtn.style.display = 'inline-block';
            
            // تحديث عدد الأحرف
            if (charCount1) {
                charCount1.textContent = transcription.length.toLocaleString();
            }
            if (charCount2) {
                charCount2.textContent = transcription.length.toLocaleString();
            }
            
            // إظهار رسالة نجاح
            showSuccessMessage('تم حفظ المحتوى النصي بنجاح');
            
            // إعادة تحميل الصفحة لتحديث البيانات من قاعدة البيانات
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            alert('خطأ: ' + (data.error || 'فشل حفظ المحتوى النصي'));
            if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = originalText; }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء حفظ المحتوى النصي: ' + error.message);
        if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = originalText; }
    });
}

// تحديث عدد الأحرف عند الكتابة
document.addEventListener('DOMContentLoaded', function() {
    const textarea1 = document.getElementById('transcriptionTextarea');
    const textarea2 = document.getElementById('transcriptionTextarea2');
    
    if (textarea1) {
        textarea1.addEventListener('input', updateTranscriptionCharCount);
    }
    if (textarea2) {
        textarea2.addEventListener('input', updateTranscriptionCharCount);
    }
});

// وظائف تعديل العنوان (تم تعريفها مسبقاً في السطر 1308)


@endif

// وظائف تعديل تصنيفات المحتوى (many-to-many مع كروت)
let originalContentCategoryIds = [];

function toggleEditContentCategory() {
    const contentCategoryBadge = document.getElementById('contentCategoryBadge');
    const contentCategoryCards = document.getElementById('contentCategoryCards');
    const contentCategoryActions = document.getElementById('contentCategoryActions');
    const editBtn = document.getElementById('editContentCategoryBtn');
    
    if (!contentCategoryCards) {
        console.error('contentCategoryCards not found');
        alert('خطأ: لم يتم العثور على كروت التصنيفات');
        return;
    }
    
    if (contentCategoryCards.classList.contains('d-none')) {
        // بدء التعديل: حفظ القيم المختارة الحالية
        const selectedCards = contentCategoryCards.querySelectorAll('.category-card-selectable.selected');
        originalContentCategoryIds = Array.from(selectedCards).map(card => parseInt(card.getAttribute('data-category-id')));
        if (contentCategoryBadge) contentCategoryBadge.classList.add('d-none');
        contentCategoryCards.classList.remove('d-none');
        if (contentCategoryActions) contentCategoryActions.classList.remove('d-none');
        if (editBtn) editBtn.style.display = 'none';
    } else {
        // إلغاء التعديل
        contentCategoryCards.classList.add('d-none');
        if (contentCategoryBadge) contentCategoryBadge.classList.remove('d-none');
        if (contentCategoryActions) contentCategoryActions.classList.add('d-none');
        if (editBtn) editBtn.style.display = 'inline-block';
    }
}

function cancelEditContentCategory() {
    const contentCategoryBadge = document.getElementById('contentCategoryBadge');
    const contentCategoryCards = document.getElementById('contentCategoryCards');
    const contentCategoryActions = document.getElementById('contentCategoryActions');
    const editBtn = document.getElementById('editContentCategoryBtn');
    
    if (contentCategoryCards) {
        // استعادة القيم الأصلية
        const allCards = contentCategoryCards.querySelectorAll('.category-card-selectable');
        allCards.forEach(card => {
            const categoryId = parseInt(card.getAttribute('data-category-id'));
            if (originalContentCategoryIds.includes(categoryId)) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
        contentCategoryCards.classList.add('d-none');
        if (contentCategoryBadge) contentCategoryBadge.classList.remove('d-none');
        if (contentCategoryActions) contentCategoryActions.classList.add('d-none');
        if (editBtn) editBtn.style.display = 'inline-block';
    }
}

function toggleCategoryCard(cardElement) {
    cardElement.classList.toggle('selected');
}

function saveContentCategory(assetId) {
    const contentCategoryBadge = document.getElementById('contentCategoryBadge');
    const contentCategoryCards = document.getElementById('contentCategoryCards');
    const contentCategoryActions = document.getElementById('contentCategoryActions');
    const editBtn = document.getElementById('editContentCategoryBtn');
    
    if (!contentCategoryCards) {
        console.error('contentCategoryCards not found');
        alert('خطأ: لم يتم العثور على كروت التصنيفات');
        return;
    }
    
    // الحصول على جميع الكروت المحددة
    const selectedCards = contentCategoryCards.querySelectorAll('.category-card-selectable.selected');
    const selectedIds = Array.from(selectedCards).map(card => parseInt(card.getAttribute('data-category-id')));
    
    console.log('Saving content categories:', {
        assetId: assetId,
        categoryIds: selectedIds,
    });
    
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        alert('خطأ: لم يتم العثور على CSRF token');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
        return;
    }
    
    const requestBody = {
        category_ids: selectedIds
    };
    
    console.log('Request body:', requestBody);
    
    fetch(`/assets/${assetId}/update-content-category`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify(requestBody)
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            return response.json().then(data => {
                console.error('Error response:', data);
                throw new Error(data.error || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Success response:', data);
        if (data.success) {
            // تحديث العرض: عرض التصنيفات كـ badges
            if (contentCategoryBadge && data.categories) {
                if (data.categories.length > 0) {
                    contentCategoryBadge.innerHTML = data.categories.map(cat => 
                        `<span class="badge bg-success me-1 mb-1">${cat.name}</span>`
                    ).join('');
                    contentCategoryBadge.classList.remove('text-muted');
                } else {
                    contentCategoryBadge.innerHTML = '<span class="text-muted">غير محدد</span>';
                    contentCategoryBadge.classList.add('text-muted');
                }
            }
            if (contentCategoryBadge) contentCategoryBadge.classList.remove('d-none');
            contentCategoryCards.classList.add('d-none');
            if (contentCategoryActions) contentCategoryActions.classList.add('d-none');
            if (editBtn) editBtn.style.display = 'inline-block';
            
            showSuccessMessage('تم حفظ تصنيفات المحتوى بنجاح');
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('خطأ: ' + (data.error || 'فشل حفظ تصنيفات المحتوى'));
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء حفظ تصنيف المحتوى: ' + error.message);
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// اسم المتحدث (الشيخ) - قائمة منسدلة من الشيوخ
function toggleEditSpeaker() {
    const speakerBadge = document.getElementById('speakerBadge');
    const speakerSelectWrap = document.getElementById('speakerSelectWrap');
    const speakerActions = document.getElementById('speakerActions');
    const editBtn = document.getElementById('editSpeakerBtn');
    if (speakerBadge) speakerBadge.classList.add('d-none');
    if (speakerSelectWrap) speakerSelectWrap.classList.remove('d-none');
    if (speakerActions) speakerActions.classList.remove('d-none');
    if (editBtn) editBtn.style.display = 'none';
}

function cancelEditSpeaker() {
    const speakerBadge = document.getElementById('speakerBadge');
    const speakerSelectWrap = document.getElementById('speakerSelectWrap');
    const speakerActions = document.getElementById('speakerActions');
    const editBtn = document.getElementById('editSpeakerBtn');
    if (speakerBadge) speakerBadge.classList.remove('d-none');
    if (speakerSelectWrap) speakerSelectWrap.classList.add('d-none');
    if (speakerActions) speakerActions.classList.add('d-none');
    if (editBtn) editBtn.style.display = 'inline-block';
}

function saveSpeaker(assetId) {
    const scholarId = document.getElementById('speakerSelect').value || '';
    const speakerBadge = document.getElementById('speakerBadge');
    const speakerSelectWrap = document.getElementById('speakerSelectWrap');
    const speakerActions = document.getElementById('speakerActions');
    const editBtn = document.getElementById('editSpeakerBtn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) { alert('خطأ: CSRF token'); return; }
    const formData = new FormData();
    formData.append('_token', csrfToken.getAttribute('content'));
    formData.append('scholar_id', scholarId);
    fetch(`/assets/${assetId}/update-speaker`, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const name = data.speaker_name || 'غير محدد';
            if (speakerBadge) {
                speakerBadge.innerHTML = name ? `<span class="badge bg-primary fs-6 px-3 py-2">${name}</span>` : '<span class="text-muted">غير محدد</span>';
                speakerBadge.classList.remove('d-none');
            }
            if (speakerSelectWrap) speakerSelectWrap.classList.add('d-none');
            if (speakerActions) speakerActions.classList.add('d-none');
            if (editBtn) editBtn.style.display = 'inline-block';
            showSuccessMessage('تم تحديث اسم المتحدث بنجاح');
        } else {
            alert('خطأ: ' + (data.message || 'فشل الحفظ'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('حدث خطأ أثناء الحفظ');
    });
}

// قوائم التشغيل (نفس فكرة تصنيفات المحتوى)
let originalPlaylistIds = [];

function toggleEditPlaylists() {
    const playlistBadge = document.getElementById('playlistBadge');
    const playlistCards = document.getElementById('playlistCards');
    const playlistActions = document.getElementById('playlistActions');
    const editBtn = document.getElementById('editPlaylistBtn');
    if (!playlistCards) return;
    if (playlistCards.classList.contains('d-none')) {
        const selectedCards = playlistCards.querySelectorAll('.playlist-card-selectable.selected');
        originalPlaylistIds = Array.from(selectedCards).map(c => parseInt(c.getAttribute('data-playlist-id')));
        if (playlistBadge) playlistBadge.classList.add('d-none');
        playlistCards.classList.remove('d-none');
        if (playlistActions) playlistActions.classList.remove('d-none');
        if (editBtn) editBtn.style.display = 'none';
    } else {
        playlistCards.classList.add('d-none');
        if (playlistBadge) playlistBadge.classList.remove('d-none');
        if (playlistActions) playlistActions.classList.add('d-none');
        if (editBtn) editBtn.style.display = 'inline-block';
    }
}

function cancelEditPlaylists() {
    const playlistBadge = document.getElementById('playlistBadge');
    const playlistCards = document.getElementById('playlistCards');
    const playlistActions = document.getElementById('playlistActions');
    const editBtn = document.getElementById('editPlaylistBtn');
    if (playlistCards) {
        playlistCards.querySelectorAll('.playlist-card-selectable').forEach(c => {
            const id = parseInt(c.getAttribute('data-playlist-id'));
            c.classList.toggle('selected', originalPlaylistIds.includes(id));
        });
        playlistCards.classList.add('d-none');
        if (playlistBadge) playlistBadge.classList.remove('d-none');
        if (playlistActions) playlistActions.classList.add('d-none');
        if (editBtn) editBtn.style.display = 'inline-block';
    }
}

function togglePlaylistCard(el) {
    el.classList.toggle('selected');
}

function savePlaylists(assetId) {
    const playlistBadge = document.getElementById('playlistBadge');
    const playlistCards = document.getElementById('playlistCards');
    const playlistActions = document.getElementById('playlistActions');
    const editBtn = document.getElementById('editPlaylistBtn');
    if (!playlistCards) return;
    const selectedCards = playlistCards.querySelectorAll('.playlist-card-selectable.selected');
    const selectedIds = Array.from(selectedCards).map(c => parseInt(c.getAttribute('data-playlist-id')));
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
        return;
    }
    fetch(`/assets/${assetId}/update-playlists`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken.getAttribute('content'), 'Accept': 'application/json' },
        body: JSON.stringify({ playlist_ids: selectedIds })
    })
    .then(r => r.ok ? r.json() : r.json().then(d => { throw new Error(d.error || r.status); }))
    .then(data => {
        if (data.success) {
            if (playlistBadge && data.playlists) {
                playlistBadge.innerHTML = data.playlists.length > 0
                    ? data.playlists.map(p => `<span class="badge bg-primary me-1 mb-1">${p.title}</span>`).join('')
                    : '<span class="text-muted">غير مضاف لأي قائمة</span>';
            }
            if (playlistBadge) playlistBadge.classList.remove('d-none');
            playlistCards.classList.add('d-none');
            if (playlistActions) playlistActions.classList.add('d-none');
            if (editBtn) editBtn.style.display = 'inline-block';
            showSuccessMessage('تم حفظ قوائم التشغيل بنجاح');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            alert('خطأ: ' + (data.error || 'فشل حفظ قوائم التشغيل'));
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(err => {
        alert('حدث خطأ: ' + err.message);
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}
</script>
@endpush
@endsection

