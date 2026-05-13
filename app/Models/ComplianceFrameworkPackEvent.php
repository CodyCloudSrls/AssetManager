<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

class ComplianceFrameworkPackEvent extends SnipeModel
{
    use HasFactory;

    public const SCOPE_SYSTEM = 'system';
    public const SCOPE_TENANT = 'tenant';

    public const EVENT_SYSTEM_SYNC = 'system_sync';
    public const EVENT_TENANT_SYNC = 'tenant_sync';
    public const EVENT_TENANT_BOOTSTRAP = 'tenant_bootstrap';

    protected $table = 'compliance_framework_pack_events';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'document_framework_id',
        'scope',
        'event_type',
        'pack_key',
        'pack_version',
        'pack_checksum',
        'actor_id',
        'diff_before',
        'diff_after',
        'result_summary',
        'remote_ip',
        'user_agent',
        'hash_algorithm',
        'previous_hash',
        'payload_hash',
        'event_hash',
        'created_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'company_id' => 'integer',
        'document_framework_id' => 'integer',
        'actor_id' => 'integer',
        'diff_before' => 'array',
        'diff_after' => 'array',
        'result_summary' => 'array',
        'created_at' => 'datetime',
    ];

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('Compliance framework pack events are append-only.');
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new LogicException('Compliance framework pack events cannot be deleted.');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')->withTrashed();
    }

    public function framework()
    {
        return $this->belongsTo(DocumentFramework::class, 'document_framework_id')->withTrashed();
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    public static function record(string $eventType, string $scope, string $packKey, array $pack, array $context = []): ?self
    {
        if (! Schema::hasTable('compliance_framework_pack_events')) {
            return null;
        }

        $resultSummary = $context['result_summary'] ?? null;
        $sourceRegister = $pack['source_register'] ?? null;

        if (is_array($sourceRegister)) {
            $resultSummary = is_array($resultSummary) ? $resultSummary : [];
            $resultSummary['_pack_source'] = [
                'key' => $pack['source_register_key'] ?? null,
                'status' => $sourceRegister['status'] ?? null,
                'scope' => $sourceRegister['scope'] ?? null,
                'jurisdiction' => $sourceRegister['jurisdiction'] ?? data_get($pack, 'framework.jurisdiction'),
                'last_checked_at' => $sourceRegister['last_checked_at'] ?? null,
                'sources' => $sourceRegister['sources'] ?? [],
            ];
        }

        $eventData = [
            'tenant_id' => $context['tenant_id'] ?? null,
            'company_id' => $context['company_id'] ?? null,
            'document_framework_id' => $context['document_framework_id'] ?? null,
            'scope' => $scope,
            'event_type' => $eventType,
            'pack_key' => $packKey,
            'pack_version' => $pack['pack_version'] ?? data_get($pack, 'framework.version'),
            'pack_checksum' => self::checksumForPack($pack),
            'actor_id' => auth()->id(),
            'diff_before' => $context['diff_before'] ?? null,
            'diff_after' => $context['diff_after'] ?? null,
            'result_summary' => $resultSummary,
            'remote_ip' => request()?->ip(),
            'user_agent' => request()?->header('User-Agent'),
            'created_at' => now(),
        ];

        return DB::transaction(fn () => self::create(array_merge($eventData, self::hashes($eventData))));
    }

    public static function checksumForPack(array $pack): string
    {
        return hash('sha256', self::stableJson(self::canonicalPayload($pack)));
    }

    private static function hashes(array $eventData): array
    {
        $previousHash = self::query()
            ->whereNotNull('event_hash')
            ->where('scope', $eventData['scope'])
            ->where('pack_key', $eventData['pack_key'])
            ->where(function ($query) use ($eventData) {
                if ($eventData['scope'] === self::SCOPE_TENANT) {
                    $query->where('tenant_id', $eventData['tenant_id']);
                } else {
                    $query->whereNull('tenant_id');
                }
            })
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('event_hash');

        $payloadHash = hash('sha256', self::stableJson(self::canonicalPayload($eventData)));
        $eventHash = hash('sha256', self::stableJson([
            'algorithm' => 'sha256',
            'payload_hash' => $payloadHash,
            'previous_hash' => $previousHash,
        ]));

        return [
            'hash_algorithm' => 'sha256',
            'previous_hash' => $previousHash,
            'payload_hash' => $payloadHash,
            'event_hash' => $eventHash,
        ];
    }

    private static function canonicalPayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $payload[$key] = $value->format(DATE_ATOM);
            } elseif (is_array($value)) {
                $payload[$key] = self::canonicalPayload($value);
            } elseif (is_bool($value)) {
                $payload[$key] = $value ? 1 : 0;
            }
        }

        ksort($payload);

        return $payload;
    }

    private static function stableJson(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
