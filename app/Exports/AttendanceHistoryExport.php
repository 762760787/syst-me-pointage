<?php

namespace App\Exports;

use App\Models\Attendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class AttendanceHistoryExport implements FromCollection, WithHeadings
{
    protected $startDate;
    protected $endDate;

    public function __construct(Carbon $startDate, Carbon $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
    * @return array
    */
    public function headings(): array
    {
        return [
            'Date',
            'Employé',
            'Heure d\'arrivée',
            'Heure de départ',
            'Temps travaillé',
            'Observation',
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $attendances = Attendance::with('employee')
            ->whereBetween('timestamp', [$this->startDate, $this->endDate])
            ->orderBy('timestamp', 'asc')
            ->get();

        $attendancesByDay = $attendances->groupBy(function ($attendance) {
            return Carbon::parse($attendance->timestamp)->format('Y-m-d');
        });

        $exportData = new Collection();

        foreach ($attendancesByDay as $date => $dayAttendances) {
            $attendancesByEmployee = $dayAttendances->groupBy('employee_id');

            foreach ($attendancesByEmployee as $employeeId => $employeeAttendances) {
                $employee = $employeeAttendances->first()->employee;
                $arrival = $employeeAttendances->where('type', 'Arrivée')->first();
                $departure = $employeeAttendances->where('type', 'Sortie')->last();

                $arrivalTime = $arrival ? Carbon::parse($arrival->timestamp) : null;
                $departureTime = $departure ? Carbon::parse($departure->timestamp) : null;

                $workedDuration = 'N/A';
                $observation = 'OK';

                if (!$arrivalTime) {
                    $observation = 'Arrivée manquante';
                } elseif (!$departureTime) {
                    $observation = 'Départ manquant';
                }

                if ($arrivalTime && $departureTime) {
                    // **CORRECTION: Calcul du temps total de présence sans soustraire la pause**
                    $workedSeconds = $arrivalTime->diffInSeconds($departureTime);

                    if ($workedSeconds < 0) {
                        $workedSeconds = 0;
                    }

                    $hours = floor($workedSeconds / 3600);
                    $minutes = floor(($workedSeconds % 3600) / 60);
                    $workedDuration = sprintf('%dh %02dm', $hours, $minutes);

                    if ($workedSeconds < (7 * 3600)) {
                        $observation = 'Heures incomplètes';
                    }
                }

                $exportData->push([
                    'date' => Carbon::parse($date)->format('d/m/Y'),
                    'employee' => $employee->prenom . ' ' . $employee->nom,
                    'arrival' => $arrivalTime ? $arrivalTime->format('H:i') : '---',
                    'departure' => $departureTime ? $departureTime->format('H:i') : '---',
                    'worked_duration' => $workedDuration,
                    'observation' => $observation
                ]);
            }
        }

        return $exportData;
    }
}

