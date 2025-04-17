<?php

namespace Tests\Unit\Models;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $reservation = new Reservation();
        $this->assertEquals([
            'room_id',
            'user_id',
            'debut',
            'fin',
            'titre',
            'description',
            'is_cancelled'
        ], $reservation->getFillable());
    }

    /** @test */
    public function it_belongs_to_a_room()
    {
        $reservation = Reservation::factory()->create();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $reservation->room());
        $this->assertInstanceOf(Room::class, $reservation->room);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $reservation = Reservation::factory()->create();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $reservation->user());
        $this->assertInstanceOf(User::class, $reservation->user);
    }

    /** @test */
    public function it_casts_dates_correctly()
    {
        $reservation = Reservation::factory()->create([
            'debut' => '2025-04-22 09:00:00',
            'fin' => '2025-04-22 11:00:00'
        ]);

        $this->assertInstanceOf(Carbon::class, $reservation->debut);
        $this->assertInstanceOf(Carbon::class, $reservation->fin);
        $this->assertEquals('2025-04-22 09:00:00', $reservation->debut->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-04-22 11:00:00', $reservation->fin->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_determine_if_it_is_upcoming()
    {
        // Future reservation
        $futureReservation = Reservation::factory()->create([
            'debut' => Carbon::now()->addDay(),
            'fin' => Carbon::now()->addDay()->addHour()
        ]);

        // Past reservation
        $pastReservation = Reservation::factory()->create([
            'debut' => Carbon::now()->subDay(),
            'fin' => Carbon::now()->subDay()->addHour()
        ]);

        $this->assertTrue($futureReservation->isUpcoming());
        $this->assertFalse($pastReservation->isUpcoming());
    }

    /** @test */
    public function it_can_determine_if_it_is_in_progress()
    {
        // Create a reservation that spans the current time
        $now = Carbon::now();
        $inProgressReservation = Reservation::factory()->create([
            'debut' => $now->copy()->subHour(),
            'fin' => $now->copy()->addHour()
        ]);

        // Future reservation
        $futureReservation = Reservation::factory()->create([
            'debut' => Carbon::now()->addDay(),
            'fin' => Carbon::now()->addDay()->addHour()
        ]);

        // Past reservation
        $pastReservation = Reservation::factory()->create([
            'debut' => Carbon::now()->subDay(),
            'fin' => Carbon::now()->subDay()->addHour()
        ]);

        $this->assertTrue($inProgressReservation->isInProgress());
        $this->assertFalse($futureReservation->isInProgress());
        $this->assertFalse($pastReservation->isInProgress());
    }

    /** @test */
    public function it_can_determine_if_it_is_past()
    {
        // Past reservation
        $pastReservation = Reservation::factory()->create([
            'debut' => Carbon::now()->subDay(),
            'fin' => Carbon::now()->subDay()->addHour()
        ]);

        // Future reservation
        $futureReservation = Reservation::factory()->create([
            'debut' => Carbon::now()->addDay(),
            'fin' => Carbon::now()->addDay()->addHour()
        ]);

        $this->assertTrue($pastReservation->isPast());
        $this->assertFalse($futureReservation->isPast());
    }

    /** @test */
    public function it_can_calculate_duration()
    {
        // 30 minute duration
        $shortReservation = Reservation::factory()->create([
            'debut' => '2025-04-22 09:00:00',
            'fin' => '2025-04-22 09:30:00'
        ]);

        // 2 hour duration
        $mediumReservation = Reservation::factory()->create([
            'debut' => '2025-04-22 09:00:00',
            'fin' => '2025-04-22 11:00:00'
        ]);

        // 2.5 hour duration
        $longReservation = Reservation::factory()->create([
            'debut' => '2025-04-22 09:00:00',
            'fin' => '2025-04-22 11:30:00'
        ]);

        $this->assertEquals('30 min', $shortReservation->duration);
        $this->assertEquals('2h', $mediumReservation->duration);
        $this->assertEquals('2h30min', $longReservation->duration);
    }

    /** @test */
    public function it_formats_start_date_correctly()
    {
        $reservation = Reservation::factory()->create([
            'debut' => '2025-04-22 09:00:00'
        ]);

        $this->assertEquals('22/04/2025', $reservation->formatted_start_date);
    }

    /** @test */
    public function it_formats_start_and_end_times_correctly()
    {
        $reservation = Reservation::factory()->create([
            'debut' => '2025-04-22 09:15:00',
            'fin' => '2025-04-22 11:30:00'
        ]);

        $this->assertEquals('09:15', $reservation->formatted_start_time);
        $this->assertEquals('11:30', $reservation->formatted_end_time);
    }
}
