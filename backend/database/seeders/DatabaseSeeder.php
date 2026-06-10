<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Property;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@rental.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_verified' => true,
            ]
        );

        $owner = User::firstOrCreate(
            ['email' => 'owner@rental.com'],
            [
                'name' => 'Mohamed Owner',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'is_verified' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'client@rental.com'],
            [
                'name' => 'Ahmed Client',
                'password' => Hash::make('password'),
                'role' => 'client',
                'is_verified' => true,
            ]
        );

        $samples = [
            [
                'titre' => 'Villa Luxe à Marrakech',
                'description' => 'Villa spacieuse avec piscine et jardin, idéale pour des vacances en famille.',
                'prix_par_nuit' => 850.00,
                'localisation' => 'Marrakech',
                'type' => 'villa',
                'max_guests' => 8,
            ],
            [
                'titre' => 'Appartement Moderne à Casablanca',
                'description' => 'Appartement bien équipé au centre-ville avec vue sur mer.',
                'prix_par_nuit' => 450.00,
                'localisation' => 'Casablanca',
                'type' => 'appartement',
                'max_guests' => 4,
            ],
            [
                'titre' => 'Riad Traditionnel à Fès',
                'description' => 'Riad authentique dans la médina avec terrasse panoramique.',
                'prix_par_nuit' => 600.00,
                'localisation' => 'Fès',
                'type' => 'maison',
                'max_guests' => 6,
            ],
        ];

        foreach ($samples as $data) {
            Property::firstOrCreate(
                [
                    'owner_id' => $owner->id,
                    'titre' => $data['titre'],
                ],
                $data
            );
        }
    }
}
