<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StaffMember;

class StaffMembersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffMembers = [
            [
                'name' => 'Dr. María González',
                'position' => 'Dermatóloga',
                'specialization' => 'Medicina Estética',
                'phone' => '555-0101',
                'email' => 'maria.gonzalez@medicalspa.com',
            ],
            [
                'name' => 'Dr. Carlos Ramírez',
                'position' => 'Cirujano Plástico',
                'specialization' => 'Cirugía Estética',
                'phone' => '555-0102',
                'email' => 'carlos.ramirez@medicalspa.com',
            ],
            [
                'name' => 'Lic. Ana Martínez',
                'position' => 'Esteticista',
                'specialization' => 'Tratamientos Faciales',
                'phone' => '555-0103',
                'email' => 'ana.martinez@medicalspa.com',
            ],
            [
                'name' => 'Lic. Pedro Sánchez',
                'position' => 'Masajista',
                'specialization' => 'Masajes Terapéuticos',
                'phone' => '555-0104',
                'email' => 'pedro.sanchez@medicalspa.com',
            ],
        ];

        foreach ($staffMembers as $staff) {
            StaffMember::create($staff);
        }

        $this->command->info('Staff members creados exitosamente!');
    }
}
