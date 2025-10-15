<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;
use Exception;

class AttendanceController extends Controller
{
    /**
     * Enregistre un nouveau pointage (arrivée ou sortie).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'qrcode_id' => 'required|string|max:255',
        ]);

        try {
            // On cherche l'employé correspondant au QR code
            $employee = Employee::where('qrcode_id', $validated['qrcode_id'])->first();

            // CORRECTION : On vérifie explicitement si l'employé a été trouvé
            if (!$employee) {
                // Si non trouvé, on renvoie une erreur 404 claire
                return response()->json(['message' => 'Employé non trouvé. Le QR code est peut-être invalide.'], 404);
            }

            // On détermine si c'est une arrivée ou un départ
            $lastAttendance = $employee->attendances()->latest('timestamp')->first();
            $type = (!$lastAttendance || $lastAttendance->type === 'check-out') ? 'check-in' : 'check-out';

            // On utilise une transaction pour s'assurer que l'enregistrement se fait sans erreur
            $attendance = DB::transaction(function () use ($employee, $type) {
                return $employee->attendances()->create([
                    'timestamp' => now(),
                    'type' => $type,
                ]);
            });

            // Si tout s'est bien passé, on renvoie une réponse de succès
            return response()->json([
                'message' => 'Pointage enregistré avec succès.',
                'employee' => $employee,
                'attendance' => $attendance,
            ], 201);

        } catch (Exception $e) {
            // En cas d'erreur imprévue (ex: problème de base de données), on la logue
            Log::error("Erreur de pointage pour qrcode_id {$validated['qrcode_id']}: " . $e->getMessage());

            // Et on renvoie une réponse d'erreur générique pour ne pas exposer de détails techniques
            return response()->json(['message' => 'Une erreur interne est survenue lors du pointage.'], 500);
        }
    }

    /**
     * Renvoie la liste des jours où il y a eu des pointages.
     */
    public function index()
    {
        $attendances = Attendance::select(DB::raw('DATE(timestamp) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return $attendances->pluck('count', 'date');
    }

    /**
     * Affiche le détail des pointages pour une date donnée.
     */
    public function show(string $date)
    {
        $employeesData = Employee::whereHas('attendances', function ($query) use ($date) {
            $query->whereDate('timestamp', $date);
        })->with(['attendances' => function ($query) use ($date) {
            $query->whereDate('timestamp', $date)->orderBy('timestamp', 'asc');
        }])->get();

        $response = [];
        foreach ($employeesData as $employee) {
            $totalMillis = 0;
            $pairs = [];
            $records = $employee->attendances;

            for ($i = 0; $i < count($records); $i += 2) {
                $arrival = $records[$i];
                if (isset($records[$i+1])) {
                    $departure = $records[$i+1];
                    if ($arrival->type == 'Arrivée' && $departure->type == 'Sortie') {
                        $start = Carbon::parse($arrival->timestamp);
                        $end = Carbon::parse($departure->timestamp);
                        $totalMillis += $end->diffInMilliseconds($start);
                        $pairs[] = $start->format('H:i:s') . ' - ' . $end->format('H:i:s');
                    }
                }
            }

            $hours = floor($totalMillis / 3600000);
            $minutes = floor(($totalMillis % 3600000) / 60000);

            $response[] = [
                'employee' => [
                    'prenom' => $employee->prenom,
                    'nom' => $employee->nom,
                    'poste' => $employee->poste,
                ],
                'pairs' => $pairs,
                'total_work_time' => $hours . 'h ' . $minutes . 'm',
            ];
        }

        return response()->json($response);
    }
}
