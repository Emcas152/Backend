<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'patient_id',
        'total',
        'discount',
        'payment_method',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'discount' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function calculateTotal(): float
    {
        $total = $this->items->sum('total_price');
        return $total - $this->discount;
    }
}
