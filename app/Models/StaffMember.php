<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'position',
        'specialization',
        'phone',
        'email',
        'avatar'
    ];

    /**
     * Relación con el usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con las citas
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
