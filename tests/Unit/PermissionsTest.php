<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $employeeUser;

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

        // Create users
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $this->employeeUser = User::factory()->create([
            'email' => 'employee@example.com',
        ]);

        // Assign roles
        Bouncer::assign('admin')->to($this->adminUser);
        Bouncer::assign('employee')->to($this->employeeUser);

        // Set up permissions
        Bouncer::allow('admin')->to('manage-rooms');
        Bouncer::allow('admin')->to('view-dashboard');

        Bouncer::allow('employee')->to('make-reservations');
        Bouncer::allow('employee')->to('view-own-reservations');

        Bouncer::allow('admin')->to('make-reservations');
        Bouncer::allow('admin')->to('view-own-reservations');
        // Refresh Bouncer's cache
        Bouncer::refresh();
    }

    /** @test */
    public function admin_has_correct_role()
    {
        $this->assertTrue($this->adminUser->isA('admin'));
        $this->assertFalse($this->adminUser->isA('employee'));
    }

    /** @test */
    public function employee_has_correct_role()
    {
        $this->assertTrue($this->employeeUser->isA('employee'));
        $this->assertFalse($this->employeeUser->isA('admin'));
    }

    /** @test */
    public function admin_has_correct_permissions()
    {
        $this->assertTrue($this->adminUser->can('manage-rooms'));
        $this->assertTrue($this->adminUser->can('view-dashboard'));

        // Admin should also be able to perform all employee actions
        $this->assertTrue($this->adminUser->can('make-reservations'));
        $this->assertTrue($this->adminUser->can('view-own-reservations'));
    }

    /** @test */
    public function employee_has_correct_permissions()
    {
        $this->assertTrue($this->employeeUser->can('make-reservations'));
        $this->assertTrue($this->employeeUser->can('view-own-reservations'));

        // Employee should not be able to perform admin actions
        $this->assertFalse($this->employeeUser->can('manage-rooms'));
        $this->assertFalse($this->employeeUser->can('view-dashboard'));
    }

    /** @test */
    public function user_can_be_assigned_multiple_roles()
    {
        $multiRoleUser = User::factory()->create([
            'email' => 'multirole@example.com',
        ]);

        // Assign both roles
        Bouncer::assign('admin')->to($multiRoleUser);
        Bouncer::assign('employee')->to($multiRoleUser);

        Bouncer::refresh();

        // Should have both roles
        $this->assertTrue($multiRoleUser->isA('admin'));
        $this->assertTrue($multiRoleUser->isA('employee'));

        // Should have permissions from both roles
        $this->assertTrue($multiRoleUser->can('manage-rooms'));
        $this->assertTrue($multiRoleUser->can('make-reservations'));
    }

    /** @test */
    public function user_without_roles_has_no_permissions()
    {
        $regularUser = User::factory()->create([
            'email' => 'regular@example.com',
        ]);

        // Should not have any roles
        $this->assertFalse($regularUser->isA('admin'));
        $this->assertFalse($regularUser->isA('employee'));

        // Should not have any permissions
        $this->assertFalse($regularUser->can('manage-rooms'));
        $this->assertFalse($regularUser->can('view-dashboard'));
        $this->assertFalse($regularUser->can('make-reservations'));
        $this->assertFalse($regularUser->can('view-own-reservations'));
    }

    /** @test */
    public function roles_can_be_revoked()
    {
        $user = User::factory()->create([
            'email' => 'temporary-admin@example.com',
        ]);

        // Assign admin role
        Bouncer::assign('admin')->to($user);
        Bouncer::refresh();

        // Should have admin role and permissions
        $this->assertTrue($user->isA('admin'));
        $this->assertTrue($user->can('manage-rooms'));

        // Revoke admin role
        Bouncer::retract('admin')->from($user);
        Bouncer::refresh();

        // Should no longer have admin role or permissions
        $this->assertFalse($user->isA('admin'));
        $this->assertFalse($user->can('manage-rooms'));
    }

    /** @test */
    public function can_assign_individual_abilities_to_users()
    {
        $user = User::factory()->create([
            'email' => 'special-user@example.com',
        ]);

        // Give individual permission without role
        Bouncer::allow($user)->to('make-reservations');
        Bouncer::refresh();

        // Should have the specific permission
        $this->assertTrue($user->can('make-reservations'));

        // But not other permissions
        $this->assertFalse($user->can('manage-rooms'));
        $this->assertFalse($user->can('view-dashboard'));
    }

    /** @test */
    public function can_forbid_specific_abilities_despite_role()
    {
        $user = User::factory()->create([
            'email' => 'restricted-admin@example.com',
        ]);

        // Assign admin role
        Bouncer::assign('admin')->to($user);

        // But forbid specific permission
        Bouncer::forbid($user)->to('manage-rooms');
        Bouncer::refresh();

        // Should be admin
        $this->assertTrue($user->isA('admin'));

        // But not able to manage rooms despite being admin
        $this->assertFalse($user->can('manage-rooms'));

        // Should still have other admin permissions
        $this->assertTrue($user->can('view-dashboard'));
    }
}
