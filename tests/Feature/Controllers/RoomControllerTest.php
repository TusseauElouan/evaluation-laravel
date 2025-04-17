<?php

namespace Tests\Feature\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Carbon\Carbon;

class RoomControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $regularUser;

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

        // Create users
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $this->regularUser = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        // Assign roles
        Bouncer::assign('admin')->to($this->adminUser);
        Bouncer::assign('employee')->to($this->regularUser);

        // Refresh Bouncer's cache
        Bouncer::refresh();
    }

    /** @test */
    public function guests_cannot_access_room_pages()
    {
        $response = $this->get(route('rooms.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('rooms.create'));
        $response->assertRedirect(route('login'));

        $room = Room::factory()->create();

        $response = $this->get(route('rooms.show', $room));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('rooms.edit', $room));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_users_can_view_rooms_index()
    {
        $room = Room::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get(route('rooms.index'));

        $response->assertStatus(200);
        $response->assertViewIs('rooms.index');
        $response->assertViewHas('rooms');
        $response->assertSee($room->nom);
    }

    /** @test */
    public function admin_can_create_a_room()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('rooms.create'));

        $response->assertStatus(200);
        $response->assertViewIs('rooms.create');

        $roomData = [
            'nom' => 'Salle de Test',
            'capacite' => 10,
            'surface' => 25.5,
            'equipment' => 'Tableau blanc, projecteur'
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('rooms.store'), $roomData);

        $response->assertRedirect(route('rooms.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'nom' => 'Salle de Test',
            'capacite' => 10
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_a_room()
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('rooms.create'));

        $response->assertRedirect(route('rooms.index'));
        $response->assertSessionHas('error');

        $roomData = [
            'nom' => 'Salle Interdite',
            'capacite' => 5
        ];

        $response = $this->actingAs($this->regularUser)
            ->post(route('rooms.store'), $roomData);

        $response->assertRedirect(route('rooms.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('rooms', [
            'nom' => 'Salle Interdite'
        ]);
    }

    /** @test */
    public function admin_can_update_a_room()
    {
        $room = Room::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('rooms.edit', $room));

        $response->assertStatus(200);
        $response->assertViewIs('rooms.edit');

        $updatedData = [
            'nom' => 'Salle Mise à Jour',
            'capacite' => 15,
            'surface' => 30,
            'equipment' => 'Tableau blanc, projecteur, système audio'
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('rooms.update', $room), $updatedData);

        $response->assertRedirect(route('rooms.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'nom' => 'Salle Mise à Jour',
            'capacite' => 15
        ]);
    }

    /** @test */
    public function non_admin_cannot_update_a_room()
    {
        $room = Room::factory()->create([
            'nom' => 'Salle Originale'
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('rooms.edit', $room));

        $response->assertRedirect(route('rooms.index'));
        $response->assertSessionHas('error');

        $updatedData = [
            'nom' => 'Tentative de Modification',
            'capacite' => 25
        ];

        $response = $this->actingAs($this->regularUser)
            ->put(route('rooms.update', $room), $updatedData);

        $response->assertRedirect(route('rooms.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'nom' => 'Salle Originale'
        ]);

        $this->assertDatabaseMissing('rooms', [
            'nom' => 'Tentative de Modification'
        ]);
    }

    /** @test */
    public function admin_can_delete_a_room_without_future_reservations()
    {
        $room = Room::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->delete(route('rooms.destroy', $room));

        $response->assertRedirect(route('rooms.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted($room);
    }

    /** @test */
    public function admin_cannot_delete_a_room_with_future_reservations()
    {
        $room = Room::factory()->create();

        // Create a future reservation for this room
        Reservation::factory()->create([
            'room_id' => $room->id,
            'debut' => Carbon::now()->addDays(1),
            'fin' => Carbon::now()->addDays(1)->addHours(2),
            'is_cancelled' => false
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('rooms.destroy', $room));

        $response->assertRedirect(route('rooms.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'deleted_at' => null
        ]);
    }

    /** @test */
    public function non_admin_cannot_delete_a_room()
    {
        $room = Room::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->delete(route('rooms.destroy', $room));

        $response->assertRedirect(route('rooms.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'deleted_at' => null
        ]);
    }

    /** @test */
    public function users_can_view_room_details()
    {
        $room = Room::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get(route('rooms.show', $room));

        $response->assertStatus(200);
        $response->assertViewIs('rooms.show');
        $response->assertViewHas('room');
    }
}
