<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Driver>
 */
final class DriverFactory extends Factory
{
    protected $model = Driver::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'license_number' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{6}'),
            'license_expiry' => $this->faker->dateTimeBetween('now', '+2 years'),
            'is_active' => $this->faker->boolean(95), // 95% chance of being active
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function expiredLicense(): static
    {
        return $this->state(fn (array $attributes) => [
            'license_expiry' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'license_expiry' => $this->faker->dateTimeBetween('now', '+30 days'),
        ]);
    }
}
