<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'price',
        'stock',
        'type',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function isService(): bool
    {
        return $this->type === 'service';
    }

    public function decrementStock(int $quantity): bool
    {
        if ($this->isService()) {
            return true; // Services don't have stock
        }

        if ($this->stock >= $quantity) {
            $this->decrement('stock', $quantity);
            return true;
        }
        return false;
    }
}
