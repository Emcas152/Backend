<?php

namespace App\Traits;

trait FiltersByDoctor
{
    /**
     * Scope query para filtrar por doctor si el usuario autenticado es un doctor
     */
    public function scopeFilterByDoctor($query, $user)
    {
        if ($user && $user->isDoctor()) {
            $staffMember = $user->staffMember;
            
            if ($staffMember) {
                return $query->where('staff_member_id', $staffMember->id);
            }
            
            // Si es doctor pero no tiene staff_member asociado, no mostrar nada
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
