<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Silber\Bouncer\BouncerFacade as Bouncer;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des utilisateurs employés aléatoires
        $randomUsers = User::factory()->count(10)->create();

        foreach ($randomUsers as $user) {
            Bouncer::assign('employee')->to($user);
            $this->command->info("Employé créé : {$user->first_name} {$user->last_name} ({$user->email})");
        }

        $user = new User;
        $user->nom = 'Tusseau';
        $user->prenom = 'Elouan';
        $user->email = 'tusseauelouan@gmail.com';
        $user->password = Hash::make('password');
        $user->email_verified_at = now();
        $user->save();

        Bouncer::assign('admin')->to($user);
    }
}
