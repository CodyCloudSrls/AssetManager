<?php

namespace App\Support\Files;

use App\Models\Actionlog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FileIntegrity
{
    public static function metadataForStoredFile(string $storagePath, ?UploadedFile $sourceFile = null): array
    {
        try {
            $contents = Storage::get($storagePath);
        } catch (Throwable $exception) {
            return [
                'integrity' => [
                    'status' => 'unavailable',
                    'algorithm' => 'sha256',
                    'storage_disk' => config('filesystems.default'),
                    'storage_path' => $storagePath,
                    'original_filename' => $sourceFile?->getClientOriginalName(),
                    'mime_type' => $sourceFile?->getClientMimeType(),
                    'recorded_at' => now()->toIso8601String(),
                ],
            ];
        }

        return [
            'integrity' => [
                'status' => 'recorded',
                'algorithm' => 'sha256',
                'sha256' => hash('sha256', $contents),
                'size_bytes' => strlen($contents),
                'storage_disk' => config('filesystems.default'),
                'storage_path' => $storagePath,
                'original_filename' => $sourceFile?->getClientOriginalName(),
                'mime_type' => $sourceFile?->getClientMimeType(),
                'recorded_at' => now()->toIso8601String(),
            ],
        ];
    }

    public static function withAuditChain(array $metadata, Actionlog $log): array
    {
        $integrity = $metadata['integrity'] ?? [];
        $previousEventHash = self::previousEventHash($log);

        $chainPayload = [
            'action_type' => $log->action_type,
            'actor_id' => $log->created_by,
            'filename' => $log->filename,
            'item_id' => $log->item_id,
            'item_type' => $log->item_type,
            'previous_event_hash' => $previousEventHash,
            'recorded_at' => $integrity['recorded_at'] ?? now()->toIso8601String(),
            'sha256' => $integrity['sha256'] ?? null,
            'size_bytes' => $integrity['size_bytes'] ?? null,
        ];

        $integrity['previous_event_hash'] = $previousEventHash;
        $integrity['event_hash'] = hash('sha256', self::stableJson($chainPayload));

        $metadata['integrity'] = $integrity;

        return $metadata;
    }

    public static function integrityFromLog(?Actionlog $log): array
    {
        if (! $log || blank($log->log_meta)) {
            return [];
        }

        $metadata = json_decode((string) $log->log_meta, true);

        return is_array($metadata) ? ($metadata['integrity'] ?? []) : [];
    }

    public static function verificationForLog(Actionlog $log): array
    {
        $integrity = self::integrityFromLog($log);

        if (blank($integrity['sha256'] ?? null)) {
            return [
                'status' => 'not_recorded',
                'verified' => null,
                'sha256' => null,
            ];
        }

        $path = $log->uploads_file_path();
        if (! $path || ! Storage::exists($path)) {
            return [
                'status' => 'missing',
                'verified' => false,
                'sha256' => $integrity['sha256'],
            ];
        }

        try {
            $currentHash = hash('sha256', Storage::get($path));
        } catch (Throwable $exception) {
            return [
                'status' => 'unavailable',
                'verified' => false,
                'sha256' => $integrity['sha256'],
            ];
        }

        $matches = hash_equals((string) $integrity['sha256'], $currentHash);

        return [
            'status' => $matches ? 'verified' : 'mismatch',
            'verified' => $matches,
            'sha256' => $integrity['sha256'],
            'current_sha256' => $currentHash,
        ];
    }

    public static function uploadDeletionRecorded(Actionlog $log): bool
    {
        return Actionlog::query()
            ->where('item_type', $log->item_type)
            ->where('item_id', $log->item_id)
            ->where('filename', $log->filename)
            ->where('action_type', 'upload deleted')
            ->exists();
    }

    private static function previousEventHash(Actionlog $log): ?string
    {
        $previousLog = Actionlog::query()
            ->where('item_type', $log->item_type)
            ->where('item_id', $log->item_id)
            ->whereIn('action_type', ['uploaded', 'upload deleted'])
            ->whereNotNull('log_meta')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->first(function (Actionlog $candidate) {
                return (bool) Arr::get(self::integrityFromLog($candidate), 'event_hash');
            });

        return $previousLog ? Arr::get(self::integrityFromLog($previousLog), 'event_hash') : null;
    }

    private static function stableJson(array $payload): string
    {
        ksort($payload);

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
