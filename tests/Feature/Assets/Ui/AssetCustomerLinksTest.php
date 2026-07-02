<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\Manufacturer;
use App\Models\Statuslabel;
use App\Models\User;
use Tests\TestCase;

class AssetCustomerLinksTest extends TestCase
{
    private function customer(Company $company, string $name = 'Bistrò del Corso'): Customer
    {
        $c = new Customer;
        $c->company_id = $company->id;
        $c->name = $name;
        $c->save();

        return $c;
    }

    private function contract(Company $company, Customer $customer, string $name = 'CTR-2026-014'): CustomerContract
    {
        $ct = new CustomerContract;
        $ct->company_id = $company->id;
        $ct->customer_id = $customer->id;
        $ct->name = $name;
        $ct->save();

        return $ct;
    }

    public function test_new_asset_inherits_the_models_contract_and_derives_the_customer(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $company = Company::factory()->create();
        $customer = $this->customer($company);
        $contract = $this->contract($company, $customer);
        $model = AssetModel::factory()->create(['customer_contract_id' => $contract->id]);

        // No customer/contract submitted → inherit the model contract + derive customer.
        $this->post(route('hardware.store'), [
            'asset_tags' => ['1' => 'DOM-1'],
            'model_id' => $model->id,
            'status_id' => Statuslabel::factory()->create()->id,
        ])->assertSessionHasNoErrors();

        $asset = Asset::where('asset_tag', 'DOM-1')->sole();
        $this->assertSame($contract->id, (int) $asset->customer_contract_id);
        $this->assertSame($customer->id, (int) $asset->customer_id);
    }

    public function test_explicit_customer_and_contract_on_the_asset_win(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $company = Company::factory()->create();
        $modelCustomer = $this->customer($company, 'Model Customer');
        $modelContract = $this->contract($company, $modelCustomer, 'MODEL-CTR');
        $model = AssetModel::factory()->create(['customer_contract_id' => $modelContract->id]);

        $assetCustomer = $this->customer($company, 'Asset Customer');
        $assetContract = $this->contract($company, $assetCustomer, 'ASSET-CTR');

        $this->post(route('hardware.store'), [
            'asset_tags' => ['1' => 'DOM-2'],
            'model_id' => $model->id,
            'status_id' => Statuslabel::factory()->create()->id,
            'customer_id' => $assetCustomer->id,
            'customer_contract_id' => $assetContract->id,
        ])->assertSessionHasNoErrors();

        $asset = Asset::where('asset_tag', 'DOM-2')->sole();
        $this->assertSame($assetContract->id, (int) $asset->customer_contract_id);
        $this->assertSame($assetCustomer->id, (int) $asset->customer_id);
    }

    public function test_model_persists_its_default_contract(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $company = Company::factory()->create();
        $customer = $this->customer($company);
        $contract = $this->contract($company, $customer);

        $this->post(route('models.store'), [
            'name' => '.it', 'category_id' => Category::factory()->create(['category_type' => 'asset'])->id,
            'manufacturer_id' => Manufacturer::factory()->create()->id,
            'visibility_type' => 'global', 'customer_contract_id' => $contract->id,
        ])->assertRedirect();

        $this->assertSame($contract->id, (int) AssetModel::where('name', '.it')->firstOrFail()->customer_contract_id);
    }
}
