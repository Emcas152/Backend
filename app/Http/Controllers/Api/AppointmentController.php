<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Appointment::with(['patient', 'staffMember'])
            ->filterByDoctor($user);

        // Filtrar por fecha
        if ($request->has('date')) {
            $query->whereDate('appointment_date', $request->date);
        }

        // Filtrar por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrar por paciente
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // Filtrar por staff (solo si no es doctor, ya que los doctores ya están filtrados)
        if ($request->has('staff_member_id') && !$user->isDoctor()) {
            $query->where('staff_member_id', $request->staff_member_id);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')
                              ->orderBy('appointment_time', 'desc')
                              ->paginate(20);

        return response()->json($appointments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'staff_member_id' => 'nullable|exists:staff_members,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i:s',
            'service' => 'required|string|max:255|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_.]+$/',
            'status' => 'sometimes|in:scheduled,confirmed,completed,cancelled',
            'notes' => 'nullable|string|max:2000',
        ]);

        // Validar que no haya conflictos de horario
        $existingAppointment = Appointment::where('staff_member_id', $validated['staff_member_id'])
            ->where('appointment_date', $validated['appointment_date'])
            ->where('appointment_time', $validated['appointment_time'])
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existingAppointment) {
            return response()->json([
                'message' => 'Ya existe una cita en este horario'
            ], 422);
        }

        $appointment = Appointment::create($validated);
        $appointment->load(['patient', 'staffMember']);

        return response()->json($appointment, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $appointment = Appointment::with(['patient', 'staffMember'])->findOrFail($id);
        return response()->json($appointment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'patient_id' => 'sometimes|required|exists:patients,id',
            'staff_member_id' => 'nullable|exists:staff_members,id',
            'appointment_date' => 'sometimes|required|date|after_or_equal:today',
            'appointment_time' => 'sometimes|required|date_format:H:i:s',
            'service' => 'sometimes|required|string|max:255|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_.]+$/',
            'status' => 'sometimes|in:scheduled,confirmed,completed,cancelled',
            'notes' => 'nullable|string|max:2000',
        ]);

        // Validar que no haya conflictos de horario (excepto esta cita)
        if (isset($validated['staff_member_id']) || isset($validated['appointment_date']) || isset($validated['appointment_time'])) {
            $existingAppointment = Appointment::where('id', '!=', $id)
                ->where('staff_member_id', $validated['staff_member_id'] ?? $appointment->staff_member_id)
                ->where('appointment_date', $validated['appointment_date'] ?? $appointment->appointment_date)
                ->where('appointment_time', $validated['appointment_time'] ?? $appointment->appointment_time)
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($existingAppointment) {
                return response()->json([
                    'message' => 'Ya existe una cita en este horario'
                ], 422);
            }
        }

        $appointment->update($validated);
        $appointment->load(['patient', 'staffMember']);

        return response()->json($appointment);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return response()->json([
            'message' => 'Cita eliminada exitosamente'
        ]);
    }

    /**
     * Update appointment status
     */
    public function updateStatus(Request $request, string $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:scheduled,confirmed,completed,cancelled',
        ]);

        $appointment->update($validated);
        $appointment->load(['patient', 'staffMember']);

        return response()->json($appointment);
    }
}
