<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Room::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roomTypes = ['Salle de réunion', 'Bureau', 'Open space', 'Salle de conférence', 'Laboratoire', 'Atelier'];
        $roomNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $floors = [1, 2, 3, 4];

        $type = $this->faker->randomElement($roomTypes);
        $name = $this->faker->randomElement($roomNames);
        $floor = $this->faker->randomElement($floors);

        return [
            'nom' => "$type $name$floor",
            'capacite' => $this->faker->numberBetween(2, 30),
            'surface' => $this->faker->optional(0.8)->randomFloat(2, 10, 100),
            'equipment' => $this->faker->optional(0.7)->randomElement([
                'Vidéoprojecteur, tableau blanc, WiFi',
                'Écran TV, système de visioconférence, WiFi',
                'Ordinateurs, imprimante, WiFi',
                'Tableau blanc, paperboard, WiFi',
                'Système audio, WiFi',
                'WiFi, prises électriques',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
