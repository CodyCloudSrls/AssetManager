<?php

namespace Tests\Feature\Actionlogs;

use App\Http\Transformers\ActionlogsTransformer;
use App\Models\Actionlog;
use Tests\TestCase;

class ActionlogsTransformerTest extends TestCase
{
    public function test_transformer_accepts_metadata_without_old_new_change_keys(): void
    {
        $actionlog = Actionlog::factory()->create([
            'action_type' => 'uploaded',
            'log_meta' => json_encode([
                'integrity' => [
                    'status' => 'recorded',
                    'sha256' => 'abc123',
                ],
            ]),
        ]);

        $transformed = (new ActionlogsTransformer)->transformActionlog($actionlog, (object) ['audit_interval' => 12]);

        $this->assertSame('', $transformed['log_meta']['integrity']['old']);
        $this->assertStringContainsString('recorded', html_entity_decode($transformed['log_meta']['integrity']['new']));
        $this->assertStringContainsString('abc123', html_entity_decode($transformed['log_meta']['integrity']['new']));
    }
}
