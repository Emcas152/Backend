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
            'name' => 'required|string|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'email' => 'required|email:rfc,dns|unique:patients,email|max:255',
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'birthday' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
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
            'name' => 'sometimes|required|string|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'email' => 'sometimes|required|email:rfc,dns|unique:patients,email,' . $id . '|max:255',
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'birthday' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
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
            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB max
            'type' => 'required|in:before,after,other',
            'notes' => 'nullable|string|max:1000',
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
            'document' => 'required|file|mimes:pdf,doc,docx,txt|max:10240', // 10MB max
            'name' => 'required|string|max:255|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_.]+$/',
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
            'signature' => 'required|file|image|mimes:png,jpg,jpeg|max:2048', // Signature image
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
            'points' => 'required|integer|min:1|max:10000',
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
            'points' => 'required|integer|min:1|max:10000',
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
