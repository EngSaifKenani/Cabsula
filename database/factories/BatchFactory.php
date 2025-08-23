<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Batch>
 */
class BatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // First, define the values that other fields will depend on.
        $quantity = fake()->numberBetween(10, 200);
        $unitCost = fake()->randomFloat(2, 1, 500); // Random cost between 1.00 and 500.00

        return [
            // --- Basic Batch Information ---
            'batch_number' => strtoupper(Str::random(3)) . '-' . fake()->numerify('#####'),
            'expiry_date' => fake()->dateTimeBetween('+6 months', '+3 years'),
            'status' => 'available',

            // --- Quantity and Stock ---
            'quantity' => $quantity,
            // The initial stock should always be the same as the quantity.
            'stock' => function (array $attributes) {
                return $attributes['quantity'];
            },

            // --- Financial Information ---
            'unit_cost' => $unitCost,
            // The selling price is calculated based on the cost price plus a random profit margin (e.g., 20% to 50%).
            'unit_price' => function (array $attributes) {
                $profitMargin = fake()->numberBetween(20, 50) / 100; // e.g., 0.20 to 0.50
                return round($attributes['unit_cost'] * (1 + $profitMargin), 2);
            },

            // The total cost for this batch.
            // It's calculated dynamically based on the final quantity and unit cost.
            'total' => function (array $attributes) {
                return $attributes['quantity'] * $attributes['unit_cost'];
            },

            // Note: The foreign keys 'purchase_item_id' and 'drug_id' are not defined here.
            // They should be provided when you call the factory from your Seeder,
            // because a batch must belong to a specific purchase item.
        ];
    }
}
