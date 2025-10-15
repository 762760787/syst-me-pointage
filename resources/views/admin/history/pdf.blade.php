<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des Pointages</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; }
        .page-break { page-break-after: always; }
        .week-title { font-size: 18px; font-weight: bold; text-align: center; margin-bottom: 20px; }
        .employee-name { font-size: 14px; font-weight: bold; margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #ccc; padding-bottom: 5px;}
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .observation-ok { color: green; }
        .observation-incomplete { color: orange; }
        .observation-missing { color: red; }
        .repo { text-align: center; color: #999; }
    </style>
</head>
<body>
    <h1 class="week-title">{{ $weekData['week_label'] }}</h1>

    @foreach($weekData['employees'] as $employeeData)
        <h2 class="employee-name">{{ $employeeData['name'] }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Jour</th>
                    <th style="text-align: center;">Arrivée</th>
                    <th style="text-align: center;">Départ</th>
                    <th>Observation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employeeData['days'] as $dayName => $dayData)
                    <tr>
                        <td>{{ ucfirst($dayName) }}</td>
                        @if($dayData)
                            <td style="text-align: center;">{{ $dayData['arrival'] }}</td>
                            <td style="text-align: center;">{{ $dayData['departure'] }}</td>
                            <td>
                                @if($dayData['observation'] === 'OK') <span class="observation-ok">
                                @elseif(in_array($dayData['observation'], ['Départ manquant', 'Arrivée manquante'])) <span class="observation-missing">
                                @elseif($dayData['observation'] === 'Heures incomplètes') <span class="observation-incomplete">
                                @endif
                                {{ $dayData['observation'] }}
                                </span>
                            </td>
                        @else
                            <td colspan="3" class="repo">Repos</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
