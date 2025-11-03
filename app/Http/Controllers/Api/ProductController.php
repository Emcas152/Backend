<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter active/inactive
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(20);
        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_.]+$/',
            'sku' => 'nullable|string|max:100|regex:/^[A-Z0-9\-]+$/|unique:products,sku',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0|max:999999.99',
            'stock' => 'nullable|integer|min:0|max:999999',
            'low_stock_alert' => 'nullable|integer|min:0|max:9999',
            'type' => 'required|in:product,service',
            'active' => 'boolean',
        ]);

        $product = Product::create($validated);

        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_.]+$/',
            'sku' => 'sometimes|nullable|string|max:100|regex:/^[A-Z0-9\-]+$/|unique:products,sku,' . $id,
            'description' => 'nullable|string|max:2000',
            'price' => 'sometimes|required|numeric|min:0|max:999999.99',
            'stock' => 'nullable|integer|min:0|max:999999',
            'low_stock_alert' => 'nullable|integer|min:0|max:9999',
            'type' => 'sometimes|required|in:product,service',
            'active' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(null, 204);
    }

    /**
     * Adjust stock
     */
    public function adjustStock(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        if ($product->isService()) {
            return response()->json(['message' => 'Los servicios no tienen inventario'], 400);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:-999999|max:999999',
            'type' => 'required|in:add,subtract,set',
        ]);

        switch ($validated['type']) {
            case 'add':
                $newStock = $product->stock + abs($validated['quantity']);
                if ($newStock > 999999) {
                    return response()->json(['message' => 'Stock excede límite máximo'], 400);
                }
                $product->increment('stock', abs($validated['quantity']));
                break;
            case 'subtract':
                $newStock = $product->stock - abs($validated['quantity']);
                if ($newStock < 0) {
                    return response()->json(['message' => 'Stock insuficiente'], 400);
                }
                $product->decrement('stock', abs($validated['quantity']));
                break;
            case 'set':
                $product->update(['stock' => abs($validated['quantity'])]);
                break;
        }

        return response()->json([
            'message' => 'Stock actualizado',
            'product' => $product->fresh(),
        ]);
    }
}
