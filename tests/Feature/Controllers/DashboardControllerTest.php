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
}
