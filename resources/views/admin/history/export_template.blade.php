<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Pointages</title>
    <style>
        /* Styles CSS simples compatibles avec dom-pdf */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .day-header {
            background-color: #e9ecef;
            padding: 10px;
            font-size: 16px;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 10px;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #888;
        }
        .error-message {
            color: #d9534f; /* red */
            font-weight: bold;
            text-align: center;
            padding: 15px;
            border: 1px solid #d9534f;
            background-color: #f2dede;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Historique des Pointages</h1>
        <p><strong>Période :</strong> {{ $periodName ?? 'N/A' }}</p>
        <p>{{ $dateRange ?? 'N/A' }}</p>
    </div>

    {{-- La vue PDF doit boucler sur la variable `$data` passée par le contrôleur --}}
    @forelse($data as $dayData)
        {{-- CORRECTION : On s'assure que la structure des données est correcte avant d'essayer de l'afficher. --}}
        @if(isset($dayData['day_label']) && isset($dayData['attendances']))
            <div class="day-header">{{ $dayData['day_label'] }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Employé</th>
                        <th>Arrivée</th>
                        <th>Départ</th>
                        <th>Temps travaillé</th>
                        <th>Observation</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- La sous-boucle pour afficher les enregistrements de chaque employé --}}
                    @foreach($dayData['attendances'] as $record)
                        <tr>
                            {{-- Accès sécurisé au nom de l'employé pour éviter les erreurs --}}
                            <td>{{ $record['employee']->prenom ?? '' }} {{ $record['employee']->nom ?? 'Employé inconnu' }}</td>
                            <td>{{ $record['arrival'] ?? '---' }}</td>
                            <td>{{ $record['departure'] ?? '---' }}</td>
                            <td>{{ $record['worked_duration'] ?? '---' }}</td>
                            <td>{{ $record['observation'] ?? '---' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
             {{-- Ce message s'affichera dans le PDF si le contrôleur envoie des données mal formatées. --}}
            <p class="error-message">Erreur : Structure de données invalide reçue. Impossible d'afficher le pointage pour cet élément.</p>
        @endif
    @empty
        <p class="no-data">Aucune donnée de pointage disponible pour la période sélectionnée.</p>
    @endforelse

</body>
</html>

