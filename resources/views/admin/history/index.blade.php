@extends('layouts.admin')

@section('title', 'Historique des Pointages Journaliers')

@section('content')
<div class="space-y-8">
    @forelse($history as $date => $dayData)
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold text-gray-800 border-b pb-3 mb-4">{{ $dayData['day_label'] }}</h2>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-3 px-4 text-left font-semibold text-gray-600">Employé</th>
                            <th class="py-3 px-4 text-center font-semibold text-gray-600">Arrivée</th>
                            <th class="py-3 px-4 text-center font-semibold text-gray-600">Pause</th>
                            <th class="py-3 px-4 text-center font-semibold text-gray-600">Départ</th>
                            <th class="py-3 px-4 text-center font-semibold text-gray-600">Temps travaillé</th>
                            <th class="py-3 px-4 text-left font-semibold text-gray-600">Observation</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($dayData['attendances'] as $record)
                            <tr>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-3">
                                        <img class="h-10 w-10 rounded-full object-cover" src="{{ $record['photo_url'] }}" alt="{{ $record['name'] }}">
                                        <span class="font-medium text-gray-800">{{ $record['name'] }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center text-gray-700">{{ $record['arrival'] }}</td>
                                <td class="py-3 px-4 text-center text-gray-500">{{ $record['break'] }}</td>
                                <td class="py-3 px-4 text-center text-gray-700">{{ $record['departure'] }}</td>
                                <td class="py-3 px-4 text-center font-semibold text-gray-800">{{ $record['worked_duration'] }}</td>
                                <td class="py-3 px-4">
                                    <span class="font-semibold text-xs py-1 px-2.5 rounded-full
                                        @if($record['observation'] === 'OK') bg-green-100 text-green-800
                                        @elseif(in_array($record['observation'], ['Départ manquant', 'Arrivée manquante'])) bg-red-100 text-red-800
                                        @elseif($record['observation'] === 'Heures incomplètes') bg-yellow-100 text-yellow-800
                                        @endif">
                                        {{ $record['observation'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white p-6 rounded-lg shadow-lg text-center">
            <p class="text-gray-500">Aucun historique de pointage à afficher.</p>
        </div>
    @endforelse
</div>
@endsection

