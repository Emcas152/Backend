<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Patient;
use App\Models\Product;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        $staffMember = null;
        
        // Si es doctor, obtener su staff_member
        if ($user && $user->isDoctor()) {
            $staffMember = $user->staffMember;
            if (!$staffMember) {
                return response()->json([
                    'data' => [
                        'monthly_income' => 0,
                        'monthly_income_change' => 0,
                        'new_patients_month' => 0,
                        'new_patients_change' => 0,
                        'average_ticket' => 0,
                        'average_ticket_change' => 0,
                        'return_rate' => 0,
                        'today_appointments' => 0,
                        'low_stock_products' => 0,
                        'upcoming_appointments' => [],
                        'monthly_performance' => []
                    ]
                ]);
            }
        }

        // Fecha actual y del mes anterior
        $currentMonth = Carbon::now()->startOfMonth();
        $previousMonth = Carbon::now()->subMonth()->startOfMonth();
        $today = Carbon::today();

        // Obtener IDs de pacientes del doctor (si aplica)
        $patientIds = null;
        if ($staffMember) {
            $patientIds = Appointment::where('staff_member_id', $staffMember->id)
                ->distinct()
                ->pluck('patient_id')
                ->toArray();
        }

        // 1. Ingresos del mes actual
        $monthlyIncomeQuery = Sale::where('created_at', '>=', $currentMonth);
        if ($patientIds !== null) {
            $monthlyIncomeQuery->whereIn('patient_id', $patientIds);
        }
        $monthlyIncome = $monthlyIncomeQuery->sum('total');
        
        // Ingresos del mes anterior para calcular cambio
        $previousMonthIncomeQuery = Sale::whereBetween('created_at', [
            $previousMonth,
            $currentMonth
        ]);
        if ($patientIds !== null) {
            $previousMonthIncomeQuery->whereIn('patient_id', $patientIds);
        }
        $previousMonthIncome = $previousMonthIncomeQuery->sum('total');
        
        $monthlyIncomeChange = $previousMonthIncome > 0 
            ? (($monthlyIncome - $previousMonthIncome) / $previousMonthIncome) * 100 
            : 0;

        // 2. Nuevos pacientes del mes
        $newPatientsQuery = Patient::where('created_at', '>=', $currentMonth);
        if ($patientIds !== null) {
            $newPatientsQuery->whereIn('id', $patientIds);
        }
        $newPatientsMonth = $newPatientsQuery->count();
        
        $previousMonthPatientsQuery = Patient::whereBetween('created_at', [
            $previousMonth,
            $currentMonth
        ]);
        if ($patientIds !== null) {
            $previousMonthPatientsQuery->whereIn('id', $patientIds);
        }
        $previousMonthPatients = $previousMonthPatientsQuery->count();
        
        $newPatientsChange = $previousMonthPatients > 0
            ? (($newPatientsMonth - $previousMonthPatients) / $previousMonthPatients) * 100
            : 0;

        // 3. Valor promedio por cita (ticket promedio)
        $currentMonthSalesQuery = Sale::where('created_at', '>=', $currentMonth);
        if ($patientIds !== null) {
            $currentMonthSalesQuery->whereIn('patient_id', $patientIds);
        }
        $currentMonthSales = $currentMonthSalesQuery->count();
        $averageTicket = $currentMonthSales > 0 ? $monthlyIncome / $currentMonthSales : 0;
        
        $previousMonthSalesQuery = Sale::whereBetween('created_at', [
            $previousMonth,
            $currentMonth
        ]);
        if ($patientIds !== null) {
            $previousMonthSalesQuery->whereIn('patient_id', $patientIds);
        }
        $previousMonthSales = $previousMonthSalesQuery->count();
        $previousAverage = $previousMonthSales > 0 ? $previousMonthIncome / $previousMonthSales : 0;
        
        $averageTicketChange = $previousAverage > 0
            ? (($averageTicket - $previousAverage) / $previousAverage) * 100
            : 0;

        // 4. Tasa de recompra (pacientes que han comprado más de una vez)
        $totalPatientsQuery = Patient::query();
        if ($patientIds !== null) {
            $totalPatientsQuery->whereIn('id', $patientIds);
        }
        $totalPatients = $totalPatientsQuery->count();
        
        $returningPatientsQuery = Patient::has('sales', '>=', 2);
        if ($patientIds !== null) {
            $returningPatientsQuery->whereIn('id', $patientIds);
        }
        $returningPatients = $returningPatientsQuery->count();
        $returnRate = $totalPatients > 0 ? ($returningPatients / $totalPatients) * 100 : 0;

        // 5. Citas de hoy
        $todayAppointmentsQuery = Appointment::whereDate('appointment_date', $today)
            ->whereIn('status', ['scheduled', 'confirmed']);
        if ($staffMember) {
            $todayAppointmentsQuery->where('staff_member_id', $staffMember->id);
        }
        $todayAppointments = $todayAppointmentsQuery->count();

        // 6. Productos con stock bajo (solo admin y staff pueden verlo)
        $lowStockProducts = 0;
        if (!$staffMember) {
            $lowStockProducts = Product::where('type', 'product')
                ->whereRaw('stock <= low_stock_alert')
                ->count();
        }

        // 7. Próximas citas del día
        $upcomingAppointmentsQuery = Appointment::with(['patient', 'staffMember'])
            ->whereDate('appointment_date', $today)
            ->whereIn('status', ['scheduled', 'confirmed']);
        if ($staffMember) {
            $upcomingAppointmentsQuery->where('staff_member_id', $staffMember->id);
        }
        $upcomingAppointments = $upcomingAppointmentsQuery
            ->orderBy('appointment_time', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'patient_name' => $appointment->patient ? $appointment->patient->name : 'Sin paciente',
                    'time' => Carbon::parse($appointment->appointment_time, 'UTC')->format('h:i A'),
                    'service' => $appointment->service
                ];
            });

        // 8. Rendimiento mensual (últimos 6 meses)
        $monthlyPerformance = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();
            
            $salesCountQuery = Sale::whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            if ($patientIds !== null) {
                $salesCountQuery->whereIn('patient_id', $patientIds);
            }
            $salesCount = $salesCountQuery->count();
            
            $monthlyPerformance[] = [
                'month' => $month->locale('es')->translatedFormat('F'),
                'amount' => $salesCount
            ];
        }

        return response()->json([
            'data' => [
                'monthly_income' => round($monthlyIncome, 2),
                'monthly_income_change' => round($monthlyIncomeChange, 1),
                'new_patients_month' => $newPatientsMonth,
                'new_patients_change' => round($newPatientsChange, 1),
                'average_ticket' => round($averageTicket, 2),
                'average_ticket_change' => round($averageTicketChange, 1),
                'return_rate' => round($returnRate, 0),
                'today_appointments' => $todayAppointments,
                'low_stock_products' => $lowStockProducts,
                'upcoming_appointments' => $upcomingAppointments,
                'monthly_performance' => $monthlyPerformance
            ]
        ]);
    }
}
