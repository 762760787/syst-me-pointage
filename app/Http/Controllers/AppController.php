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
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceHistoryExport;

class AppController extends Controller
{
    public function scanner()
    {
        return view('pages.scanner');
    }


    /**
     * Affiche l'historique des pointages par journée (nouvelle version professionnelle).
     */
    public function history(Request $request)
    {
        // Récupère le nom de l'employé depuis la requête du filtre
        $employeeName = $request->input('employee_name');

        // Récupère tous les employés pour peupler le menu déroulant du filtre
        $employees = Employee::orderBy('prenom')->get();

        // Commence la requête pour les pointages en incluant les informations de l'employé
        $attendanceQuery = Attendance::with('employee')->orderBy('timestamp', 'desc');

        // Si un nom d'employé est sélectionné dans le filtre, on l'ajoute à la requête
        if ($employeeName) {
            $attendanceQuery->whereHas('employee', function ($query) use ($employeeName) {
                $query->where('prenom', $employeeName);
            });
        }

        // Exécute la requête
        $attendances = $attendanceQuery->get();

        // Groupe tous les pointages par jour
        $attendancesByDay = $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->timestamp)->format('Y-m-d');
        });

        $history = [];

        // Boucle sur chaque journée pour traiter les données
        foreach ($attendancesByDay as $date => $dayAttendances) {
            $dayLabel = Carbon::parse($date)->translatedFormat('l j F Y');
            $records = [];

            // Regroupe les pointages de la journée par employé
            $attendancesByEmployee = $dayAttendances->groupBy('employee_id');

            foreach ($attendancesByEmployee as $employeeId => $employeeAttendances) {
                $employee = $employeeAttendances->first()->employee;
                $arrival = $employeeAttendances->where('type', 'Arrivée')->sortBy('timestamp')->first();
                $departure = $employeeAttendances->where('type', 'Sortie')->sortByDesc('timestamp')->first();

                $arrivalTime = $arrival ? Carbon::parse($arrival->timestamp) : null;
                $departureTime = $departure ? Carbon::parse($departure->timestamp) : null;

                $workedDuration = '0h 00m';
                $observation = 'OK';

                if (!$arrivalTime) {
                    $observation = 'Arrivée manquante';
                } elseif (!$departureTime) {
                    $observation = 'Départ manquant';
                }

                // Calcule le temps de travail si arrivée et départ existent
                if ($arrivalTime && $departureTime) {
                    $breakStart = Carbon::parse($date . ' 13:30:00');
                    $breakEnd = Carbon::parse($date . ' 14:30:00');

                    $overlapStart = $arrivalTime->max($breakStart);
                    $overlapEnd = $departureTime->min($breakEnd);

                    $overlapSeconds = 0;
                    if ($overlapStart < $overlapEnd) {
                        $overlapSeconds = $overlapStart->diffInSeconds($overlapEnd);
                    }

                    $totalSecondsOnSite = $arrivalTime->diffInSeconds($departureTime);
                    $workedSeconds = $totalSecondsOnSite - $overlapSeconds;

                    if ($workedSeconds < 0) $workedSeconds = 0;

                    $hours = floor($workedSeconds / 3600);
                    $minutes = floor(($workedSeconds % 3600) / 60);
                    $workedDuration = sprintf('%dh %02dm', $hours, $minutes);

                    if ($workedSeconds < (7 * 3600)) {
                        $observation = 'Heures incomplètes';
                    }
                }

                $records[] = [
                    'employee' => $employee,
                    'arrival' => $arrivalTime ? $arrivalTime->format('H:i') : '---',
                    'departure' => $departureTime ? $departureTime->format('H:i') : '---',
                    'break' => '13:30 - 14:30',
                    'worked_duration' => $workedDuration,
                    'observation' => $observation
                ];
            }

            $history[$date] = [
                'day_label' => $dayLabel,
                'attendances' => $records
            ];
        }

        // Retourne la vue avec les données de l'historique, la liste des employés et l'employé sélectionné
        return view('admin.history.index', [
            'history' => $history,
            'employees' => $employees,
            'selected_employee' => $employeeName
        ]);
    }

    /**
     * Gère l'export de l'historique en PDF ou Excel.
     */
    public function exportHistory(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|in:current_month,last_month,current_week,last_week',
            'format' => 'required|in:excel,pdf',
        ]);

        $period = $validated['period'];
        $format = $validated['format'];
        $periodName = '';

        switch ($period) {
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                $periodName = 'Mois Dernier (' . $startDate->translatedFormat('F Y') . ')';
                break;
            case 'current_week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                $periodName = 'Semaine en Cours';
                break;
            case 'last_week':
                $startDate = Carbon::now()->subWeek()->startOfWeek();
                $endDate = Carbon::now()->subWeek()->endOfWeek();
                $periodName = 'Semaine Dernière';
                break;
            case 'current_month':
            default:
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                $periodName = 'Mois en Cours (' . $startDate->translatedFormat('F Y') . ')';
                break;
        }

        $dateRange = 'Du ' . $startDate->format('d/m/Y') . ' au ' . $endDate->format('d/m/Y');
        $fileName = "historique_pointages_{$period}_" . Carbon::now()->format('Y-m-d');

        if ($format == 'excel') {
            return Excel::download(new AttendanceHistoryExport($startDate, $endDate), "{$fileName}.xlsx");
        }

        if ($format == 'pdf') {
            // --- DÉBUT DE LA CORRECTION ---
            // On ne réutilise PAS l'export Excel. On reconstruit les données
            // avec la structure attendue par le template PDF (groupée par jour),
            // en s'inspirant de la logique de la méthode history().

            $attendances = Attendance::with('employee')
                ->whereBetween('timestamp', [$startDate, $endDate])
                ->orderBy('timestamp', 'asc') // Tri par ordre chronologique pour faciliter le traitement
                ->get();

            $attendancesByDay = $attendances->groupBy(fn($attendance) => Carbon::parse($attendance->timestamp)->format('Y-m-d'));

            $historyData = collect(); // Utiliser une collection est une bonne pratique
            foreach ($attendancesByDay as $date => $dayAttendances) {
                $dayLabel = Carbon::parse($date)->translatedFormat('l j F Y');
                $records = [];
                $attendancesByEmployee = $dayAttendances->groupBy('employee_id');

                foreach ($attendancesByEmployee as $employeeId => $employeeAttendances) {
                    $employee = $employeeAttendances->first()->employee;
                    $arrival = $employeeAttendances->where('type', 'Arrivée')->first();
                    $departure = $employeeAttendances->where('type', 'Sortie')->last();

                    $arrivalTime = $arrival ? Carbon::parse($arrival->timestamp) : null;
                    $departureTime = $departure ? Carbon::parse($departure->timestamp) : null;
                    $workedDuration = '0h 00m';
                    $observation = 'OK';

                    if (!$arrivalTime) $observation = 'Arrivée manquante';
                    elseif (!$departureTime) $observation = 'Départ manquant';

                    if ($arrivalTime && $departureTime) {
                        $breakStart = Carbon::parse($date . ' 13:30:00');
                        $breakEnd = Carbon::parse($date . ' 14:30:00');
                        $overlapStart = $arrivalTime->max($breakStart);
                        $overlapEnd = $departureTime->min($breakEnd);
                        $overlapSeconds = ($overlapStart < $overlapEnd) ? $overlapStart->diffInSeconds($overlapEnd) : 0;
                        $totalSecondsOnSite = $arrivalTime->diffInSeconds($departureTime);
                        $workedSeconds = max(0, $totalSecondsOnSite - $overlapSeconds);

                        $hours = floor($workedSeconds / 3600);
                        $minutes = floor(($workedSeconds % 3600) / 60);
                        $workedDuration = sprintf('%dh %02dm', $hours, $minutes);

                        if ($workedSeconds < (7 * 3600)) $observation = 'Heures incomplètes';
                    }

                    $records[] = [
                        'employee' => $employee,
                        'arrival' => $arrivalTime ? $arrivalTime->format('H:i') : '---',
                        'departure' => $departureTime ? $departureTime->format('H:i') : '---',
                        'worked_duration' => $workedDuration,
                        'observation' => $observation
                    ];
                }

                $historyData->push([
                    'day_label' => $dayLabel,
                    'attendances' => $records
                ]);
            }

            $pdf = Pdf::loadView('admin.history.export_template', [
                'data' => $historyData, // On passe les données correctement formatées
                'periodName' => $periodName,
                'dateRange' => $dateRange
            ]);
            // --- FIN DE LA CORRECTION ---

            return $pdf->download("{$fileName}.pdf");
        }

        return redirect()->route('admin.history.index')->with('error', 'Format d\'exportation non valide.');
    }
    /**
     * Fonction privée pour récupérer et formater les données d'historique pour une période donnée.
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getHistoryData(Carbon $startDate, Carbon $endDate): array
    {
        // Temps de travail requis en secondes (excluant la pause d'1h)
        $requiredWorkSeconds = 7 * 3600;

        // Récupérer les pointages dans la période donnée
        $attendances = Attendance::with('employee')
            ->whereBetween('timestamp', [$startDate, $endDate])
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

            $attendancesByEmployee = $dayAttendances->groupBy('employee_id');
            $dailyRecords = [];

            foreach ($attendancesByEmployee as $employeeId => $employeeAttendances) {
                if ($employeeAttendances->isEmpty() || !$employeeAttendances->first()->employee) continue;

                $employee = $employeeAttendances->first()->employee;
                $arrivalRecord = $employeeAttendances->where('type', 'Arrivée')->first();
                $departureRecord = $employeeAttendances->where('type', 'Sortie')->last();
                $arrival = $arrivalRecord ? Carbon::parse($arrivalRecord->timestamp) : null;
                $departure = $departureRecord ? Carbon::parse($departureRecord->timestamp) : null;

                $observation = 'OK';
                $workedDurationFormatted = '---';

                if ($arrival && $departure) {
                    $totalSecondsOnSite = $departure->diffInSeconds($arrival);
                    $breakStart = $arrival->copy()->setTime(13, 30, 0);
                    $breakEnd = $arrival->copy()->setTime(14, 30, 0);
                    $overlapStart = $arrival->max($breakStart);
                    $overlapEnd = $departure->min($breakEnd);
                    $overlapSeconds = 0;
                    if ($overlapStart->lt($overlapEnd)) {
                        $overlapSeconds = $overlapEnd->diffInSeconds($overlapStart);
                    }
                    $workedSeconds = max(0, $totalSecondsOnSite - $overlapSeconds);
                    $hours = floor($workedSeconds / 3600);
                    $minutes = floor(($workedSeconds % 3600) / 60);
                    $workedDurationFormatted = sprintf('%dh %02dm', $hours, $minutes);

                    if ($workedSeconds < $requiredWorkSeconds) $observation = 'Heures incomplètes';
                } elseif ($arrival && !$departure) {
                    $observation = 'Départ manquant';
                } elseif (!$arrival && $departure) {
                    $observation = 'Arrivée manquante';
                } else {
                    continue;
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

            usort($dailyRecords, fn($a, $b) => strcmp($a['name'], $b['name']));

            if (!empty($dailyRecords)) {
                $history[$date] = [
                    'day_label' => $dayLabel,
                    'attendances' => $dailyRecords,
                ];
            }
        }
        return $history;
    }

    public function storeAttendance(Request $request)
    {
        $validated = $request->validate(['qrcode_id' => 'required|string']);

        try {
            $employee = Employee::where('qrcode_id', $validated['qrcode_id'])->first();

            if (!$employee) {
                return response()->json(['message' => 'Employé non trouvé. QR code invalide.'], 404);
            }

            $todayAttendances = $employee->attendances()
                ->whereDate('timestamp', Carbon::today())
                ->get();

            $arrivalRecord = $todayAttendances->firstWhere('type', 'Arrivée');
            $departureRecord = $todayAttendances->firstWhere('type', 'Sortie');

            $type = null;

            if (!$arrivalRecord) {
                $type = 'Arrivée';
            } elseif ($arrivalRecord && !$departureRecord) {
                $arrivalTime = Carbon::parse($arrivalRecord->timestamp);
                $now = Carbon::now();

                // On vérifie si au moins 1h30 (90 minutes) se sont écoulées
                if ($arrivalTime->diffInMinutes($now) < 90) {
                    return response()->json([
                        'message' => 'Vous devez travailler au moins 1h30 avant de pointer votre départ.'
                    ], 403);
                }
                $type = 'Sortie';
            } else {
                return response()->json(['message' => 'Vous avez déjà enregistré une arrivée et une sortie aujourd\'hui.'], 409);
            }

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
