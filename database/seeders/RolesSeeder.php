<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Silber\Bouncer\BouncerFacade as Bouncer;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Création des rôles avec les noms de colonnes corrects
        Bouncer::role()->firstOrCreate([
            'name' => 'admin',
            'title' => 'Administrateur',
        ]);

        Bouncer::role()->firstOrCreate([
            'name' => 'employee',
            'title' => 'Employé',
        ]);

        // Définir les permissions pour les administrateurs
        Bouncer::allow('admin')->to('manage-rooms');
        Bouncer::allow('admin')->to('view-dashboard');

        // Définir les permissions pour les employés
        Bouncer::allow('employee')->to('make-reservations');
        Bouncer::allow('employee')->to('view-own-reservations');
    }
}
