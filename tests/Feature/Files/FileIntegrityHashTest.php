<?php

namespace Tests\Feature\Files;

use App\Support\Files\FileIntegrity;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The integrity hashing was changed to stream the stored file (constant memory) instead of
 * loading the whole file into a PHP string. These tests pin the two things that must not change:
 * the resulting sha256 is byte-identical to the old one-shot hash (so previously recorded hashes
 * still verify), and the size is correct.
 */
class FileIntegrityHashTest extends TestCase
{
    public function test_streamed_sha256_matches_the_one_shot_hash_and_size(): void
    {
        Storage::fake('local');

        // Random, multi-chunk payload so the streaming reader is actually exercised.
        $bytes = random_bytes(512 * 1024);
        $path = 'private_uploads/contracts/contract-1-abcd1234-x.pdf';
        Storage::put($path, $bytes);

        $meta = FileIntegrity::metadataForStoredFile($path)['integrity'];

        $this->assertSame('recorded', $meta['status']);
        $this->assertSame('sha256', $meta['algorithm']);
        // Parity: the streamed hash equals the previous hash('sha256', Storage::get($path)).
        $this->assertSame(hash('sha256', $bytes), $meta['sha256']);
        $this->assertSame(strlen($bytes), $meta['size_bytes']);
    }

    public function test_missing_file_is_reported_unavailable_not_fatal(): void
    {
        Storage::fake('local');

        $meta = FileIntegrity::metadataForStoredFile('private_uploads/contracts/nope.pdf')['integrity'];

        $this->assertSame('unavailable', $meta['status']);
        $this->assertArrayNotHasKey('sha256', $meta);
    }
}
