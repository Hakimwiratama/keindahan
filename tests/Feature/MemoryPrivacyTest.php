<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Tests\TestCase;

class MemoryPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_is_redirected_to_login_after_registration(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Nadia',
            'email' => '  NADIA@EXAMPLE.TEST  ',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Akun berhasil dibuat. Silakan masuk.');

        $this->assertGuest();
        $this->assertDatabaseHas('users', ['email' => 'nadia@example.test']);

        $user = User::where('email', 'nadia@example.test')->firstOrFail();
        $this->assertDatabaseMissing('albums', ['user_id' => $user->id]);
    }

    public function test_a_registered_user_can_log_in_with_mixed_case_email(): void
    {
        $user = User::factory()->create([
            'email' => 'nadia@example.test',
            'password' => 'a-strong-password',
        ]);

        $this->post(route('login.store'), [
            'email' => '  NADIA@EXAMPLE.TEST ',
            'password' => 'a-strong-password',
        ])->assertRedirect(route('albums.index'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('albums.index'))
            ->assertOk()
            ->assertSee('login-transition-overlay', false)
            ->assertSee('login-light-top-left', false)
            ->assertSee('login-light-top-right', false)
            ->assertSee('login-light-bottom-right', false)
            ->assertSee('login-light-bottom-left', false)
            ->assertSee('login-light-core', false);
    }

    public function test_the_login_page_uses_the_dark_snow_theme(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('dark-dashboard', false)
            ->assertSee('class="snowfall"', false)
            ->assertSee('❄', false);
    }

    public function test_the_login_page_explains_when_its_session_expires(): void
    {
        $this->withSession(['login_session_expired' => true])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('Sesi login berakhir atau halaman sudah terlalu lama terbuka.');
    }

    public function test_a_user_only_sees_their_own_memories(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownAlbum = $owner->albums()->create(['name' => 'Sayang']);
        $otherAlbum = $otherUser->albums()->create(['name' => 'Kuliah pribadi']);
        $ownMemory = $owner->memories()->create([
            'album_id' => $ownAlbum->id,
            'title' => 'Milik saya',
            'type' => 'note',
            'note' => 'Catatan pribadi',
        ]);
        $otherUser->memories()->create([
            'album_id' => $otherAlbum->id,
            'title' => 'Bukan milik saya',
            'type' => 'note',
            'note' => 'Catatan rahasia',
        ]);

        $this->actingAs($owner)
            ->get(route('albums.index'))
            ->assertOk()
            ->assertSee('dark-dashboard', false)
            ->assertSee('Sayang')
            ->assertSee('class="snowfall"', false)
            ->assertDontSee('Kuliah pribadi')
            ->assertDontSee('Bukan milik saya');

        $this->actingAs($owner)
            ->get(route('albums.show', $ownAlbum))
            ->assertOk()
            ->assertSee('class="snowfall"', false);

        $this->actingAs($owner)
            ->get(route('memories.show', [$ownAlbum, $ownMemory]))
            ->assertOk()
            ->assertSee('class="snowfall"', false)
            ->assertSee('Milik saya')
            ->assertSee('← Kembali ke folder')
            ->assertSee(route('albums.show', $ownAlbum), false);
    }

    public function test_photo_and_video_details_can_navigate_between_media_without_notes(): void
    {
        $user = User::factory()->create();
        $album = $user->albums()->create(['name' => 'Liburan']);

        $newerPhoto = $user->memories()->create([
            'album_id' => $album->id,
            'title' => 'Foto baru',
            'type' => 'photo',
            'file_path' => 'memories/newer.jpg',
        ]);
        $currentVideo = $user->memories()->create([
            'album_id' => $album->id,
            'title' => 'Video saat ini',
            'type' => 'video',
            'file_path' => 'memories/current.mp4',
            'mime_type' => 'video/mp4',
        ]);
        $user->memories()->create([
            'album_id' => $album->id,
            'title' => 'Catatan',
            'type' => 'note',
            'note' => 'Catatan tidak ikut navigasi media.',
        ]);
        $olderPhoto = $user->memories()->create([
            'album_id' => $album->id,
            'title' => 'Foto lama',
            'type' => 'photo',
            'file_path' => 'memories/older.jpg',
        ]);

        $this->actingAs($user)
            ->get(route('memories.show', [$album, $currentVideo]))
            ->assertOk()
            ->assertSee('2 / 3')
            ->assertSee(route('memories.show', [$album, $newerPhoto]), false)
            ->assertSee(route('memories.show', [$album, $olderPhoto]), false)
            ->assertSee('media-nav-previous', false)
            ->assertSee('media-nav-next', false)
            ->assertSee('data-media-viewer', false)
            ->assertSee('touchstart', false);
    }

    public function test_a_single_media_item_does_not_show_navigation_controls(): void
    {
        $user = User::factory()->create();
        $album = $user->albums()->create(['name' => 'Kenangan']);
        $photo = $user->memories()->create([
            'album_id' => $album->id,
            'title' => 'Satu foto',
            'type' => 'photo',
            'file_path' => 'memories/one.jpg',
        ]);

        $this->actingAs($user)
            ->get(route('memories.show', [$album, $photo]))
            ->assertOk()
            ->assertDontSee('media-navigation', false)
            ->assertDontSee('data-media-viewer', false);
    }

    public function test_a_user_can_create_and_open_a_private_album(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('albums.store'), ['name' => 'Kuliah'])
            ->assertRedirect();

        $album = $user->albums()->where('name', 'Kuliah')->firstOrFail();

        $this->actingAs($user)
            ->get(route('albums.index'))
            ->assertOk()
            ->assertSee('Kuliah');

        $this->actingAs($user)
            ->get(route('albums.create'))
            ->assertOk()
            ->assertSee('Contoh: Sayang');

        $this->actingAs($user)
            ->get(route('albums.show', $album))
            ->assertOk()
            ->assertSee('Folder ini masih kosong');

        $this->actingAs($user)
            ->get(route('memories.create', $album))
            ->assertOk()
            ->assertSee('name="files[]"', false)
            ->assertSee('1 GB');
    }

    public function test_the_first_uploaded_photo_is_shown_as_an_album_cover(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $album = $user->albums()->create(['name' => 'Rumah']);

        $firstPhoto = $user->memories()->create([
            'album_id' => $album->id,
            'title' => 'Depan rumah',
            'type' => 'photo',
            'file_path' => UploadedFile::fake()->image('depan.jpg')->store('memories/'.$user->id.'/'.$album->id, 'local'),
            'mime_type' => 'image/jpeg',
        ]);

        $user->memories()->create([
            'album_id' => $album->id,
            'title' => 'Dapur',
            'type' => 'photo',
            'file_path' => UploadedFile::fake()->image('dapur.jpg')->store('memories/'.$user->id.'/'.$album->id, 'local'),
            'mime_type' => 'image/jpeg',
        ]);

        $this->actingAs($user)
            ->get(route('albums.index'))
            ->assertOk()
            ->assertSee(route('memories.preview', [$album, $firstPhoto]))
            ->assertSee('2 kenangan');
    }

    public function test_a_user_cannot_open_another_users_album_or_its_memories(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $album = $owner->albums()->create(['name' => 'Privat']);
        $memory = $owner->memories()->create([
            'album_id' => $album->id,
            'title' => 'Rahasia',
            'type' => 'note',
            'note' => 'Catatan pribadi',
        ]);

        $this->actingAs($otherUser)
            ->get(route('albums.show', $album))
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->get(route('memories.show', [$album, $memory]))
            ->assertNotFound();
    }

    public function test_a_user_can_delete_an_album_and_all_its_memories(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $album = $user->albums()->create(['name' => 'Liburan']);
        $path = UploadedFile::fake()->image('pantai.jpg')->store('memories/'.$user->id.'/'.$album->id, 'local');
        $user->memories()->create([
            'album_id' => $album->id,
            'title' => 'Pantai',
            'type' => 'photo',
            'file_path' => $path,
            'original_filename' => 'pantai.jpg',
        ]);
        $user->memories()->create([
            'album_id' => $album->id,
            'title' => 'Catatan liburan',
            'type' => 'note',
            'note' => 'Perjalanan yang menyenangkan.',
        ]);

        $this->actingAs($user)
            ->get(route('albums.show', $album))
            ->assertOk()
            ->assertSee('Hapus folder');

        $this->actingAs($user)
            ->delete(route('albums.destroy', $album))
            ->assertRedirect(route('albums.index'))
            ->assertSessionHas('status', 'Folder “Liburan” beserta 2 kenangan berhasil dihapus.');

        $this->assertDatabaseMissing('albums', ['id' => $album->id]);
        $this->assertDatabaseMissing('memories', ['album_id' => $album->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_a_user_cannot_delete_another_users_album(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $album = $owner->albums()->create(['name' => 'Folder pribadi']);

        $this->actingAs($otherUser)
            ->delete(route('albums.destroy', $album))
            ->assertNotFound();

        $this->assertDatabaseHas('albums', ['id' => $album->id, 'user_id' => $owner->id]);
    }

    public function test_a_user_can_delete_an_empty_album(): void
    {
        $user = User::factory()->create();
        $album = $user->albums()->create(['name' => 'Folder kosong']);

        $this->actingAs($user)
            ->delete(route('albums.destroy', $album))
            ->assertRedirect(route('albums.index'))
            ->assertSessionHas('status', 'Folder “Folder kosong” beserta 0 kenangan berhasil dihapus.');

        $this->assertDatabaseMissing('albums', ['id' => $album->id]);
    }

    public function test_the_default_album_cleanup_preserves_memories_in_the_archive_folder(): void
    {
        $user = User::factory()->create();
        $defaultAlbum = $user->albums()->create(['name' => 'Sayang']);
        $archiveAlbum = $user->albums()->create(['name' => 'Kenangan lama']);
        $memory = $user->memories()->create([
            'album_id' => $defaultAlbum->id,
            'title' => 'Kenangan terdahulu',
            'type' => 'note',
            'note' => 'Tetap tersimpan.',
        ]);

        $migration = require database_path('migrations/2026_11_01_000005_remove_default_albums.php');
        $migration->up();

        $this->assertDatabaseMissing('albums', ['id' => $defaultAlbum->id]);
        $this->assertDatabaseHas('albums', ['id' => $archiveAlbum->id, 'name' => 'Kenangan lama']);
        $this->assertDatabaseHas('memories', [
            'id' => $memory->id,
            'album_id' => $archiveAlbum->id,
            'note' => 'Tetap tersimpan.',
        ]);
    }

    public function test_a_user_can_create_models_with_user_and_album_ids_directly(): void
    {
        $user = User::factory()->create();

        $album = Album::create([
            'user_id' => $user->id,
            'name' => 'Folder manual',
        ]);

        $memory = Memory::create([
            'user_id' => $user->id,
            'album_id' => $album->id,
            'title' => 'Catatan manual',
            'type' => 'note',
            'note' => 'Tersimpan dengan user_id yang benar.',
        ]);

        $this->assertSame($user->id, $album->user_id);
        $this->assertSame($user->id, $memory->user_id);
        $this->assertSame($album->id, $memory->album_id);
    }

    public function test_a_photo_preview_still_works_when_the_mime_type_is_missing(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $album = $owner->albums()->create(['name' => 'Foto']);
        $path = UploadedFile::fake()->image('photo.jpg')->store('memories/'.$owner->id.'/'.$album->id, 'local');
        $memory = $owner->memories()->create([
            'album_id' => $album->id,
            'title' => 'Foto pribadi',
            'type' => 'photo',
            'file_path' => $path,
            'mime_type' => null,
        ]);

        $this->actingAs($owner)
            ->get(route('memories.preview', [$album, $memory]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_a_user_can_preview_a_photo_only_in_their_own_album(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $album = $owner->albums()->create(['name' => 'Foto']);
        $path = UploadedFile::fake()->image('photo.jpg')->store('memories/'.$owner->id.'/'.$album->id, 'local');
        $memory = $owner->memories()->create([
            'album_id' => $album->id,
            'title' => 'Foto pribadi',
            'type' => 'photo',
            'file_path' => $path,
            'mime_type' => 'image/jpeg',
        ]);

        $this->actingAs($owner)
            ->get(route('memories.preview', [$album, $memory]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($otherUser)
            ->get(route('memories.preview', [$album, $memory]))
            ->assertNotFound();
    }

    public function test_a_user_cannot_view_or_download_another_users_memory(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $album = $owner->albums()->create(['name' => 'Foto pribadi']);
        $path = UploadedFile::fake()->image('private.jpg')->store('memories/'.$owner->id, 'local');
        $memory = $owner->memories()->create([
            'album_id' => $album->id,
            'title' => 'Rahasia',
            'type' => 'photo',
            'file_path' => $path,
            'original_filename' => 'private.jpg',
        ]);

        $this->actingAs($otherUser)
            ->get(route('memories.show', [$album, $memory]))
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->get(route('memories.download', [$album, $memory]))
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->delete(route('memories.destroy', [$album, $memory]))
            ->assertNotFound();

        $this->assertDatabaseHas('memories', ['id' => $memory->id, 'user_id' => $owner->id, 'album_id' => $album->id]);
        Storage::disk('local')->assertExists($path);
    }

    public function test_a_registered_user_can_upload_and_download_a_private_photo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $album = $user->albums()->create(['name' => 'Senja']);
        $file = UploadedFile::fake()->image('sunset.jpg');

        $this->actingAs($user)
            ->post(route('memories.store', $album), [
                'title' => 'Senja',
                'type' => 'photo',
                'files' => [$file],
            ])
            ->assertRedirect(route('albums.show', $album));

        $memory = Memory::firstOrFail();
        Storage::disk('local')->assertExists($memory->file_path);
        $this->assertSame($album->id, $memory->album_id);

        $this->actingAs($user)
            ->get(route('memories.download', [$album, $memory]))
            ->assertOk();
    }

    public function test_a_php_upload_error_is_reported_with_a_specific_message(): void
    {
        $user = User::factory()->create();
        $album = $user->albums()->create(['name' => 'Video']);
        $failedUpload = new SymfonyUploadedFile(
            __FILE__,
            'video.mp4',
            'video/mp4',
            UPLOAD_ERR_NO_TMP_DIR,
            true,
        );

        $this->actingAs($user)
            ->from(route('memories.create', $album))
            ->post(route('memories.store', $album), [
                'title' => 'Video gagal',
                'type' => 'video',
                'files' => [$failedUpload],
            ])
            ->assertRedirect(route('memories.create', $album))
            ->assertSessionHasErrors([
                'files' => 'Server tidak menemukan folder sementara untuk menerima file.',
            ]);
    }

    public function test_a_user_can_add_multiple_photos_to_one_album(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $album = $user->albums()->create(['name' => 'Kuliah']);

        $this->actingAs($user)
            ->post(route('memories.store', $album), [
                'title' => 'Hari pertama',
                'type' => 'photo',
                'files' => [
                    UploadedFile::fake()->image('kelas.jpg'),
                    UploadedFile::fake()->image('teman.jpg'),
                ],
            ])
            ->assertRedirect(route('albums.show', $album));

        $this->assertSame(2, $album->memories()->count());
        $this->assertSame(['kelas.jpg', 'teman.jpg'], $album->memories()->orderBy('id')->pluck('original_filename')->all());

        $this->actingAs($user)
            ->get(route('albums.show', $album))
            ->assertOk()
            ->assertSee('kelas.jpg')
            ->assertSee('teman.jpg');
    }

    public function test_a_user_can_save_a_note_in_an_album(): void
    {
        $user = User::factory()->create();
        $album = $user->albums()->create(['name' => 'Sayang']);

        $this->actingAs($user)
            ->post(route('memories.store', $album), [
                'title' => 'Hari istimewa',
                'type' => 'note',
                'note' => 'Kenangan baik bersama keluarga.',
            ])
            ->assertRedirect(route('albums.show', $album));

        $this->assertDatabaseHas('memories', [
            'user_id' => $user->id,
            'album_id' => $album->id,
            'title' => 'Hari istimewa',
            'note' => 'Kenangan baik bersama keluarga.',
        ]);
    }

    public function test_a_user_can_upload_a_video_of_up_to_one_gibibyte(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $album = $user->albums()->create(['name' => 'Video']);
        $file = UploadedFile::fake()->create('long-memory.mp4', 1_048_576, 'video/mp4');

        $this->actingAs($user)
            ->post(route('memories.store', $album), [
                'title' => 'Video besar',
                'type' => 'video',
                'files' => [$file],
            ])
            ->assertRedirect(route('albums.show', $album));

        $this->assertDatabaseHas('memories', [
            'user_id' => $user->id,
            'album_id' => $album->id,
            'title' => 'Video besar',
            'file_size' => 1_073_741_824,
        ]);

        $memory = $album->memories()->firstOrFail();
        $this->actingAs($user)
            ->get(route('memories.show', [$album, $memory]))
            ->assertOk()
            ->assertSee('class="snowfall"', false)
            ->assertSee('<video class="detail-preview" controls', false);
    }

    public function test_a_user_cannot_upload_a_video_larger_than_one_gibibyte(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $album = $user->albums()->create(['name' => 'Video']);
        $file = UploadedFile::fake()->create('too-large.mp4', 1_048_577, 'video/mp4');

        $this->actingAs($user)
            ->from(route('memories.create', $album))
            ->post(route('memories.store', $album), [
                'title' => 'Video terlalu besar',
                'type' => 'video',
                'files' => [$file],
            ])
            ->assertRedirect(route('memories.create', $album))
            ->assertSessionHasErrors('files.0');

        $this->assertDatabaseMissing('memories', [
            'user_id' => $user->id,
            'title' => 'Video terlalu besar',
        ]);
    }
}
