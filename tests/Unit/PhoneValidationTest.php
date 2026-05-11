<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Supplier;
use Tests\TestCase;

class PhoneValidationTest extends TestCase
{
    public function test_three_digit_phone_numbers_are_valid_for_contact_records()
    {
        $company = Company::factory()->make([
            'name' => 'Short Phone Company',
            'phone' => '117',
        ]);

        $this->assertTrue($company->isValid(), $company->getErrors()->toJson());

        $supplier = Supplier::factory()->make([
            'name' => 'Short Phone Supplier',
            'fax' => null,
            'phone' => '117',
            'visibility_type' => Supplier::VISIBILITY_GLOBAL,
        ]);

        $this->assertTrue($supplier->isValid(), $supplier->getErrors()->toJson());

        $customerCompany = Company::factory()->create();
        $customer = new Customer([
            'company_id' => $customerCompany->id,
            'name' => 'Short Phone Customer',
            'phone' => '117',
        ]);

        $this->assertTrue($customer->isValid(), $customer->getErrors()->toJson());
    }
}
