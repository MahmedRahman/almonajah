# Performance Optimization Checklist

## Must-Do (Top 10)

1. ✅ **Select Only Required Fields**
   - Use `select()` to limit columns in queries
   - Reduces memory usage and query time

2. ✅ **Eager Loading with Constraints**
   - Use `load(['relation' => function($q) { $q->select(...); }])`
   - Prevents N+1 queries

3. ✅ **Cache Heavy Queries**
   - Cache stats, filters, related videos (30min-1hr)
   - Cache transcription segments (1hr)

4. ✅ **Lazy Loading Images**
   - Use `loading="lazy"` and `decoding="async"`
   - Add explicit `width` and `height` to prevent CLS

5. ✅ **Route/Config/View Caching**
   - `php artisan route:cache`
   - `php artisan config:cache`
   - `php artisan view:cache`

6. ⚠️ **Database Indexes** (TODO)
   - Add indexes on: `relative_path`, `speaker_name`, `orientation`, `duration_seconds`
   - Add composite index on `(relative_path, orientation, duration_seconds)`

7. ⚠️ **HLS Optimization** (TODO)
   - Use 6-second segments (current)
   - Generate 360p, 480p, 720p renditions
   - Use `hls.js` for adaptive streaming

8. ⚠️ **Thumbnail Generation** (TODO)
   - Generate thumbnails during video upload/processing
   - Store in WebP format (smaller size)
   - Create multiple sizes (320x180, 640x360)

9. ⚠️ **CDN for Media** (TODO)
   - Use CDN for HLS segments, thumbnails, captions
   - Configure proper cache headers

10. ⚠️ **Pagination Optimization** (TODO)
    - Use cursor pagination for large datasets
    - Limit to 12-20 items per page

---

## Caching Plan (Laravel + CDN)

### Laravel Cache
- **Stats**: 1 hour (`home_stats`, `home_speaker_names`, `home_categories`, `home_years`)
- **Shorts**: 30 minutes (`home_shorts`)
- **Related Videos**: 30 minutes (`related_assets_{id}`)
- **Transcription Segments**: 1 hour (`transcription_segments_{id}`)
- **Cache Driver**: Redis (recommended) or File

### CDN Cache Headers
```php
// In middleware or controller
return response()->view('home', $data)
    ->header('Cache-Control', 'public, max-age=300'); // 5 minutes
```

### Cache Invalidation
- Clear cache when asset is updated/deleted
- Use cache tags if using Redis: `Cache::tags(['assets'])->flush()`

---

## Database Plan

### Indexes to Add
```sql
ALTER TABLE assets ADD INDEX idx_relative_path (relative_path(255));
ALTER TABLE assets ADD INDEX idx_speaker_name (speaker_name);
ALTER TABLE assets ADD INDEX idx_orientation (orientation);
ALTER TABLE assets ADD INDEX idx_duration (duration_seconds);
ALTER TABLE assets ADD INDEX idx_path_orientation_duration (relative_path(100), orientation, duration_seconds);
```

### Query Optimization
- ✅ Use `select()` to limit columns
- ✅ Use `where()` with indexed columns
- ✅ Use `paginate()` instead of `get()` for lists
- ⚠️ Use `cursor()` for large exports (if needed)

### Pagination
- Homepage: 12 items per page
- Admin list: 20 items per page
- Use `simplePaginate()` if no page numbers needed

---

## Media Plan

### HLS Renditions (Default Settings)
- **360p**: 640x360, 800k video, 96k audio
- **480p**: 854x480, 1400k video, 128k audio
- **720p**: 1280x720, 2800k video, 128k audio
- **Segment Duration**: 6 seconds
- **Playlist Type**: VOD (Video on Demand)

### Thumbnails
- Generate during video processing
- Format: WebP (fallback to JPEG)
- Sizes: 320x180 (grid), 640x360 (detail), 1280x720 (preview)
- Store in `storage/app/public/assets/{year}/{id}/thumbnails/`

### Captions
- Store in `captions/` subfolder
- Formats: JSON (segments), TXT (plain), TIMED_TXT (timed)
- Compress JSON if > 100KB

### Video Player
- Use **hls.js** for HLS playback
- Preload: `metadata` (not `auto`)
- Autoplay: Disabled (user-initiated)

---

## Frontend Plan

### Blade Rendering
- ✅ Use `@once` directive for scripts/styles
- ✅ Minimize `@php` blocks
- ✅ Use `@forelse` instead of `@if` + `@foreach`

### Lazy Loading
- ✅ Images: `loading="lazy"` + `decoding="async"`
- ✅ Videos: `preload="none"` for thumbnails
- ⚠️ Use Intersection Observer for video thumbnail loading

### Assets Bundling
- Use Laravel Mix or Vite
- Minify CSS/JS
- Use `defer` for non-critical scripts

### Image Optimization
- Add `width` and `height` attributes (prevent CLS)
- Use `srcset` for responsive images
- Consider WebP with JPEG fallback

---

## Background Jobs Plan

### Queue Configuration
- Use Redis or Database queue driver
- Process HLS conversion in background
- Process Whisper transcription in background

### Job Classes
```php
// app/Jobs/ConvertToHls.php
// app/Jobs/TranscribeVideo.php
// app/Jobs/GenerateThumbnails.php
```

### Queue Workers
```bash
php artisan queue:work --tries=3 --timeout=3600
```

### Progress Tracking
- Use cache to store progress
- Use WebSockets or polling for real-time updates
- Clear cache after completion

---

## Monitoring

### Metrics to Watch
1. **TTFB (Time to First Byte)**: < 200ms
2. **LCP (Largest Contentful Paint)**: < 2.5s
3. **CLS (Cumulative Layout Shift)**: < 0.1
4. **FCP (First Contentful Paint)**: < 1.8s
5. **Database Query Time**: < 100ms per query
6. **Cache Hit Rate**: > 80%

### Tools
- Laravel Telescope (dev)
- Laravel Debugbar (dev)
- Google PageSpeed Insights
- Lighthouse (Chrome DevTools)

### Logging
- Log slow queries (> 100ms)
- Log cache misses
- Monitor queue job failures

---

## Quick Wins (Already Implemented)
- ✅ Route caching
- ✅ Config caching
- ✅ View caching
- ✅ Query field selection
- ✅ Eager loading with constraints
- ✅ Cache for stats and filters
- ✅ Lazy loading images
- ✅ Image dimensions (width/height)

---

## Next Steps (Priority Order)
1. Add database indexes
2. Generate thumbnails during processing
3. Move HLS/transcription to background jobs
4. Set up CDN for media files
5. Implement WebP thumbnails
6. Add cursor pagination for large lists
7. Set up monitoring (Telescope/Debugbar)

