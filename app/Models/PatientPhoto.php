<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PatientPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'path',
        'type',
        'notes',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function getUrlAttribute(): ?string
    {
        if (!$this->path) {
            return null;
        }
        
        // If using S3, generate a temporary URL
        if (config('filesystems.default') === 's3') {
            return Storage::disk('s3')->temporaryUrl($this->path, now()->addMinutes(60));
        }
        
        // For local storage
        return Storage::url($this->path);
    }
}
