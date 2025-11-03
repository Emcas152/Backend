<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\FiltersByDoctor;

class Appointment extends Model
{
    use HasFactory, FiltersByDoctor;

    protected $fillable = [
        'patient_id',
        'staff_member_id',
        'appointment_date',
        'appointment_time',
        'service',
        'status',
        'notes'
    ];

    protected $casts = [
        'appointment_date' => 'date',
    ];

    /**
     * Mutators para sanitizar datos
     */
    protected function service(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => strip_tags(trim($value)),
        );
    }

    protected function notes(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? strip_tags(trim($value)) : null,
        );
    }

    /**
     * Relación con el paciente
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Relación con el staff member
     */
    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class);
    }
}
