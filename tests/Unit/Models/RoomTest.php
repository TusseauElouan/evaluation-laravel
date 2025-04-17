<?php

namespace Tests\Unit\Models;

use App\Models\Room;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $room = new Room();
        $this->assertEquals(['nom', 'capacite', 'surface', 'equipment'], $room->getFillable());
    }

    /** @test */
    public function it_has_a_relationship_with_reservations()
    {
        $room = Room::factory()->create();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\HasMany', $room->reservations());
    }

    /** @test */
    public function it_can_check_availability()
    {
        // Create a room
        $room = Room::factory()->create();

        // Define time range for testing
        $start = Carbon::today()->setHour(10)->setMinute(0);
        $end = Carbon::today()->setHour(12)->setMinute(0);

        // Initially the room should be available
        $this->assertTrue($room->isAvailable($start, $end));

        // Create a reservation
        Reservation::factory()->create([
            'room_id' => $room->id,
            'debut' => $start,
            'fin' => $end,
            'is_cancelled' => false
        ]);

        // Room should no longer be available
        $this->assertFalse($room->isAvailable($start, $end));
    }

    /** @test */
    public function it_can_exclude_a_specific_reservation_when_checking_availability()
    {
        // Create a room
        $room = Room::factory()->create();

        // Define time range for testing
        $start = Carbon::today()->setHour(10)->setMinute(0);
        $end = Carbon::today()->setHour(12)->setMinute(0);

        // Create a reservation
        $reservation = Reservation::factory()->create([
            'room_id' => $room->id,
            'debut' => $start,
            'fin' => $end,
            'is_cancelled' => false
        ]);

        // Room should be available when excluding the existing reservation
        $this->assertTrue($room->isAvailable($start, $end, $reservation->id));
    }

    /** @test */
    public function cancelled_reservations_dont_affect_availability()
    {
        // Create a room
        $room = Room::factory()->create();

        // Define time range for testing
        $start = Carbon::today()->setHour(10)->setMinute(0);
        $end = Carbon::today()->setHour(12)->setMinute(0);

        // Create a cancelled reservation
        Reservation::factory()->create([
            'room_id' => $room->id,
            'debut' => $start,
            'fin' => $end,
            'is_cancelled' => true
        ]);

        // Room should still be available since the reservation is cancelled
        $this->assertTrue($room->isAvailable($start, $end));
    }

    /** @test */
    public function it_checks_for_overlapping_reservations()
    {
        // Create a room
        $room = Room::factory()->create();

        // Define time range for an existing reservation
        $existingStart = Carbon::today()->setHour(10)->setMinute(0);
        $existingEnd = Carbon::today()->setHour(12)->setMinute(0);

        // Create a reservation
        Reservation::factory()->create([
            'room_id' => $room->id,
            'debut' => $existingStart,
            'fin' => $existingEnd,
            'is_cancelled' => false
        ]);

        // Check various overlapping scenarios

        // Case 1: New reservation starts during existing one
        $newStart1 = Carbon::today()->setHour(11)->setMinute(0);
        $newEnd1 = Carbon::today()->setHour(13)->setMinute(0);
        $this->assertFalse($room->isAvailable($newStart1, $newEnd1));

        // Case 2: New reservation ends during existing one
        $newStart2 = Carbon::today()->setHour(9)->setMinute(0);
        $newEnd2 = Carbon::today()->setHour(11)->setMinute(0);
        $this->assertFalse($room->isAvailable($newStart2, $newEnd2));

        // Case 3: New reservation completely within existing one
        $newStart3 = Carbon::today()->setHour(10)->setMinute(30);
        $newEnd3 = Carbon::today()->setHour(11)->setMinute(30);
        $this->assertFalse($room->isAvailable($newStart3, $newEnd3));

        // Case 4: New reservation completely contains existing one
        $newStart4 = Carbon::today()->setHour(9)->setMinute(0);
        $newEnd4 = Carbon::today()->setHour(13)->setMinute(0);
        $this->assertFalse($room->isAvailable($newStart4, $newEnd4));

        // Case 5: Non-overlapping - before existing
        $newStart5 = Carbon::today()->setHour(8)->setMinute(0);
        $newEnd5 = Carbon::today()->setHour(9)->setMinute(30);
        $this->assertTrue($room->isAvailable($newStart5, $newEnd5));

        // Case 6: Non-overlapping - after existing
        $newStart6 = Carbon::today()->setHour(12)->setMinute(30);
        $newEnd6 = Carbon::today()->setHour(14)->setMinute(0);
        $this->assertTrue($room->isAvailable($newStart6, $newEnd6));
    }

    /** @test */
    public function it_properly_casts_attributes()
    {
        $room = Room::factory()->create([
            'capacite' => '15', // String that should be cast to integer
            'surface' => '25.5', // String that should be cast to float
        ]);

        $this->assertIsInt($room->capacite);
        $this->assertIsFloat($room->surface);
    }
}
