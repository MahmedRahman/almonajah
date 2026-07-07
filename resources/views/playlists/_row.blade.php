@php
    $depth = $depth ?? 0;
    $indentRem = $depth * 1.75;
    $coverUrl = $playlist->image_path ? asset('storage/' . $playlist->image_path) : '';
    $deleteConfirm = ($playlist->children_count ?? 0) > 0
        ? 'سيتم حذف هذه القائمة وجميع القوائم الفرعية التابعة لها. هل أنت متأكد؟'
        : 'هل أنت متأكد من الحذف؟';
@endphp
<tr class="{{ $depth > 0 ? 'playlist-child-row' : '' }} {{ ! $playlist->is_visible ? 'playlist-hidden-row' : '' }}">
    <td>
        @if($playlist->image_path)
            <img src="{{ asset('storage/' . $playlist->image_path) }}"
                 alt="{{ $playlist->title }}"
                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
        @else
            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-music-note-list text-white" style="font-size: 1.5rem;"></i>
            </div>
        @endif
    </td>
    <td>
        <div class="d-flex align-items-center flex-wrap gap-1" style="padding-right: {{ $indentRem }}rem;">
            @if($depth > 0)
                <i class="bi bi-arrow-return-left text-muted flex-shrink-0" title="قائمة فرعية"></i>
            @endif
            <a href="{{ route('assets.index', ['playlist' => $playlist->id]) }}" class="text-decoration-none fw-medium text-dark">
                {{ $playlist->title }}
            </a>
            @if($depth === 0)
                <span class="badge bg-dark">أساسية</span>
            @else
                <span class="badge bg-secondary">فرعية</span>
            @endif
            @if(($playlist->children_count ?? 0) > 0)
                <span class="badge bg-light text-dark border">{{ $playlist->children_count }} فرعية</span>
            @endif
            @if($playlist->is_visible)
                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-eye"></i> ظاهرة</span>
            @else
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-eye-slash"></i> مخفية</span>
            @endif
        </div>
        @if($playlist->parent)
            <small class="text-muted d-block mt-1" style="padding-right: {{ $indentRem }}rem;">
                داخل: {{ $playlist->parent->title }}
            </small>
        @endif
    </td>
    <td><code>{{ $playlist->slug }}</code></td>
    <td>{{ \Illuminate\Support\Str::limit($playlist->description, 50) }}</td>
    <td>
        <span class="badge bg-primary">{{ $playlist->assets_count ?? 0 }}</span>
    </td>
    <td>
        <div class="btn-group btn-group-sm flex-wrap">
            <button type="button" class="btn btn-outline-success btn-add-sub-playlist" title="إضافة قائمة فرعية"
                    data-parent-id="{{ $playlist->id }}"
                    data-parent-title="{{ e($playlist->title) }}">
                <i class="bi bi-plus-lg"></i>
            </button>
            <a href="{{ route('assets.index', ['playlist' => $playlist->id]) }}" class="btn btn-outline-primary" title="عرض الفيديوهات">
                <i class="bi bi-collection-play"></i>
            </a>
            <button type="button" class="btn btn-outline-info btn-order-playlist" title="ترتيب الملفات"
                    data-playlist-id="{{ $playlist->id }}"
                    data-playlist-title="{{ e($playlist->title) }}">
                <i class="bi bi-sort-down"></i>
            </button>
            <form action="{{ route('playlists.toggle-visibility', $playlist) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn {{ $playlist->is_visible ? 'btn-outline-warning' : 'btn-outline-success' }}"
                        title="{{ $playlist->is_visible ? 'إخفاء من الموقع' : 'إظهار في الموقع' }}">
                    <i class="bi {{ $playlist->is_visible ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                </button>
            </form>
            <button type="button" class="btn btn-outline-secondary btn-edit-playlist"
                    data-playlist-id="{{ $playlist->id }}"
                    data-playlist-title="{{ e($playlist->title) }}"
                    data-playlist-slug="{{ e($playlist->slug ?? '') }}"
                    data-playlist-description="{{ e($playlist->description ?? '') }}"
                    data-playlist-image="{{ e($coverUrl) }}"
                    data-playlist-visible="{{ $playlist->is_visible ? '1' : '0' }}">
                <i class="bi bi-pencil"></i>
            </button>
            <form action="{{ route('playlists.destroy', $playlist) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger"
                        onclick="return confirm(@json($deleteConfirm))">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </td>
</tr>
@foreach($playlist->children ?? [] as $child)
    @include('playlists._row', ['playlist' => $child, 'depth' => $depth + 1])
@endforeach
