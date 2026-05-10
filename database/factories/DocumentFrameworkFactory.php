<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\DocumentFramework;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentFrameworkFactory extends Factory
{
    protected $model = DocumentFramework::class;

    public function definition()
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => strtoupper($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'status' => 'active',
            'sort_order' => 0,
            'is_active' => true,
            'created_by' => User::factory()->superuser(),
            'company_id' => Company::factory(),
            'visibility_type' => DocumentFramework::VISIBILITY_PRIVATE,
            'is_system_template' => false,
        ];
    }

    public function systemTemplate()
    {
        return $this->state(function () {
            return [
                'company_id' => null,
                'visibility_type' => DocumentFramework::VISIBILITY_GLOBAL,
                'is_system_template' => true,
            ];
        });
    }
}
