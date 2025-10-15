@extends('layouts.admin')

@section('title', 'Feuilles de Présence')

@section('content')
<div class="bg-white p-6 rounded-lg shadow-lg">
    <div class="space-y-8">
        @forelse($attendancesByDate as $date => $records)
            <div>
                <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">{{ \Carbon\Carbon::parse($date)->locale('fr')->translatedFormat('l j F Y') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-200 text-gray-600">
                            <tr>
                                <th class="text-left py-2 px-3 uppercase font-semibold text-sm">Employé</th>
                                <th class="text-left py-2 px-3 uppercase font-semibold text-sm">Arrivée</th>
                                <th class="text-left py-2 px-3 uppercase font-semibold text-sm">Départ</th>
                                <th class="text-left py-2 px-3 uppercase font-semibold text-sm">Temps de Travail</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            {{-- On boucle sur chaque enregistrement de la journée --}}
                            @foreach($records as $record)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-3 flex items-center">
                                        {{-- CORRECTION : On accède à l'objet 'employee' et à ses propriétés --}}
                                        <img src="{{ $record['employee']->photo_url }}" alt="Photo de {{ $record['employee']->prenom }}" class="h-8 w-8 rounded-full object-cover mr-3">
                                        <span class="font-semibold">{{ $record['employee']->prenom }} {{ $record['employee']->nom }}</span>
                                    </td>
                                    <td class="py-2 px-3">{{ $record['Arrivée'] }}</td>
                                    <td class="py-2 px-3">{{ $record['Sortie'] ?? 'N/A' }}</td>
                                    <td class="py-2 px-3 font-mono">{{ $record['duration'] ?? 'En cours...' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="text-center py-10">
                <p class="text-gray-500">Aucun pointage enregistré pour le moment.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

