<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Patient;
use App\Models\Product;
use Carbon\Carbon;

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::all();
        $products = Product::all();

        if ($patients->isEmpty() || $products->isEmpty()) {
            $this->command->warn('No hay pacientes o productos. Ejecuta primero DatabaseSeeder.');
            return;
        }

        // Crear ventas de los últimos 6 meses
        for ($month = 5; $month >= 0; $month--) {
            $monthStart = Carbon::now()->subMonths($month)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($month)->endOfMonth();
            
            // 5-15 ventas por mes
            $salesCount = rand(5, 15);
            
            for ($i = 0; $i < $salesCount; $i++) {
                $patient = $patients->random();
                $randomDate = Carbon::createFromTimestamp(
                    rand($monthStart->timestamp, $monthEnd->timestamp)
                );
                
                $sale = Sale::create([
                    'patient_id' => $patient->id,
                    'payment_method' => collect(['cash', 'card', 'transfer'])->random(),
                    'total' => 0,
                    'created_at' => $randomDate,
                    'updated_at' => $randomDate,
                ]);

                // Agregar 1-3 items por venta
                $itemsCount = rand(1, 3);
                $total = 0;

                for ($j = 0; $j < $itemsCount; $j++) {
                    $product = $products->random();
                    $quantity = rand(1, 3);
                    $unitPrice = $product->price;
                    $totalPrice = $unitPrice * $quantity;
                    
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ]);

                    $total += $totalPrice;
                }

                // Actualizar total
                $sale->update(['total' => $total]);

                // Otorgar puntos de lealtad
                $points = floor($total / 10);
                if ($points > 0) {
                    $patient->addLoyaltyPoints($points);
                }
            }
        }

        $this->command->info('Ventas de ejemplo creadas exitosamente!');
    }
}
