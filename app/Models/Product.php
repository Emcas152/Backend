<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'price',
        'stock',
        'low_stock_alert',
        'type',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'low_stock_alert' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Mutators para sanitizar datos
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => strip_tags(trim($value)),
        );
    }

    protected function sku(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? strtoupper(strip_tags(trim($value))) : null,
        );
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? strip_tags(trim($value)) : null,
        );
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
