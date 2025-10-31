<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Sale::query()->with(['items.product', 'patient', 'user']);

        // Filter by date range
        if ($request->has('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Filter by patient
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $sales = $query->latest()->paginate(20);
        return response()->json($sales);
    }

    /**
     * Store a newly created resource in storage (TPV)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'nullable|exists:patients,id',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,card,transfer,other',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $total = 0;
            
            // Create sale
            $sale = Sale::create([
                'user_id' => $request->user()->id,
                'patient_id' => $validated['patient_id'] ?? null,
                'total' => 0,
                'discount' => $validated['discount'] ?? 0,
                'payment_method' => $validated['payment_method'],
                'status' => 'completed',
            ]);

            // Process items
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];
                $unitPrice = $product->price;
                $totalPrice = $unitPrice * $quantity;

                // Decrement stock if it's a product (not service)
                if (!$product->decrementStock($quantity)) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Stock insuficiente para el producto: {$product->name}",
                    ], 400);
                }

                // Create sale item
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);

                $total += $totalPrice;
            }

            // Update sale total
            $sale->update(['total' => $total]);

            // Add loyalty points if patient
            if ($sale->patient_id) {
                $patient = Patient::find($sale->patient_id);
                // 1 point per $10 spent
                $points = (int) floor($total / 10);
                if ($points > 0) {
                    $patient->addLoyaltyPoints($points);
                }
            }

            DB::commit();

            return response()->json([
                'sale' => $sale->load(['items.product', 'patient']),
                'message' => 'Venta registrada exitosamente',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al procesar la venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $sale = Sale::with(['items.product', 'patient', 'user'])->findOrFail($id);
        return response()->json($sale);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $sale = Sale::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|required|in:pending,completed,cancelled',
        ]);

        $sale->update($validated);

        return response()->json($sale);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $sale = Sale::findOrFail($id);
        
        // Don't delete, just cancel
        $sale->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Venta cancelada',
        ]);
    }

    /**
     * Get sales statistics
     */
    public function statistics(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth());
        $to = $request->get('to', now()->endOfMonth());

        $stats = [
            'total_sales' => Sale::whereBetween('created_at', [$from, $to])
                ->where('status', 'completed')
                ->sum('total'),
            'sales_count' => Sale::whereBetween('created_at', [$from, $to])
                ->where('status', 'completed')
                ->count(),
            'average_sale' => Sale::whereBetween('created_at', [$from, $to])
                ->where('status', 'completed')
                ->avg('total'),
            'by_payment_method' => Sale::whereBetween('created_at', [$from, $to])
                ->where('status', 'completed')
                ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(total) as total'))
                ->groupBy('payment_method')
                ->get(),
        ];

        return response()->json($stats);
    }
}
