<?php

namespace Database\Factories;

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
            'sort_order' => 0,
            'is_active' => true,
            'created_by' => User::factory()->superuser(),
            'company_id' => null,
            'visibility_type' => DocumentFramework::VISIBILITY_GLOBAL,
        ];
    }
}
