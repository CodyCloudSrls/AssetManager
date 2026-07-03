<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\CustomField;
use App\Models\User;
use Tests\TestCase;

/**
 * Hetrix status flows one-way from an "Indirizzo IP" asset to the "Dominio" assets linked to
 * it via linked_ip_asset_id (AssetObserver::saved). The Hetrix custom field is resolved by
 * name at runtime, so these tests create it explicitly.
 */
class HetrixPropagationTest extends TestCase
{
    private function hetrixColumn(): string
    {
        return CustomField::factory()->create(['name' => 'Hetrix'])->db_column;
    }

    public function test_domain_inherits_the_ips_hetrix_when_it_is_linked(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $col = $this->hetrixColumn();
        $model = AssetModel::factory()->create();

        $ip = Asset::factory()->create(['model_id' => $model->id]);
        $ip->{$col} = '1';
        $ip->save();

        $domain = Asset::factory()->create(['model_id' => $model->id]);
        $domain->linked_ip_asset_id = $ip->id;   // linking it inherits the IP's Hetrix value
        $domain->save();

        $this->assertSame('1', (string) $domain->fresh()->{$col});
    }

    public function test_changing_the_ips_hetrix_propagates_to_linked_domains(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $col = $this->hetrixColumn();
        $model = AssetModel::factory()->create();

        $ip = Asset::factory()->create(['model_id' => $model->id]);
        $ip->{$col} = '0';
        $ip->save();

        $domain = Asset::factory()->create(['model_id' => $model->id, 'linked_ip_asset_id' => $ip->id]);

        $ip->{$col} = '1';   // change on the IP propagates to the linked domain
        $ip->save();

        $this->assertSame('1', (string) $domain->fresh()->{$col});
    }

    public function test_ordinary_asset_save_without_hetrix_changes_is_unaffected(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $this->hetrixColumn();
        $model = AssetModel::factory()->create();

        $asset = Asset::factory()->create(['model_id' => $model->id, 'name' => 'Prima']);
        $asset->name = 'Dopo';
        $asset->save();   // no link, no Hetrix change → no propagation, no error

        $this->assertSame('Dopo', $asset->fresh()->name);
    }
}
