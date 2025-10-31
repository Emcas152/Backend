<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
