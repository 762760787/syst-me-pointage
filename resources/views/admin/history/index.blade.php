@extends('layouts.admin')

@section('title', 'Historique des Pointages')

@section('content')


{{-- Section pour l'export --}}
<div class="bg-white p-6 rounded-lg shadow-lg mb-8">
    <h2 class="text-xl font-bold text-gray-800 border-b pb-3 mb-4">Exporter l'historique</h2>
    <form action="{{ route('admin.history.export') }}" method="GET" id="exportForm">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
            <div>
                <label for="period" class="block text-sm font-medium text-gray-700">Période</label>
                <select id="period" name="period" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="current_month">Mois en cours</option>
                    <option value="last_month">Mois dernier</option>
                    <option value="current_week">Semaine en cours</option>
                    <option value="last_week">Semaine dernière</option>
                </select>
            </div>

            {{-- Champ caché pour le format, sera rempli par JS --}}
            <input type="hidden" name="format" id="exportFormat">

            <div class="md:col-span-2 flex items-center space-x-4">
                 <button type="submit" onclick="document.getElementById('exportFormat').value = 'excel';" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Exporter en Excel
                </button>
                <button type="submit" onclick="document.getElementById('exportFormat').value = 'pdf';" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Exporter en PDF
                </button>
            </div>
        </div>
    </form>
</div>


{{-- Section de Filtre --}}
<div class="bg-white p-6 rounded-lg shadow-lg mb-8">
    <h2 class="text-xl font-bold text-gray-800 border-b pb-3 mb-4">Filtrer l'historique</h2>
    <form action="{{ route('admin.history.index') }}" method="GET" class="w-full">
        <div class="flex flex-col md:flex-row md:items-end md:space-x-4">
            <div class="flex-grow">
                <label for="employee_name" class="block text-sm font-medium text-gray-700">Filtrer par employé</label>
                <select id="employee_name" name="employee_name" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="">Tous les employés</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->prenom }}" {{ ($selected_employee ?? '') == $employee->prenom ? 'selected' : '' }}>
                            {{ $employee->prenom }} {{ $employee->nom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mt-4 md:mt-0 flex items-center space-x-2">
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Filtrer
                </button>
                <a href="{{ route('admin.history.index') }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Réinitialiser
                </a>
            </div>
        </div>
    </form>
</div>

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
                            {{-- <th class="py-3 px-4 text-center font-semibold text-gray-600">Pause</th> --}}
                            <th class="py-3 px-4 text-center font-semibold text-gray-600">Départ</th>
                            <th class="py-3 px-4 text-center font-semibold text-gray-600">Temps travaillé</th>
                            <th class="py-3 px-4 text-left font-semibold text-gray-600">Observation</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($dayData['attendances'] as $record)
                            <tr>
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <img class="h-10 w-10 rounded-full object-cover mr-4" src="{{ $record['employee']->photo_url }}" alt="Photo de {{ $record['employee']->prenom }}">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $record['employee']->prenom }} {{ $record['employee']->nom }}</p>
                                            <p class="text-xs text-gray-500">{{ $record['employee']->poste }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center text-gray-700">{{ $record['arrival'] }}</td>
                                {{-- <td class="py-3 px-4 text-center text-gray-500">01h</td> --}}
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
            <p class="text-gray-500">Aucun historique de pointage à afficher pour les critères sélectionnés.</p>
        </div>
    @endforelse
</div>
@endsection

