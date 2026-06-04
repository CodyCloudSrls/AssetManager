<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use LogicException;

class CustomerContractEvent extends SnipeModel
{
    use HasFactory;

    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_DELETED = 'deleted';

    protected $table = 'customer_contract_events';

    public $timestamps = false;

    protected $fillable = [
        'customer_contract_id',
        'company_id',
        'event_type',
        'actor_id',
        'old_values',
        'new_values',
        'note',
        'remote_ip',
        'user_agent',
        'hash_algorithm',
        'previous_hash',
        'payload_hash',
        'event_hash',
        'created_at',
    ];

    protected $casts = [
        'customer_contract_id' => 'integer',
        'company_id' => 'integer',
        'actor_id' => 'integer',
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('Customer contract audit events are append-only.');
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new LogicException('Customer contract audit events cannot be deleted.');
    }

    public function contract()
    {
        return $this->belongsTo(CustomerContract::class, 'customer_contract_id')->withTrashed();
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    public static function log(CustomerContract $contract, string $eventType, array $oldValues = [], array $newValues = [], ?string $note = null): self
    {
        $eventData = [
            'customer_contract_id' => $contract->id,
            'company_id' => $contract->company_id,
            'event_type' => $eventType,
            'actor_id' => auth()->id(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'note' => $note,
            'remote_ip' => request()?->ip(),
            'user_agent' => request()?->header('User-Agent'),
            'created_at' => now(),
        ];

        return self::create(array_merge($eventData, self::hashes($contract, $eventData)));
    }

    public static function snapshot(CustomerContract $contract): array
    {
        $contract->loadMissing('subscriptions.costLines', 'tenantServices');

        return [
            'company_id' => $contract->company_id,
            'customer_id' => $contract->customer_id,
            'document_id' => $contract->document_id,
            'owner_id' => $contract->owner_id,
            'name' => $contract->name,
            'contract_number' => $contract->contract_number,
            'status' => $contract->status,
            'currency' => $contract->currency,
            'signed_at' => self::dateValue($contract->signed_at),
            'starts_at' => self::dateValue($contract->starts_at),
            'ends_at' => self::dateValue($contract->ends_at),
            'renewal_due_at' => self::dateValue($contract->renewal_due_at),
            'notice_due_at' => self::dateValue($contract->notice_due_at),
            'scope' => $contract->scope,
            'notes' => $contract->notes,
            'tenant_service_ids' => $contract->tenantServices->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
            'subscriptions' => $contract->subscriptions->map(fn (ContractSubscription $subscription) => [
                'id' => $subscription->id,
                'name' => $subscription->name,
                'service_code' => $subscription->service_code,
                'quantity' => (string) $subscription->quantity,
                'unit_price' => (string) $subscription->unit_price,
                'billing_frequency' => $subscription->billing_frequency,
                'starts_at' => self::dateValue($subscription->starts_at),
                'ends_at' => self::dateValue($subscription->ends_at),
                'is_active' => (bool) $subscription->is_active,
                'cost_lines' => $subscription->costLines->map(fn (ContractCostLine $costLine) => [
                    'id' => $costLine->id,
                    'supplier_id' => $costLine->supplier_id,
                    'description' => $costLine->description,
                    'quantity' => (string) $costLine->quantity,
                    'unit_cost' => (string) $costLine->unit_cost,
                    'cost_frequency' => $costLine->cost_frequency,
                    'starts_at' => self::dateValue($costLine->starts_at),
                    'ends_at' => self::dateValue($costLine->ends_at),
                    'is_active' => (bool) $costLine->is_active,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    public static function changes(array $before, array $after): array
    {
        return $before === $after ? [[], []] : [$before, $after];
    }

    private static function hashes(CustomerContract $contract, array $eventData): array
    {
        $previousHash = self::query()
            ->where('customer_contract_id', $contract->id)
            ->whereNotNull('event_hash')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
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
            }
        }

        ksort($payload);

        return $payload;
    }

    private static function stableJson(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function dateValue($value): ?string
    {
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : ($value ?: null);
    }
}
