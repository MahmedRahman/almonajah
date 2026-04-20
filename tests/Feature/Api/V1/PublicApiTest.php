<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    protected int $assetId;

    protected int $playlistId;

    protected int $scholarId;

    protected int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPublicGraph();
    }

    public function test_core_endpoints_return_success_shape(): void
    {
        $this->getJson('/api/v1/videos')->assertOk()->assertJsonStructure(['success', 'data', 'meta' => ['pagination']]);
        $this->getJson('/api/v1/home')->assertOk()->assertJsonStructure(['success', 'data', 'meta' => ['pagination']]);
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonStructure(['success', 'data' => ['items'], 'meta']);
        $this->getJson('/api/v1/search?q=demo')->assertOk()->assertJsonStructure(['success', 'data', 'meta' => ['pagination']]);
        $this->getJson('/api/v1/search/suggestions?q=demo')->assertOk()->assertJsonStructure(['success', 'data' => ['items'], 'meta']);
        $this->getJson("/api/v1/assets/{$this->assetId}")->assertOk()->assertJsonStructure(['success', 'data' => ['item'], 'meta']);
        $this->getJson("/api/v1/assets/{$this->assetId}/related")->assertOk()->assertJsonStructure(['success', 'data', 'meta' => ['pagination']]);
        $this->getJson("/api/v1/assets/{$this->assetId}/comments")->assertOk()->assertJsonStructure(['success', 'data', 'meta' => ['pagination']]);
    }

    public function test_secondary_endpoints_return_success_shape(): void
    {
        $this->getJson('/api/v1/audio/home')->assertOk()->assertJsonStructure(['success', 'data', 'meta' => ['pagination']]);
        $this->getJson('/api/v1/audio/tracks')->assertOk()->assertJsonStructure(['success', 'data', 'meta' => ['pagination']]);
        $this->getJson("/api/v1/audio/tracks/{$this->assetId}")->assertOk()->assertJsonStructure(['success', 'data' => ['item'], 'meta']);
        $this->getJson('/api/v1/playlists')->assertOk()->assertJsonStructure(['success', 'data', 'meta' => ['pagination']]);
        $this->getJson("/api/v1/playlists/{$this->playlistId}")->assertOk()->assertJsonStructure(['success', 'data', 'meta' => ['pagination']]);
        $this->getJson('/api/v1/scholars')->assertOk()->assertJsonStructure(['success', 'data', 'meta' => ['pagination']]);
        $this->getJson("/api/v1/scholars/{$this->scholarId}")->assertOk()->assertJsonStructure(['success', 'data', 'meta' => ['pagination']]);
        $this->getJson('/api/v1/shorts')->assertOk()->assertJsonStructure(['success', 'data', 'meta' => ['pagination']]);
        $this->getJson('/api/v1/live/feed')->assertOk()->assertJsonStructure(['success', 'data', 'meta' => ['pagination']]);
    }

    public function test_filters_and_pagination_contract(): void
    {
        $this->getJson('/api/v1/videos?per_page=1&category_name=General')
            ->assertOk()
            ->assertJsonPath('meta.pagination.per_page', 1);

        $this->getJson('/api/v1/audio/tracks?year=1445&per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.pagination.per_page', 1);

        $this->getJson("/api/v1/videos?category_id={$this->categoryId}")
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $this->assetId);
    }

    private function seedPublicGraph(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'API User',
            'email' => 'api@example.com',
            'password' => bcrypt('secret'),
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->categoryId = DB::table('categories')->insertGetId([
            'name' => 'General',
            'slug' => 'general',
            'show_on_site' => 1,
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->scholarId = DB::table('scholars')->insertGetId([
            'name' => 'Scholar One',
            'status' => 'active',
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assetId = DB::table('assets')->insertGetId([
            'file_name' => 'demo.mp4',
            'relative_path' => 'assets/1445/demo.mp4',
            'extension' => 'mp4',
            'size_bytes' => 1024,
            'modified_at' => now(),
            'is_publishable' => 1,
            'title' => 'Demo Asset',
            'speaker_name' => 'Scholar One',
            'scholar_id' => $this->scholarId,
            'orientation' => 'landscape',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('asset_category')->insert([
            'asset_id' => $this->assetId,
            'category_id' => $this->categoryId,
            'order' => 1,
        ]);

        DB::table('audio_files')->insert([
            'asset_id' => $this->assetId,
            'format' => 'mp3',
            'bitrate' => '192k',
            'file_path' => 'assets/1445/demo.mp3',
            'duration_seconds' => 120,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->playlistId = DB::table('playlists')->insertGetId([
            'title' => 'Playlist A',
            'slug' => 'playlist-a',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('asset_playlist')->insert([
            'asset_id' => $this->assetId,
            'playlist_id' => $this->playlistId,
            'order' => 1,
        ]);

        DB::table('comments')->insert([
            'user_id' => $userId,
            'asset_id' => $this->assetId,
            'content' => 'Great content',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
