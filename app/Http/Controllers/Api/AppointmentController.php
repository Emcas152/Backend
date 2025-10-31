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
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
            'service' => 'required|string|max:255',
            'status' => 'sometimes|in:scheduled,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

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
            'appointment_date' => 'sometimes|required|date',
            'appointment_time' => 'sometimes|required',
            'service' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|in:scheduled,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

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
