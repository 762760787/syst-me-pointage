@extends('layouts.admin')

@section('title', 'Tableau de Bord')

@section('content')
    <!-- Cartes de statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="bg-blue-500 p-3 rounded-full text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total des Employés</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['totalEmployees'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="bg-green-500 p-3 rounded-full text-white">
                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Présents Aujourd'hui</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['currentlyPresent'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="bg-red-500 p-3 rounded-full text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Absents Aujourd'hui</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['absentToday'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique et Activité Récente -->
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Graphique de présence -->
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-lg">
            <h3 class="font-semibold text-lg text-gray-800 mb-4">Présence des 7 derniers jours</h3>
            <canvas id="attendanceChart"></canvas>
        </div>

        <!-- Activité Récente -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h3 class="font-semibold text-lg text-gray-800 mb-4">Activité Récente</h3>
            <ul class="space-y-4">
                @forelse($stats['recentActivities'] as $activity)
                    <li class="flex items-center">
                        <img class="h-10 w-10 rounded-full object-cover mr-3" src="{{ $activity->employee->photo_url }}" alt="">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $activity->employee->firstname }} {{ $activity->employee->lastname }}</p>
                            <p class="text-xs text-gray-500">
                                @if($activity->type == 'Arrivée')
                                    <span class="text-green-600">Arrivée</span>
                                @else
                                    <span class="text-red-600">Départ</span>
                                @endif
                                - {{ $activity->timestamp->diffForHumans() }}
                            </p>
                        </div>
                    </li>
                @empty
                     <li class="text-sm text-gray-500">Aucune activité récente.</li>
                @endforelse
            </ul>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($stats['chart']['labels']),
            datasets: [{
                label: 'Employés présents',
                data: @json($stats['chart']['values']),
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>
@endsection

