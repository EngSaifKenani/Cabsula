<?php

namespace Database\Factories;

use App\Models\Manufacturer;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'contact_person' => fake()->name(),
        ];
    }

    /**
     * Configure the model factory.
     *
     * This is the ideal place to handle relationships.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Supplier $supplier) {
            // First, get a random number of existing manufacturers (e.g., 1 to 5)
            // This requires that you have already seeded the manufacturers table.
            $manufacturers = Manufacturer::inRandomOrder()
                ->limit(rand(1, 5))
                ->get();

            // Attach the manufacturers to the newly created supplier
            // This will create records in the 'manufacturer_supplier' pivot table.
            $supplier->manufacturers()->attach($manufacturers);
        });
    }
}
