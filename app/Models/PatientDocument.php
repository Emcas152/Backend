<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PatientDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'name',
        'path',
        'type',
        'requires_signature',
        'is_signed',
        'signature_path',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'requires_signature' => 'boolean',
            'is_signed' => 'boolean',
            'signed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function getUrlAttribute(): ?string
    {
        if (!$this->path) {
            return null;
        }
        
        if (config('filesystems.default') === 's3') {
            return Storage::disk('s3')->temporaryUrl($this->path, now()->addMinutes(60));
        }
        
        return Storage::url($this->path);
    }

    public function markAsSigned(string $signaturePath): void
    {
        $this->update([
            'is_signed' => true,
            'signature_path' => $signaturePath,
            'signed_at' => now(),
        ]);
    }
}
