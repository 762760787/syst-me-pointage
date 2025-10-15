<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class HistoryExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $weekData;

    public function __construct(array $weekData)
    {
        $this->weekData = $weekData;
    }

    public function collection()
    {
        $rows = [];
        foreach ($this->weekData['employees'] as $employeeData) {
            foreach ($employeeData['days'] as $dayName => $dayData) {
                $rows[] = [
                    'Employé' => $employeeData['name'],
                    'Jour' => ucfirst($dayName),
                    'Arrivée' => $dayData['arrival'] ?? 'Repos',
                    'Départ' => $dayData['departure'] ?? 'Repos',
                    'Observation' => $dayData['observation'] ?? 'Repos',
                ];
            }
            // Ajoute une ligne vide pour séparer les employés
            $rows[] = ['','','','',''];
        }
        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'Employé',
            'Jour',
            'Arrivée',
            'Départ',
            'Observation',
        ];
    }

    public function title(): string
    {
        return $this->weekData['week_label'];
    }
}
