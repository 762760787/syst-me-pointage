<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AppController extends Controller
{
    public function scanner()
    {
        return view('pages.scanner');
    }


    /**
     * Affiche l'historique des pointages par semaine.
     */
    public function history()
    {
        // Temps de travail requis en secondes (excluant la pause d'1h)
        $requiredWorkSeconds = 7 * 3600;

        // Récupérer tous les pointages, triés par date
        $attendances = Attendance::with('employee')
            ->orderBy('timestamp', 'asc')
            ->get();

        // Grouper les pointages par jour
        $dailyData = $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->timestamp)->format('Y-m-d');
        });

        $history = [];

        // Trier les jours du plus récent au plus ancien
        $sortedDailyData = $dailyData->sortKeysDesc();

        foreach ($sortedDailyData as $date => $dayAttendances) {
            $dayCarbon = Carbon::parse($date);
            $dayLabel = $dayCarbon->translatedFormat('l j F Y');

            // Grouper les pointages de la journée par employé
            $attendancesByEmployee = $dayAttendances->groupBy('employee_id');

            $dailyRecords = [];

            foreach ($attendancesByEmployee as $employeeId => $employeeAttendances) {
                if ($employeeAttendances->isEmpty() || !$employeeAttendances->first()->employee) {
                    continue;
                }

                $employee = $employeeAttendances->first()->employee;

                // Trouver le premier pointage d'arrivée et le dernier de sortie
                $arrivalRecord = $employeeAttendances->where('type', 'Arrivée')->first();
                $departureRecord = $employeeAttendances->where('type', 'Sortie')->last();

                $arrival = $arrivalRecord ? Carbon::parse($arrivalRecord->timestamp) : null;
                $departure = $departureRecord ? Carbon::parse($departureRecord->timestamp) : null;

                $observation = 'OK';
                $workedDurationFormatted = '---';

                if ($arrival && $departure) {

                    // --- LOGIQUE DE CALCUL CORRIGÉE ET FIABILISÉE ---
                    // 1. Calculer le temps total passé sur site
                    $totalSecondsOnSite = $departure->diffInSeconds($arrival);

                    // 2. Définir la période de pause fixe pour la journée en cours
                    $breakStart = $arrival->copy()->setTime(13, 30, 0);
                    $breakEnd = $arrival->copy()->setTime(14, 30, 0);

                    // 3. Calculer la durée de la superposition entre la période de travail et la pause
                    $overlapStart = $arrival->max($breakStart); // Le début de la superposition est le plus tardif des deux départs
                    $overlapEnd = $departure->min($breakEnd);   // La fin de la superposition est le plus précoce des deux fins

                    $overlapSeconds = 0;
                    // Il y a une superposition uniquement si son début est avant sa fin
                    if ($overlapStart->lt($overlapEnd)) {
                        $overlapSeconds = $overlapEnd->diffInSeconds($overlapStart);
                    }

                    // 4. Le temps travaillé est le temps total moins la durée de la pause qui a eu lieu pendant le travail
                    $workedSeconds = $totalSecondsOnSite - $overlapSeconds;
                    $workedSeconds = max(0, $workedSeconds); // S'assurer de ne pas avoir de temps négatif

                    $hours = floor($workedSeconds / 3600);
                    $minutes = floor(($workedSeconds % 3600) / 60);
                    $workedDurationFormatted = sprintf('%dh %02dm', $hours, $minutes);
                    // --- FIN DE LA NOUVELLE LOGIQUE ---

                    if ($workedSeconds < $requiredWorkSeconds) {
                        $observation = 'Heures incomplètes';
                    }

                } elseif ($arrival && !$departure) {
                    $observation = 'Départ manquant';
                } elseif (!$arrival && $departure) {
                    $observation = 'Arrivée manquante';
                } else {
                    continue; // Ne pas afficher si pas de pointage valide
                }

                $dailyRecords[] = [
                    'name' => $employee->prenom . ' ' . $employee->nom,
                    'photo_url' => $employee->photo_url,
                    'arrival' => $arrival ? $arrival->format('H:i') : '---',
                    'departure' => $departure ? $departure->format('H:i') : '---',
                    'break' => '13:30 - 14:30',
                    'worked_duration' => $workedDurationFormatted,
                    'observation' => $observation,
                ];
            }

            // Trier les employés par ordre alphabétique pour cette journée
            usort($dailyRecords, fn($a, $b) => strcmp($a['name'], $b['name']));

            if (!empty($dailyRecords)) {
                $history[$date] = [
                    'day_label' => $dayLabel,
                    'attendances' => $dailyRecords,
                ];
            }
        }

        return view('admin.history.index', compact('history'));
    }


    /**
     * NOUVELLE LOGIQUE INTELLIGENTE :
     * Gère le pointage en vérifiant l'état de la journée et l'écart de 4 heures.
     */
    public function storeAttendance(Request $request)
    {
        $validated = $request->validate(['qrcode_id' => 'required|string']);

        try {
            $employee = Employee::where('qrcode_id', $validated['qrcode_id'])->first();

            if (!$employee) {
                return response()->json(['message' => 'Employé non trouvé. QR code invalide.'], 404);
            }

            // On récupère les pointages de l'employé pour AUJOURD'HUI
            $todayAttendances = $employee->attendances()
                ->whereDate('timestamp', Carbon::today())
                ->get();

            $arrivalRecord = $todayAttendances->firstWhere('type', 'Arrivée');
            $departureRecord = $todayAttendances->firstWhere('type', 'Sortie');

            $type = null;

            // Scénario 1 : Aucune arrivée n'a été enregistrée aujourd'hui
            if (!$arrivalRecord) {
                $type = 'Arrivée';
            }
            // Scénario 2 : Une arrivée a été enregistrée, mais pas encore de départ
            elseif ($arrivalRecord && !$departureRecord) {
                $arrivalTime = Carbon::parse($arrivalRecord->timestamp);
                $now = Carbon::now();

                // On vérifie si au moins 4 heures se sont écoulées
                if ($arrivalTime->diffInHours($now) < 4) {
                    return response()->json([
                        'message' => 'Vous devez travailler au moins 4 heures avant de pointer votre départ.'
                    ], 403); // 403 Forbidden : Action non autorisée
                }
                $type = 'Sortie';
            }
            // Scénario 3 : Une arrivée ET un départ ont déjà été enregistrés pour la journée
            else {
                return response()->json(['message' => 'Vous avez déjà enregistré une arrivée et une sortie aujourd\'hui.'], 409); // 409 Conflict
            }

            // Enregistrement du pointage
            $attendance = $employee->attendances()->create([
                'timestamp' => now(),
                'type' => $type,
            ]);

            return response()->json([
                'message' => 'Pointage enregistré.',
                'employee' => $employee,
                'attendance' => $attendance,
            ], 201);
        } catch (Exception $e) {
            Log::error("Erreur de pointage : " . $e->getMessage());
            return response()->json(['message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    // ... (toutes les autres fonctions du AppController)
    public function dashboard()
    {
        $today = Carbon::today();
        $totalEmployees = Employee::count();
        $todaysAttendances = Attendance::whereDate('timestamp', $today)->orderBy('timestamp', 'desc')->get();
        $presentEmployeeIds = $todaysAttendances->groupBy('employee_id')->filter(fn($a) => $a->first()->type === 'Arrivée')->keys();
        $currentlyPresent = count($presentEmployeeIds);
        $absentToday = $totalEmployees - $currentlyPresent;
        $latestAttendances = Attendance::with('employee')->orderBy('timestamp', 'desc')->take(5)->get();
        $attendancesLast7Days = Attendance::where('timestamp', '>=', Carbon::today()->subDays(6))->where('type', 'Arrivée')->orderBy('timestamp')->get()->groupBy(fn($d) => Carbon::parse($d->timestamp)->format('Y-m-d'))->map(fn($day) => $day->count());
        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $chartLabels[] = $date->locale('fr')->translatedFormat('D j');
            $chartData[] = $attendancesLast7Days->get($dateString, 0);
        }
        $stats = ['totalEmployees' => $totalEmployees, 'currentlyPresent' => $currentlyPresent, 'absentToday' => $absentToday, 'recentActivities' => $latestAttendances, 'chart' => ['labels' => $chartLabels, 'values' => $chartData,]];
        return view('admin.dashboard', compact('stats'));
    }
    public function employees()
    {
        $employees = Employee::orderBy('nom')->get();
        return view('admin.employees.index', compact('employees'));
    }
    public function createEmployee()
    {
        return view('admin.employees.create');
    }
    public function storeEmployee(Request $request)
    {
        $validated = $request->validate(['prenom' => 'required|string|max:255', 'nom' => 'required|string|max:255', 'poste' => 'required|string|max:255', 'photo_url' => 'nullable|url|max:2048',]);
        $photoUrl = $validated['photo_url'];
        if (empty($photoUrl)) {
            $initials = strtoupper(substr($validated['prenom'], 0, 1) . substr($validated['nom'], 0, 1));
            $photoUrl = "https://placehold.co/100x100/E2E8F0/4A5568?text=" . $initials;
        }
        Employee::create(['prenom' => $validated['prenom'], 'nom' => $validated['nom'], 'poste' => $validated['poste'], 'photo_url' => $photoUrl, 'qrcode_id' => Str::uuid(),]);
        return redirect()->route('admin.employees.index')->with('success', 'L\'employé a été ajouté avec succès !');
    }
    public function editEmployee($id)
    {
        $employee = Employee::findOrFail($id);
        return view('admin.employees.edit', compact('employee'));
    }
    public function updateEmployee(Request $request, $id)
    {
        $validated = $request->validate(['prenom' => 'required|string|max:255', 'nom' => 'required|string|max:255', 'poste' => 'required|string|max:255', 'photo_url' => 'nullable|url|max:2048',]);
        $employee = Employee::findOrFail($id);
        $employee->update($validated);
        return redirect()->route('admin.employees.index')->with('success', 'Les informations de l\'employé ont été mises à jour avec succès !');
    }
    public function destroyEmployee($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();
        return redirect()->route('admin.employees.index')->with('success', 'L\'employé a été supprimé avec succès.');
    }
    public function attendances()
    {
        $attendances = Attendance::with('employee')->orderBy('timestamp', 'desc')->get();
        $attendancesByDate = [];
        foreach ($attendances as $attendance) {
            $date = Carbon::parse($attendance->timestamp)->format('Y-m-d');
            $employeeId = $attendance->employee_id;
            if (!isset($attendancesByDate[$date][$employeeId])) {
                $attendancesByDate[$date][$employeeId] = ['employee' => $attendance->employee, 'Arrivée' => null, 'Sortie' => null, 'duration' => null,];
            }
            if ($attendance->type === 'Arrivée') {
                $attendancesByDate[$date][$employeeId]['Arrivée'] = Carbon::parse($attendance->timestamp)->format('H:i:s');
            } elseif ($attendance->type === 'Sortie') {
                $attendancesByDate[$date][$employeeId]['Sortie'] = Carbon::parse($attendance->timestamp)->format('H:i:s');
            }
        }
        foreach ($attendancesByDate as $date => &$records) {
            foreach ($records as &$record) {
                if ($record['Arrivée'] && $record['Sortie']) {
                    $checkInTime = Carbon::parse($record['Arrivée']);
                    $checkOutTime = Carbon::parse($record['Sortie']);
                    $record['duration'] = $checkInTime->diff($checkOutTime)->format('%H:%I:%S');
                }
            }
        }
        return view('admin.attendances.index', compact('attendancesByDate'));
    }
}
