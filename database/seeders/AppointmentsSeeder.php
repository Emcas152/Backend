<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\StaffMember;
use Carbon\Carbon;

class AppointmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = Patient::all();
        $staffMembers = StaffMember::all();

        if ($patients->isEmpty() || $staffMembers->isEmpty()) {
            $this->command->error('No hay pacientes o staff members. Ejecuta los seeders primero.');
            return;
        }

        $services = [
            'Limpieza Facial',
            'Botox',
            'Relleno de Labios',
            'Láser Depilación',
            'Peeling Químico',
            'Hidrafacial',
            'Masaje Relajante',
            'Tratamiento Anti-edad',
        ];

        $statuses = ['scheduled', 'confirmed', 'completed', 'cancelled'];

        // Generar citas para los últimos 6 meses
        for ($i = 0; $i < 6; $i++) {
            $month = Carbon::now()->subMonths($i);
            
            // 15-25 citas por mes
            $appointmentsCount = rand(15, 25);
            
            for ($j = 0; $j < $appointmentsCount; $j++) {
                $date = $month->copy()->addDays(rand(1, 28));
                
                Appointment::create([
                    'patient_id' => $patients->random()->id,
                    'staff_member_id' => $staffMembers->random()->id,
                    'appointment_date' => $date->format('Y-m-d'),
                    'appointment_time' => Carbon::createFromTime(rand(9, 17), rand(0, 3) * 15)->format('H:i:s'),
                    'service' => $services[array_rand($services)],
                    'status' => $statuses[array_rand($statuses)],
                    'notes' => rand(0, 1) ? 'Paciente con ' . ($i === 0 ? 'próxima' : 'anterior') . ' cita' : null,
                ]);
            }
        }

        // Generar algunas citas para hoy
        for ($i = 0; $i < 5; $i++) {
            Appointment::create([
                'patient_id' => $patients->random()->id,
                'staff_member_id' => $staffMembers->random()->id,
                'appointment_date' => Carbon::today()->format('Y-m-d'),
                'appointment_time' => Carbon::createFromTime(rand(9, 17), rand(0, 3) * 15)->format('H:i:s'),
                'service' => $services[array_rand($services)],
                'status' => ['scheduled', 'confirmed'][array_rand(['scheduled', 'confirmed'])],
                'notes' => 'Cita de hoy',
            ]);
        }

        // Generar citas futuras (próximos 7 días)
        for ($i = 0; $i < 10; $i++) {
            Appointment::create([
                'patient_id' => $patients->random()->id,
                'staff_member_id' => $staffMembers->random()->id,
                'appointment_date' => Carbon::today()->addDays(rand(1, 7))->format('Y-m-d'),
                'appointment_time' => Carbon::createFromTime(rand(9, 17), rand(0, 3) * 15)->format('H:i:s'),
                'service' => $services[array_rand($services)],
                'status' => 'scheduled',
                'notes' => 'Cita próxima',
            ]);
        }

        $this->command->info('Citas creadas exitosamente!');
    }
}
