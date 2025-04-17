<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Silber\Bouncer\BouncerFacade as Bouncer;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $user = new User();
        $this->assertEquals([
            'first_name',
            'last_name',
            'email',
            'password',
        ], $user->getFillable());
    }

    /** @test */
    public function it_has_hidden_attributes()
    {
        $user = new User();
        $this->assertEquals([
            'password',
            'remember_token',
        ], $user->getHidden());
    }

    /** @test */
    public function it_has_a_relationship_with_reservations()
    {
        $user = User::factory()->create();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\HasMany', $user->reservations());
    }

    /** @test */
    public function test_it_uses_identity_trait()
    {
        $user = User::factory()->create([
            'prenom' => 'John',
            'nom' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $user->identity);
        $this->assertEquals('JD', $user->initials);
    }

    /** @test */
    public function it_can_determine_role_membership()
    {
        // Create a user
        $user = User::factory()->create();

        // Create admin and employee roles (if they don't exist)
        Bouncer::role()->firstOrCreate([
            'name' => 'admin',
            'title' => 'Administrateur',
        ]);

        Bouncer::role()->firstOrCreate([
            'name' => 'employee',
            'title' => 'Employé',
        ]);

        // Assign 'employee' role to user
        Bouncer::assign('employee')->to($user);

        // Ensure the cache is refreshed
        Bouncer::refresh();

        // Test role checks
        $this->assertTrue($user->isA('employee'));
        $this->assertFalse($user->isA('admin'));

        // Change role to admin
        Bouncer::retract('employee')->from($user);
        Bouncer::assign('admin')->to($user);
        Bouncer::refresh();

        // Test role checks again
        $this->assertTrue($user->isA('admin'));
        $this->assertFalse($user->isA('employee'));
    }

    /** @test */
    public function it_can_have_multiple_reservations()
    {
        $user = User::factory()->create();

        // Create multiple reservations for the user
        Reservation::factory()->count(3)->create([
            'user_id' => $user->id
        ]);

        $this->assertCount(3, $user->reservations);
        $this->assertInstanceOf(Reservation::class, $user->reservations->first());
    }

    /** @test */
    public function it_casts_email_verified_at_to_datetime()
    {
        $user = User::factory()->create();
        $this->assertIsObject($user->email_verified_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }
}
