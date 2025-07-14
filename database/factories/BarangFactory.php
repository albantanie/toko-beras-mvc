<?php

namespace Database\Factories;

use App\Models\Barang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Barang>
 */
class BarangFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Barang::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Beras Premium', 'Beras Medium', 'Beras Ekonomi', 'Beras Organik'];
        $category = fake()->randomElement($categories);

        return [
            'kode_barang' => 'BR' . fake()->unique()->numberBetween(1000, 9999),
            'nama' => $category . ' ' . fake()->randomElement(['Jasmine', 'Basmati', 'Pandan Wangi', 'IR64', 'Ciherang']),
            'kategori' => $category,
            'deskripsi' => fake()->sentence(10),
            'harga_beli' => fake()->numberBetween(8000, 12000),
            'harga_jual' => fake()->numberBetween(10000, 15000),
            'stok' => fake()->numberBetween(10, 100),
            'stok_minimum' => fake()->numberBetween(5, 20),
            'berat_per_unit' => 25, // 1 karung = 25kg
            'gambar' => null,
            'is_active' => true,
            'created_by' => 1, // Default to user ID 1
            'updated_by' => 1, // Default to user ID 1
        ];
    }

    /**
     * Indicate that the barang is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the barang has low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stok' => fake()->numberBetween(1, 5),
        ]);
    }

    /**
     * Indicate that the barang is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stok' => 0,
        ]);
    }
}
