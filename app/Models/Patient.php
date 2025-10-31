<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'birthday',
        'address',
        'qr_code',
        'loyalty_points',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'loyalty_points' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PatientPhoto::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PatientDocument::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    // Generate QR code
    public function generateQRCode(): string
    {
        if (!$this->qr_code) {
            $this->qr_code = 'PAT-' . str_pad($this->id, 8, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5($this->email), 0, 6));
            $this->save();
        }
        return $this->qr_code;
    }

    // Add loyalty points
    public function addLoyaltyPoints(int $points): void
    {
        $this->increment('loyalty_points', $points);
    }

    // Redeem loyalty points
    public function redeemLoyaltyPoints(int $points): bool
    {
        if ($this->loyalty_points >= $points) {
            $this->decrement('loyalty_points', $points);
            return true;
        }
        return false;
    }
}
