<?php

namespace Tests\Feature\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Carbon\Carbon;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $regularUser;
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
        Bouncer::allow('admin')->to('view-dashboard');
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

        // Assign roles
        Bouncer::assign('admin')->to($this->adminUser);
        Bouncer::assign('employee')->to($this->regularUser);

        // Refresh Bouncer's cache
        Bouncer::refresh();

        // Create a test room
        $this->room = Room::factory()->create([
            'nom' => 'Test Room',
            'capacite' => 10
        ]);
    }

    /** @test */
    public function guests_cannot_access_dashboard()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_sees_admin_dashboard()
    {
        // Create some sample data for the dashboard
        Room::factory()->count(3)->create();
        User::factory()->count(5)->create();

        // Create some reservations
        Reservation::factory()->count(2)->create([
            'debut' => Carbon::today()->setHour(10),
            'fin' => Carbon::today()->setHour(12),
        ]);

        Reservation::factory()->count(3)->create([
            'debut' => Carbon::tomorrow()->setHour(10),
            'fin' => Carbon::tomorrow()->setHour(12),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('home'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.admin');

        // Check if the view has the necessary variables
        $response->assertViewHas('totalRooms');
        $response->assertViewHas('totalUsers');
        $response->assertViewHas('totalReservations');
        $response->assertViewHas('reservationsByDay');
        $response->assertViewHas('rooms');
        $response->assertViewHas('todayReservations');
    }

    /** @test */
    public function regular_user_sees_employee_dashboard()
    {
        // Create some upcoming reservations for the user
        Reservation::factory()->count(2)->create([
            'user_id' => $this->regularUser->id,
            'room_id' => $this->room->id,
            'debut' => Carbon::tomorrow()->setHour(10),
            'fin' => Carbon::tomorrow()->setHour(12),
        ]);

        // Create some past reservations for the user
        Reservation::factory()->count(2)->create([
            'user_id' => $this->regularUser->id,
            'room_id' => $this->room->id,
            'debut' => Carbon::yesterday()->setHour(10),
            'fin' => Carbon::yesterday()->setHour(12),
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('home'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.employee');

        // Check if the view has the necessary variables
        $response->assertViewHas('upcomingReservations');
        $response->assertViewHas('pastReservations');
        $response->assertViewHas('rooms');
    }

    /** @test */
    public function admin_dashboard_shows_correct_statistics()
    {
        // Create specific test data
        $roomCount = 5;
        $userCount = 10;
        $reservationCount = 15;

        Room::factory()->count($roomCount - 1)->create(); // -1 because we already created one in setUp
        User::factory()->count($userCount - 2)->create(); // -2 because we already created admin and regular user
        Reservation::factory()->count($reservationCount)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('home'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.admin');

        // Get the view data
        $viewData = $response->viewData();

        // Check that the statistics are correct
        $this->assertEquals($roomCount, $viewData['totalRooms']);
        $this->assertEquals($userCount, $viewData['totalUsers']);
        $this->assertEquals($reservationCount, $viewData['totalReservations']);
    }

    /** @test */
    public function employee_dashboard_only_shows_user_reservations()
    {
        // Create reservations for the current user
        $userReservations = Reservation::factory()->count(3)->create([
            'user_id' => $this->regularUser->id,
            'room_id' => $this->room->id,
            'debut' => Carbon::tomorrow(),
            'fin' => Carbon::tomorrow()->addHours(2),
        ]);

        // Create reservations for another user
        $otherUser = User::factory()->create();
        Bouncer::assign('employee')->to($otherUser);

        $otherReservations = Reservation::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'room_id' => $this->room->id,
            'debut' => Carbon::tomorrow(),
            'fin' => Carbon::tomorrow()->addHours(2),
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('home'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.employee');

        // Get the view data
        $viewData = $response->viewData();

        // Check that only the current user's reservations are shown
        $this->assertCount(3, $viewData['upcomingReservations']);

        // Check that all reservations belong to the current user
        foreach ($viewData['upcomingReservations'] as $reservation) {
            $this->assertEquals($this->regularUser->id, $reservation->user_id);
        }
    }

    /** @test */
    public function admin_dashboard_shows_todays_reservations()
    {
        // Create reservations for today
        $todayReservations = Reservation::factory()->count(2)->create([
            'debut' => Carbon::today()->setHour(10),
            'fin' => Carbon::today()->setHour(12),
        ]);

        // Create reservations for tomorrow
        $tomorrowReservations = Reservation::factory()->count(3)->create([
            'debut' => Carbon::tomorrow()->setHour(10),
            'fin' => Carbon::tomorrow()->setHour(12),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('home'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.admin');

        // Get the view data
        $viewData = $response->viewData();

        // Check that only today's reservations are shown
        $this->assertCount(2, $viewData['todayReservations']);

        // Check that all reservations are for today
        foreach ($viewData['todayReservations'] as $reservation) {
            $this->assertTrue(Carbon::parse($reservation->debut)->isToday());
        }
    }

    /** @test */
    public function admin_dashboard_shows_rooms_with_reservation_counts()
    {
        // Create additional rooms
        $room1 = Room::factory()->create(['nom' => 'Room 1']);
        $room2 = Room::factory()->create(['nom' => 'Room 2']);

        // Create reservations for each room
        Reservation::factory()->count(3)->create(['room_id' => $room1->id]);
        Reservation::factory()->count(2)->create(['room_id' => $room2->id]);
        Reservation::factory()->count(1)->create(['room_id' => $this->room->id]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('home'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.admin');

        // Get the view data
        $viewData = $response->viewData();

        // Check that rooms are present with correct reservation counts
        $this->assertCount(3, $viewData['rooms']);

        $roomStats = [];
        foreach ($viewData['rooms'] as $room) {
            $roomStats[$room->id] = $room->reservations_count;
        }

        $this->assertEquals(3, $roomStats[$room1->id]);
        $this->assertEquals(2, $roomStats[$room2->id]);
        $this->assertEquals(1, $roomStats[$this->room->id]);
    }
}
