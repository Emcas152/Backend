<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Patient;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = User::create([
            'name' => 'Admin CRM',
            'email' => 'admin@crm.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Create Doctor
        $doctor = User::create([
            'name' => 'Dr. María García',
            'email' => 'doctor@crm.com',
            'password' => Hash::make('doctor123'),
            'role' => 'doctor',
        ]);

        // Create Staff
        $staff = User::create([
            'name' => 'Juan Pérez',
            'email' => 'staff@crm.com',
            'password' => Hash::make('staff123'),
            'role' => 'staff',
        ]);

        // Create Patient Users
        $patient1User = User::create([
            'name' => 'Ana Martínez',
            'email' => 'ana@example.com',
            'password' => Hash::make('patient123'),
            'role' => 'patient',
        ]);

        $patient2User = User::create([
            'name' => 'Carlos López',
            'email' => 'carlos@example.com',
            'password' => Hash::make('patient123'),
            'role' => 'patient',
        ]);

        // Create Patients
        $patient1 = Patient::create([
            'user_id' => $patient1User->id,
            'name' => 'Ana Martínez',
            'email' => 'ana@example.com',
            'phone' => '+34 600 123 456',
            'birthday' => '1985-03-15',
            'address' => 'Calle Mayor 123, Madrid',
            'loyalty_points' => 50,
        ]);
        $patient1->generateQRCode();

        $patient2 = Patient::create([
            'user_id' => $patient2User->id,
            'name' => 'Carlos López',
            'email' => 'carlos@example.com',
            'phone' => '+34 600 654 321',
            'birthday' => '1990-07-22',
            'address' => 'Avenida Libertad 45, Barcelona',
            'loyalty_points' => 120,
        ]);
        $patient2->generateQRCode();

        // Create Products
        Product::create([
            'name' => 'Tratamiento Facial Premium',
            'sku' => 'TFP-001',
            'description' => 'Tratamiento facial completo con hidratación profunda',
            'price' => 89.99,
            'stock' => 0,
            'type' => 'service',
            'active' => true,
        ]);

        Product::create([
            'name' => 'Masaje Relajante 60min',
            'sku' => 'MR-060',
            'description' => 'Masaje relajante de cuerpo completo',
            'price' => 65.00,
            'stock' => 0,
            'type' => 'service',
            'active' => true,
        ]);

        Product::create([
            'name' => 'Depilación Láser - Piernas',
            'sku' => 'DL-PIER',
            'description' => 'Sesión de depilación láser en piernas completas',
            'price' => 120.00,
            'stock' => 0,
            'type' => 'service',
            'active' => true,
        ]);

        Product::create([
            'name' => 'Crema Hidratante Facial',
            'sku' => 'CHF-001',
            'description' => 'Crema hidratante de alta calidad para todo tipo de piel',
            'price' => 45.50,
            'stock' => 25,
            'type' => 'product',
            'active' => true,
        ]);

        Product::create([
            'name' => 'Serum Antiarrugas',
            'sku' => 'SA-002',
            'description' => 'Serum con ácido hialurónico y vitamina C',
            'price' => 78.00,
            'stock' => 15,
            'type' => 'product',
            'active' => true,
        ]);

        Product::create([
            'name' => 'Aceite Corporal Relajante',
            'sku' => 'ACR-003',
            'description' => 'Aceite con extractos naturales para masajes',
            'price' => 32.00,
            'stock' => 30,
            'type' => 'product',
            'active' => true,
        ]);

        Product::create([
            'name' => 'Exfoliante Corporal',
            'sku' => 'EC-004',
            'description' => 'Exfoliante corporal con sales marinas',
            'price' => 28.50,
            'stock' => 20,
            'type' => 'product',
            'active' => true,
        ]);

        Product::create([
            'name' => 'Mascarilla Purificante',
            'sku' => 'MP-005',
            'description' => 'Mascarilla facial de arcilla verde',
            'price' => 24.00,
            'stock' => 18,
            'type' => 'product',
            'active' => true,
        ]);

        echo "✅ Database seeded successfully!\n";
        echo "📧 Admin: admin@crm.com / admin123\n";
        echo "👨‍⚕️ Doctor: doctor@crm.com / doctor123\n";
        echo "👤 Staff: staff@crm.com / staff123\n";
        echo "👥 Patients: ana@example.com, carlos@example.com / patient123\n";
    }
}
