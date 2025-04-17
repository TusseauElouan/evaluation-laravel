<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Reservation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = Carbon::now()->addDays(rand(-15, 30))->setHour(rand(8, 17))->setMinute(0)->setSecond(0);

        $endDate = (clone $startDate)->addMinutes(rand(1, 8) * 30);

        $meetingTitles = [
            'Réunion d\'équipe',
            'Présentation projet',
            'Entretien candidat',
            'Formation',
            'Brainstorming',
            'Point hebdomadaire',
            'Réunion client',
            'Revue de projet',
            'Comité de direction',
            'Rétrospective sprint',
            'Démonstration produit',
            'Atelier design',
            'Planification',
            'Réunion stratégique',
            'Séance de coaching'
        ];

        return [
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'room_id' => Room::inRandomOrder()->first()->id ?? Room::factory(),
            'debut' => $startDate,
            'fin' => $endDate,
            'titre' => $this->faker->randomElement($meetingTitles),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'is_cancelled' => $this->faker->boolean(10), // 10% de chances d'être annulée
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indiquer que la réservation est pour aujourd'hui
     */
    public function today(): self
    {
        return $this->state(function (array $attributes) {
            $hour = rand(8, 16);
            $startDate = Carbon::today()->setHour($hour)->setMinute(0)->setSecond(0);
            $endDate = (clone $startDate)->addHours(rand(1, 3));

            return [
                'debut' => $startDate,
                'fin' => $endDate,
                'is_cancelled' => false,
            ];
        });
    }

    /**
     * Indiquer que la réservation est pour demain
     */
    public function tomorrow(): self
    {
        return $this->state(function (array $attributes) {
            $hour = rand(8, 16);
            $startDate = Carbon::tomorrow()->setHour($hour)->setMinute(0)->setSecond(0);
            $endDate = (clone $startDate)->addHours(rand(1, 3));

            return [
                'debut' => $startDate,
                'fin' => $endDate,
                'is_cancelled' => false,
            ];
        });
    }

    /**
     * Indiquer que la réservation est annulée
     */
    public function cancelled(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'is_cancelled' => true,
            ];
        });
    }
}
