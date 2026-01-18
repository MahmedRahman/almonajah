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
                            <a href="{{ route('assets.open-folder', $asset) }}" 
                               class="badge bg-secondary fs-6 text-decoration-none" 
                               title="انقر لفتح فولدر الملف"
                               style="cursor: pointer;">
                                {{ $asset->id }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th width="200">اسم الملف:</th>
                        <td>{{ $asset->file_name }}</td>
                    </tr>
                    @if($asset->title)
                    <tr>
                        <th>العنوان:</th>
                        <td><strong class="fs-5">{{ $asset->title }}</strong></td>
                    </tr>
                    @else
                    <tr>
                        <th>العنوان:</th>
                        <td><span class="text-muted">غير محدد</span></td>
                    </tr>
                    @endif
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
                    @if($asset->speaker_name)
                    <tr>
                        <th>اسم المتحدث (الشيخ):</th>
                        <td>
                            <span class="badge bg-primary fs-6 px-3 py-2">{{ $asset->speaker_name }}</span>
                        </td>
                    </tr>
                    @else
                    <tr>
                        <th>اسم المتحدث (الشيخ):</th>
                        <td>
                            <span class="text-muted">غير محدد</span>
                        </td>
                    </tr>
                    @endif
                    @if($asset->category)
                    <tr>
                        <th>التصنيف:</th>
                        <td>
                            <span class="badge bg-info">{{ $asset->category }}</span>
                        </td>
                    </tr>
                    @endif
                    @if($asset->year)
                    <tr>
                        <th>السنة الهجرية:</th>
                        <td>
                            <span class="badge bg-warning text-dark">{{ $asset->year }}</span>
                        </td>
                    </tr>
                    @endif
                    @if($asset->gregorian_year)
                    <tr>
                        <th>السنة الميلادية:</th>
                        <td>
                            <span class="badge bg-success">{{ $asset->gregorian_year }}</span>
                        </td>
                    </tr>
                    @endif
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
                    @if($asset->modified_at)
                    <tr>
                        <th>تاريخ التعديل:</th>
                        <td>
                            @if(is_string($asset->modified_at))
                                {{ \Carbon\Carbon::parse($asset->modified_at)->format('Y-m-d H:i:s') }}
                            @else
                                {{ $asset->modified_at->format('Y-m-d H:i:s') }}
                            @endif
                        </td>
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

        @php
            // التحقق من وجود الملف في storage (يجب تعريفه قبل استخدامه)
            $fileUrl = null;
            $fileInStorage = false;
            if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
                // الملف موجود في storage
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($asset->relative_path)) {
                    $fileUrl = asset('storage/' . $asset->relative_path);
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
                    <div class="card-header bg-white">
                        <h5 class="mb-0">معاينة الملف</h5>
                    </div>
                    <div class="card-body">
                        @if(in_array(strtolower($asset->extension), ['mp4', 'mov', 'mkv', 'm4v', 'webm', 'avi']))
                            <video 
                                id="videoPlayer" 
                                controls 
                                class="w-100" 
                                style="max-height: 500px;"
                                @if(isset($transcriptionSegments) && $transcriptionSegments) ontimeupdate="updateTranscriptionHighlight()" @endif>
                                <source src="{{ $fileUrl }}" type="video/{{ $asset->extension }}">
                                متصفحك لا يدعم تشغيل الفيديو.
                            </video>
                        @elseif(in_array(strtolower($asset->extension), ['mp3', 'wav', 'ogg', 'm4a', 'aac']))
                            <audio controls class="w-100">
                                <source src="{{ $fileUrl }}" type="audio/{{ $asset->extension }}">
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
                    <div class="card-header bg-white">
                        <h5 class="mb-0">المحتوى النصي</h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="bg-light p-3 rounded flex-grow-1" id="transcriptionContainer" style="max-height: 500px; overflow-y: auto; text-align: right; direction: rtl;">
                            @if(isset($transcriptionSegments) && $transcriptionSegments && $fileUrl)
                                @foreach($transcriptionSegments as $index => $segment)
                                    <span 
                                        class="transcription-segment" 
                                        data-start="{{ $segment['start'] }}" 
                                        data-end="{{ $segment['end'] }}"
                                        data-index="{{ $index }}"
                                        style="cursor: pointer; transition: background-color 0.3s; display: inline-block; margin: 2px;"
                                        onclick="seekToTime({{ $segment['start'] }})">
                                        {{ trim($segment['text']) }}
                                    </span>
                                @endforeach
                            @else
                                <p class="mb-0" style="white-space: pre-wrap; text-align: right; direction: rtl;">{{ $asset->transcription }}</p>
                            @endif
                        </div>
                        <div class="mt-2 text-muted small">
                            عدد الأحرف: {{ number_format(strlen($asset->transcription)) }}
                            @if(isset($transcriptionSegments) && $transcriptionSegments && $fileUrl)
                                <span class="badge bg-info ms-2">مزامنة نشطة</span>
                            @endif
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
                        <source src="{{ $fileUrl }}" type="video/{{ $asset->extension }}">
                        متصفحك لا يدعم تشغيل الفيديو.
                    </video>
                @elseif(in_array(strtolower($asset->extension), ['mp3', 'wav', 'ogg', 'm4a', 'aac']))
                    <audio controls class="w-100">
                        <source src="{{ $fileUrl }}" type="audio/{{ $asset->extension }}">
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
            <div class="card-header bg-white">
                <h5 class="mb-0">المحتوى النصي</h5>
            </div>
            <div class="card-body">
                <div class="bg-light p-3 rounded" id="transcriptionContainer" style="max-height: 400px; overflow-y: auto; text-align: right; direction: rtl;">
                    <p class="mb-0" style="white-space: pre-wrap; text-align: right; direction: rtl;">{{ $asset->transcription }}</p>
                </div>
                <div class="mt-2 text-muted small">
                    عدد الأحرف: {{ number_format(strlen($asset->transcription)) }}
                </div>
            </div>
        </div>
        @endif

        @if($asset->topics || $asset->emotions || $asset->intent || $asset->audience)
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">تحليل المحتوى</h5>
            </div>
            <div class="card-body">
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

        @if($asset->hlsVersions && $asset->hlsVersions->count() > 0)
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">نسخ HLS المتاحة</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>النسخة</th>
                                <th>الأبعاد</th>
                                <th>معدل البت (فيديو)</th>
                                <th>معدل البت (صوت)</th>
                                <th>عدد القطع</th>
                                <th>الحجم</th>
                                <th>الرابط</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($asset->hlsVersions as $version)
                            <tr>
                                <td>
                                    <span class="badge bg-primary fs-6">{{ $version->resolution }}</span>
                                </td>
                                <td>{{ $version->width }} × {{ $version->height }}</td>
                                <td><code>{{ $version->bitrate }}</code></td>
                                <td><code>{{ $version->audio_bitrate }}</code></td>
                                <td>{{ $version->segment_count ?? '-' }}</td>
                                <td>
                                    @if($version->total_size_bytes)
                                        @php
                                            $sizeMB = round($version->total_size_bytes / (1024 * 1024), 2);
                                        @endphp
                                        {{ $sizeMB }} MB
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($version->playlist_path)
                                        <a href="{{ asset('storage/' . $version->playlist_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
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
                @if($asset->hlsVersions->first() && $asset->hlsVersions->first()->master_playlist_path)
                <div class="mt-3">
                    <strong>قائمة التشغيل الرئيسية:</strong>
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
        @if($asset->speaker_name || $asset->category || $asset->year || $asset->gregorian_year)
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h5 class="mb-0">معلومات المحتوى</h5>
            </div>
            <div class="card-body">
                @if($asset->speaker_name)
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">اسم المتحدث (الشيخ):</small>
                    <strong class="d-block fs-5 text-primary">{{ $asset->speaker_name }}</strong>
                </div>
                @else
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">اسم المتحدث (الشيخ):</small>
                    <span class="text-muted">غير محدد</span>
                </div>
                @endif
                @if($asset->category)
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">التصنيف:</small>
                    <span class="badge bg-info fs-6">{{ $asset->category }}</span>
                </div>
                @endif
                <div class="row">
                    @if($asset->year)
                    <div class="col-6 mb-2">
                        <small class="text-muted d-block mb-1">السنة الهجرية:</small>
                        <span class="badge bg-warning text-dark">{{ $asset->year }}</span>
                    </div>
                    @endif
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

                <!-- 3. استخراج المحتوى النصي -->
                @if($fileInStorage)
                <form id="transcribeForm" class="mb-3">
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
                        @if($asset->topics || $asset->emotions || $asset->intent || $asset->audience)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i>
                            </span>
                        @endif
                    </button>
                </form>
                @endif

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

                <!-- Progress Bar for HLS Conversion -->
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
                
                <!-- Terminal Log Viewer for HLS Conversion -->
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
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-upload me-1"></i>رفع صورة مصغرة
                    </button>
                </form>
                @else
                <small class="text-muted d-block">يجب نقل الفيديو إلى الموقع أولاً لرفع صورة مصغرة</small>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// زر تحليل المحتوى النصي
document.getElementById('analyzeBtn')?.addEventListener('click', function(e) {
    const btn = this;
    const originalText = btn.innerHTML;
    
    if (!confirm('سيتم إرسال المحتوى النصي إلى DeepSeek API لتحليله. هل تريد المتابعة؟')) {
        return false;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري التحليل...';
    
    fetch('{{ route("assets.analyze", $asset) }}', {
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
            return;
        }
        
        if (data.success) {
            // إعادة تحميل الصفحة لعرض النتائج مباشرة بدون رسالة
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء تحليل المحتوى');
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
    
    fetch('{{ route("assets.convert-hls", $asset) }}', {
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
    fetch('{{ route("assets.hls-status", $asset) }}')
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
                
                // حذف Cache بعد الانتهاء
                fetch('{{ route("assets.hls-status", $asset) }}?clear=1')
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
                
                // حذف Cache عند الخطأ
                fetch('{{ route("assets.hls-status", $asset) }}?clear=1')
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
    
    fetch('{{ route("assets.extract-audio", $asset) }}', {
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
    fetch('{{ route("assets.extract-audio-status", $asset) }}')
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
                
                // حذف Cache بعد الانتهاء
                fetch('{{ route("assets.extract-audio-status", $asset) }}?clear=1')
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
                
                // حذف Cache عند الخطأ
                fetch('{{ route("assets.extract-audio-status", $asset) }}?clear=1')
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
    
    // إرسال طلب AJAX
    fetch('{{ route("assets.transcribe", $asset) }}', {
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
            let errorMessage = data.error;
            if (data.can_clear) {
                errorMessage += '\n\nهل تريد إعادة تعيين الحالة والبدء من جديد؟';
                if (confirm(errorMessage)) {
                    // حذف الـ cache وإعادة المحاولة
                    fetch('{{ route("assets.transcribe-status", $asset) }}?clear=1')
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
    fetch('{{ route("assets.transcribe-status", $asset) }}')
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
                
                // حذف Cache بعد الانتهاء
                fetch('{{ route("assets.transcribe-status", $asset) }}?clear=1')
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
                
                // حذف Cache عند الخطأ
                fetch('{{ route("assets.transcribe-status", $asset) }}?clear=1')
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

// مزامنة الفيديو مع المحتوى النصي
@if(isset($transcriptionSegments) && $transcriptionSegments && $fileUrl)
const transcriptionSegments = @json($transcriptionSegments);
let currentHighlightedIndex = -1;

function updateTranscriptionHighlight() {
    const video = document.getElementById('videoPlayer');
    if (!video) return;
    
    const currentTime = video.currentTime;
    
    // البحث عن الـ segment المطابق للوقت الحالي
    let activeIndex = -1;
    for (let i = 0; i < transcriptionSegments.length; i++) {
        const segment = transcriptionSegments[i];
        if (currentTime >= segment.start && currentTime <= segment.end) {
            activeIndex = i;
            break;
        }
    }
    
    // إذا تغير الـ segment النشط، نقوم بتحديث التمييز
    if (activeIndex !== currentHighlightedIndex) {
        // إزالة التمييز من جميع الـ segments
        document.querySelectorAll('.transcription-segment').forEach(seg => {
            seg.style.backgroundColor = '';
            seg.style.color = '';
            seg.style.fontWeight = 'normal';
            seg.style.padding = '';
            seg.style.borderRadius = '';
        });
        
        // إضافة التمييز للـ segment النشط
        if (activeIndex >= 0) {
            const activeSegment = document.querySelector(`.transcription-segment[data-index="${activeIndex}"]`);
            if (activeSegment) {
                activeSegment.style.backgroundColor = '#ffc107';
                activeSegment.style.color = '#000';
                activeSegment.style.fontWeight = 'bold';
                activeSegment.style.padding = '2px 4px';
                activeSegment.style.borderRadius = '3px';
                
                // التمرير إلى الـ segment النشط
                activeSegment.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        currentHighlightedIndex = activeIndex;
    }
}

function seekToTime(time) {
    const video = document.getElementById('videoPlayer');
    if (video) {
        video.currentTime = time;
        video.play();
    }
}
@endif
</script>
@endpush
@endsection

