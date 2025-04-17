<?php

namespace Tests\Feature\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ReservationConfirmation;
use App\Notifications\ReservationCancelled;

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $regularUser;
    protected $anotherUser;
    protected $room;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Bouncer::role()->firstOrCreate([
            'name' => 'admin',
            'title' => 'Administrateur',
        ]);

        Bouncer::role()->firstOrCreate([
            'name' => 'employee',
            'title' => 'Employé',
        ]);

        // Set up permissions
        Bouncer::allow('admin')->to('manage-rooms');
        Bouncer::allow('employee')->to('make-reservations');
        Bouncer::allow('employee')->to('view-own-reservations');

        // Create users
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'prenom' => 'Admin',
            'nom' => 'User'
        ]);

        $this->regularUser = User::factory()->create([
            'email' => 'user@example.com',
            'prenom' => 'Regular',
            'nom' => 'User'
        ]);

        $this->anotherUser = User::factory()->create([
            'email' => 'another@example.com',
            'prenom' => 'Another',
            'nom' => 'User'
        ]);

        // Assign roles
        Bouncer::assign('admin')->to($this->adminUser);
        Bouncer::assign('employee')->to($this->regularUser);
        Bouncer::assign('employee')->to($this->anotherUser);

        // Refresh Bouncer's cache
        Bouncer::refresh();

        // Create a test room
        $this->room = Room::factory()->create([
            'nom' => 'Test Room',
            'capacite' => 10
        ]);
    }

    /** @test */
    public function guests_cannot_access_reservation_pages()
    {
        $response = $this->get(route('reservations.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('reservations.create'));
        $response->assertRedirect(route('login'));

        $reservation = Reservation::factory()->create();

        $response = $this->get(route('reservations.show', $reservation));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('reservations.edit', $reservation));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_users_can_view_their_reservations()
    {
        // Create reservations for the regular user
        $userReservation = Reservation::factory()->create([
            'user_id' => $this->regularUser->id,
            'room_id' => $this->room->id,
            'titre' => 'User Reservation'
        ]);

        // Create reservations for another user
        $otherReservation = Reservation::factory()->create([
            'user_id' => $this->anotherUser->id,
            'room_id' => $this->room->id,
            'titre' => 'Other User Reservation'
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('reservations.index'));

        $response->assertStatus(200);
        $response->assertViewIs('reservations.index');
        $response->assertSee('User Reservation');
        $response->assertDontSee('Other User Reservation');
    }

    /** @test */
    public function admin_can_view_all_reservations()
    {
        // Create reservations for different users
        $userReservation = Reservation::factory()->create([
            'user_id' => $this->regularUser->id,
            'room_id' => $this->room->id,
            'titre' => 'User Reservation'
        ]);

        $otherReservation = Reservation::factory()->create([
            'user_id' => $this->anotherUser->id,
            'room_id' => $this->room->id,
            'titre' => 'Other User Reservation'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('reservations.index'));

        $response->assertStatus(200);
        $response->assertViewIs('reservations.index');
        $response->assertSee('User Reservation');
        $response->assertSee('Other User Reservation');
    }

    /** @test */
    public function users_can_create_reservations()
    {
        Notification::fake();

        $response = $this->actingAs($this->regularUser)
            ->get(route('reservations.create'));

        $response->assertStatus(200);
        $response->assertViewIs('reservations.create');

        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $reservationData = [
            'room_id' => $this->room->id,
            'reservation_date' => $tomorrow,
            'debut' => '09:00',
            'fin' => '11:00',
            'titre' => 'Test Reservation',
            'description' => 'This is a test reservation'
        ];

        $response = $this->actingAs($this->regularUser)
            ->post(route('reservations.store'), $reservationData);

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'room_id' => $this->room->id,
            'user_id' => $this->regularUser->id,
            'titre' => 'Test Reservation',
            'description' => 'This is a test reservation'
        ]);

        Notification::assertSentTo(
            $this->regularUser,
            ReservationConfirmation::class
        );
    }

    /** @test */
    public function system_prevents_double_booking()
    {
        // Create an existing reservation
        $existingReservation = Reservation::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->anotherUser->id,
            'debut' => Carbon::tomorrow()->setHour(10)->setMinute(0),
            'fin' => Carbon::tomorrow()->setHour(12)->setMinute(0),
            'is_cancelled' => false
        ]);

        // Try to book the same room at an overlapping time
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $reservationData = [
            'room_id' => $this->room->id,
            'reservation_date' => $tomorrow,
            'debut' => '11:00',
            'fin' => '13:00',
            'titre' => 'Overlapping Reservation',
            'description' => 'This should not be allowed'
        ];

        $response = $this->actingAs($this->regularUser)
            ->post(route('reservations.store'), $reservationData);

        $response->assertSessionHasErrors('debut');

        $this->assertDatabaseMissing('reservations', [
            'titre' => 'Overlapping Reservation',
            'user_id' => $this->regularUser->id
        ]);
    }

    /** @test */
    public function users_can_view_their_own_reservations()
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->regularUser->id,
            'room_id' => $this->room->id
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('reservations.show', $reservation));

        $response->assertStatus(200);
        $response->assertViewIs('reservations.show');
        $response->assertViewHas('reservation');
    }

    /** @test */
    public function users_cannot_view_others_reservations()
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->anotherUser->id,
            'room_id' => $this->room->id
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('reservations.show', $reservation));

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function admin_can_view_any_reservation()
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->regularUser->id,
            'room_id' => $this->room->id
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('reservations.show', $reservation));

        $response->assertStatus(200);
        $response->assertViewIs('reservations.show');
        $response->assertViewHas('reservation');
    }

    /** @test */
    public function users_can_edit_their_own_future_reservations()
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->regularUser->id,
            'room_id' => $this->room->id,
            'debut' => Carbon::tomorrow()->setHour(10)->setMinute(0),
            'fin' => Carbon::tomorrow()->setHour(12)->setMinute(0),
            'titre' => 'Original Title'
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('reservations.edit', $reservation));

        $response->assertStatus(200);
        $response->assertViewIs('reservations.edit');

        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $updatedData = [
            'room_id' => $this->room->id,
            'reservation_date' => $tomorrow,
            'debut' => '14:00',
            'fin' => '16:00',
            'titre' => 'Updated Title',
            'description' => 'Updated Description'
        ];

        $response = $this->actingAs($this->regularUser)
            ->put(route('reservations.update', $reservation), $updatedData);

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'titre' => 'Updated Title',
            'description' => 'Updated Description'
        ]);
    }

    /** @test */
    public function users_cannot_edit_past_reservations()
    {
        $pastReservation = Reservation::factory()->create([
            'user_id' => $this->regularUser->id,
            'room_id' => $this->room->id,
            'debut' => Carbon::yesterday()->setHour(10)->setMinute(0),
            'fin' => Carbon::yesterday()->setHour(12)->setMinute(0),
            'titre' => 'Past Reservation'
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('reservations.edit', $pastReservation));

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function users_cannot_edit_others_reservations()
    {
        $otherReservation = Reservation::factory()->create([
            'user_id' => $this->anotherUser->id,
            'room_id' => $this->room->id,
            'debut' => Carbon::tomorrow()->setHour(10)->setMinute(0),
            'fin' => Carbon::tomorrow()->setHour(12)->setMinute(0),
            'titre' => 'Other User Reservation'
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('reservations.edit', $otherReservation));

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function admin_can_edit_any_future_reservation()
    {
        $reservation = Reservation::factory()->create([
            'user_id' => $this->regularUser->id,
            'room_id' => $this->room->id,
            'debut' => Carbon::tomorrow()->setHour(10)->setMinute(0),
            'fin' => Carbon::tomorrow()->setHour(12)->setMinute(0),
            'titre' => 'Regular User Reservation'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('reservations.edit', $reservation));

        $response->assertStatus(200);
        $response->assertViewIs('reservations.edit');

        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $updatedData = [
            'room_id' => $this->room->id,
            'reservation_date' => $tomorrow,
            'debut' => '14:00',
            'fin' => '16:00',
            'titre' => 'Admin Updated Title',
            'description' => 'Admin Updated Description'
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('reservations.update', $reservation), $updatedData);

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'titre' => 'Admin Updated Title',
            'description' => 'Admin Updated Description'
        ]);
    }

    /** @test */
    public function users_can_cancel_their_own_future_reservations()
    {
        Notification::fake();

        $reservation = Reservation::factory()->create([
            'user_id' => $this->regularUser->id,
            'room_id' => $this->room->id,
            'debut' => Carbon::tomorrow()->setHour(10)->setMinute(0),
            'fin' => Carbon::tomorrow()->setHour(12)->setMinute(0),
            'is_cancelled' => false
        ]);

        $response = $this->actingAs($this->regularUser)
            ->post(route('reservations.cancel', $reservation));

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'is_cancelled' => true
        ]);

        Notification::assertSentTo(
            $this->regularUser,
            ReservationCancelled::class
        );
    }

    /** @test */
    public function users_cannot_cancel_past_reservations()
    {
        $pastReservation = Reservation::factory()->create([
            'user_id' => $this->regularUser->id,
            'room_id' => $this->room->id,
            'debut' => Carbon::yesterday()->setHour(10)->setMinute(0),
            'fin' => Carbon::yesterday()->setHour(12)->setMinute(0),
            'is_cancelled' => false
        ]);

        $response = $this->actingAs($this->regularUser)
            ->post(route('reservations.cancel', $pastReservation));

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('reservations', [
            'id' => $pastReservation->id,
            'is_cancelled' => false
        ]);
    }

    /** @test */
    public function users_cannot_cancel_others_reservations()
    {
        $otherReservation = Reservation::factory()->create([
            'user_id' => $this->anotherUser->id,
            'room_id' => $this->room->id,
            'debut' => Carbon::tomorrow()->setHour(10)->setMinute(0),
            'fin' => Carbon::tomorrow()->setHour(12)->setMinute(0),
            'is_cancelled' => false
        ]);

        $response = $this->actingAs($this->regularUser)
            ->post(route('reservations.cancel', $otherReservation));

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('reservations', [
            'id' => $otherReservation->id,
            'is_cancelled' => false
        ]);
    }

    /** @test */
    public function admin_can_cancel_any_future_reservation()
    {
        Notification::fake();

        $reservation = Reservation::factory()->create([
            'user_id' => $this->regularUser->id,
            'room_id' => $this->room->id,
            'debut' => Carbon::tomorrow()->setHour(10)->setMinute(0),
            'fin' => Carbon::tomorrow()->setHour(12)->setMinute(0),
            'is_cancelled' => false
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('reservations.cancel', $reservation));

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'is_cancelled' => true
        ]);

        Notification::assertSentTo(
            $this->regularUser,
            ReservationCancelled::class
        );
    }

    /** @test */
    public function check_availability_returns_correct_data()
    {
        // Create some reservations for the test room
        $reservation1 = Reservation::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->regularUser->id,
            'debut' => Carbon::tomorrow()->setHour(10)->setMinute(0),
            'fin' => Carbon::tomorrow()->setHour(12)->setMinute(0),
            'is_cancelled' => false,
            'titre' => 'Morning Reservation'
        ]);

        $reservation2 = Reservation::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->anotherUser->id,
            'debut' => Carbon::tomorrow()->setHour(14)->setMinute(0),
            'fin' => Carbon::tomorrow()->setHour(16)->setMinute(0),
            'is_cancelled' => false,
            'titre' => 'Afternoon Reservation'
        ]);

        // Create a cancelled reservation that should not appear
        $cancelled = Reservation::factory()->create([
            'room_id' => $this->room->id,
            'user_id' => $this->regularUser->id,
            'debut' => Carbon::tomorrow()->setHour(12)->setMinute(0),
            'fin' => Carbon::tomorrow()->setHour(14)->setMinute(0),
            'is_cancelled' => true,
            'titre' => 'Cancelled Reservation'
        ]);

        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->actingAs($this->regularUser)
            ->get(route('reservations.check-availability', [
                'room_id' => $this->room->id,
                'date' => $tomorrow
            ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'room',
            'reservations'
        ]);

        $jsonData = $response->json();

        // Should have 2 non-cancelled reservations
        $this->assertCount(2, $jsonData['reservations']);

        // Check that the reservations are correct
        $titles = array_column($jsonData['reservations'], 'titre');
        $this->assertContains('Morning Reservation', $titles);
        $this->assertContains('Afternoon Reservation', $titles);
        $this->assertNotContains('Cancelled Reservation', $titles);
    }
}
