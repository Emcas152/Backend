<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientPhoto;
use App\Models\PatientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Patient::query()->with(['photos', 'documents']);

        // Si es doctor, filtrar solo pacientes que tienen citas con él
        if ($user && $user->isDoctor()) {
            $staffMember = $user->staffMember;
            
            if ($staffMember) {
                $query->whereHas('sales', function ($q) use ($staffMember) {
                    // Pacientes que tienen ventas/tratamientos
                    $q->whereNotNull('id');
                })->orWhereHas('appointments', function ($q) use ($staffMember) {
                    // O pacientes que tienen citas con este doctor
                    $q->where('staff_member_id', $staffMember->id);
                });
            } else {
                // Si es doctor pero no tiene staff_member, no mostrar nada
                $query->whereRaw('1 = 0');
            }
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $patients = $query->paginate(20);
        return response()->json($patients);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:patients,email',
            'phone' => 'nullable|string|max:20',
            'birthday' => 'nullable|date',
            'address' => 'nullable|string',
        ]);

        $patient = Patient::create($validated);
        $patient->generateQRCode();

        return response()->json($patient, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, Request $request)
    {
        $user = $request->user();
        $query = Patient::with(['photos', 'documents', 'sales']);

        // Si es doctor, verificar que tenga acceso a este paciente
        if ($user && $user->isDoctor()) {
            $staffMember = $user->staffMember;
            
            if ($staffMember) {
                $query->where(function ($q) use ($staffMember) {
                    $q->whereHas('appointments', function ($subq) use ($staffMember) {
                        $subq->where('staff_member_id', $staffMember->id);
                    });
                });
            } else {
                // Si es doctor pero no tiene staff_member, denegar acceso
                return response()->json(['message' => 'No autorizado'], 403);
            }
        }

        $patient = $query->findOrFail($id);
        return response()->json($patient);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $patient = Patient::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:patients,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'birthday' => 'nullable|date',
            'address' => 'nullable|string',
        ]);

        $patient->update($validated);

        return response()->json($patient);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $patient = Patient::findOrFail($id);
        $patient->delete();

        return response()->json(null, 204);
    }

    /**
     * Upload photo for patient (before/after)
     */
    public function uploadPhoto(Request $request, string $id)
    {
        $patient = Patient::findOrFail($id);

        $validated = $request->validate([
            'photo' => 'required|image|max:5120', // 5MB max
            'type' => 'required|in:before,after,other',
            'notes' => 'nullable|string',
        ]);

        $path = $request->file('photo')->store("patients/{$patient->id}/photos", 'public');

        $photo = PatientPhoto::create([
            'patient_id' => $patient->id,
            'path' => $path,
            'type' => $validated['type'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'photo' => $photo,
            'url' => $photo->url,
        ], 201);
    }

    /**
     * Upload document for patient
     */
    public function uploadDocument(Request $request, string $id)
    {
        $patient = Patient::findOrFail($id);

        $validated = $request->validate([
            'document' => 'required|file|max:10240', // 10MB max
            'name' => 'required|string|max:255',
            'type' => 'required|in:consent,contract,prescription,lab_result,other',
            'requires_signature' => 'boolean',
        ]);

        $path = $request->file('document')->store("patients/{$patient->id}/documents", 'public');

        $document = PatientDocument::create([
            'patient_id' => $patient->id,
            'name' => $validated['name'],
            'path' => $path,
            'type' => $validated['type'],
            'requires_signature' => $validated['requires_signature'] ?? false,
        ]);

        return response()->json([
            'document' => $document,
            'url' => $document->url,
        ], 201);
    }

    /**
     * Sign document
     */
    public function signDocument(Request $request, string $patientId, string $documentId)
    {
        $document = PatientDocument::where('patient_id', $patientId)
            ->findOrFail($documentId);

        if (!$document->requires_signature) {
            return response()->json(['message' => 'Este documento no requiere firma'], 400);
        }

        $validated = $request->validate([
            'signature' => 'required|file|max:2048', // Signature image
        ]);

        $signaturePath = $request->file('signature')->store("patients/{$patientId}/signatures", 'public');
        $document->markAsSigned($signaturePath);

        return response()->json($document);
    }

    /**
     * Add loyalty points
     */
    public function addLoyaltyPoints(Request $request, string $id)
    {
        $patient = Patient::findOrFail($id);

        $validated = $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $patient->addLoyaltyPoints($validated['points']);

        return response()->json([
            'message' => 'Puntos añadidos exitosamente',
            'loyalty_points' => $patient->fresh()->loyalty_points,
        ]);
    }

    /**
     * Redeem loyalty points
     */
    public function redeemLoyaltyPoints(Request $request, string $id)
    {
        $patient = Patient::findOrFail($id);

        $validated = $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $success = $patient->redeemLoyaltyPoints($validated['points']);

        if (!$success) {
            return response()->json([
                'message' => 'Puntos insuficientes',
            ], 400);
        }

        return response()->json([
            'message' => 'Puntos canjeados exitosamente',
            'loyalty_points' => $patient->fresh()->loyalty_points,
        ]);
    }
}
