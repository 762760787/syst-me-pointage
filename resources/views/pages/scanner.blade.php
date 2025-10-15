@extends('layouts.app')

@section('title', 'Scanner de Pointage')

@section('content')
<div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl mx-auto text-center">
    <h1 class="text-2xl font-bold mb-4">Scanner de Pointage</h1>
    <p class="text-gray-600 mb-6">Veuillez présenter votre badge QR Code devant la caméra.</p>

    <div class="w-full max-w-sm mx-auto bg-gray-200 rounded-lg overflow-hidden border-4 border-gray-300">
        <div id="reader" class="w-full"></div>
    </div>

    <div id="scan-status" class="mt-4 text-lg font-semibold h-8"></div>
</div>


<!-- Modal de Confirmation -->
<div id="confirmation-modal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center hidden z-50">
    <div class="bg-white p-8 rounded-lg shadow-2xl text-center transform transition-all scale-95 opacity-0" id="modal-content">
        <img id="employee-photo" src="" alt="Photo de l'employé" class="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-gray-200 object-cover">
        <h2 id="employee-name" class="text-3xl font-bold text-gray-800"></h2>
        <p id="attendance-type" class="text-2xl mt-2"></p>
        <p id="attendance-time" class="text-lg text-gray-500 mt-1"></p>
    </div>
</div>

@endsection

@push('scripts')
{{-- La bibliothèque html5-qrcode pour le scan --}}
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Axios est configuré pour envoyer automatiquement le jeton de sécurité (CSRF)
    // qui a été placé dans le gabarit layouts/app.blade.php
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const statusElement = document.getElementById('scan-status');
    const modal = document.getElementById('confirmation-modal');
    const modalContent = document.getElementById('modal-content');

    const appState = {
        scanner: null,
        lastScanTime: 0,
        scanCooldown: 5000, // Temps d'attente de 5 secondes entre chaque scan réussi
    };

    function startScanner() {
        appState.scanner = new Html5Qrcode("reader");

        statusElement.textContent = "Démarrage de la caméra...";
        appState.scanner.start(
            { facingMode: "environment" }, // Utilise la caméra arrière par défaut
            {
                fps: 10, // Tente de scanner 10 fois par seconde
                qrbox: (w, h) => ({ width: Math.floor(Math.min(w,h) * 0.7), height: Math.floor(Math.min(w,h) * 0.7) })
            },
            onScanSuccess, // Fonction appelée en cas de succès
            () => { /* Ne rien faire si aucun QR code n'est trouvé dans une frame */ }
        ).then(() => {
             statusElement.textContent = "Prêt à scanner";
        }).catch(err => {
            console.error("Erreur de démarrage du scanner.", err);
            statusElement.textContent = "Erreur: Caméra non trouvée.";
            statusElement.classList.add('text-red-500');
        });
    }

    async function onScanSuccess(decodedText) {
        const now = Date.now();
        // Empêche un employé de scanner son badge plusieurs fois de suite par accident
        if (now - appState.lastScanTime < appState.scanCooldown) return;

        appState.lastScanTime = now;
        statusElement.textContent = "Code détecté, traitement...";

        try {
            // Envoie le QR code scanné à la route web sécurisée
            const response = await axios.post("{{ route('web.attendance.store') }}", {
                qrcode_id: decodedText
            });

            showConfirmation(response.data);

        } catch (error) {
            let errorMessage = 'Une erreur est survenue.';
            // Affiche un message d'erreur clair si le serveur en renvoie un
            if (error.response && error.response.data && error.response.data.message) {
                errorMessage = error.response.data.message;
            }
            console.error('Erreur de pointage:', error);
            showError(errorMessage);
        }
    }

    function showConfirmation(data) {
        // Met à jour et affiche le modal de confirmation avec les informations de l'employé
        document.getElementById('employee-photo').src = data.employee.photo_url;
        document.getElementById('employee-name').textContent = `${data.employee.prenom} ${data.employee.nom}`;
        const attendanceType = document.getElementById('attendance-type');

        if(data.attendance.type === 'Arrivée') {
            attendanceType.textContent = 'Arrivée ✅';
            attendanceType.className = 'text-2xl mt-2 text-green-600 font-semibold';
        } else {
            attendanceType.textContent = 'Départ 🚪';
            attendanceType.className = 'text-2xl mt-2 text-red-600 font-semibold';
        }

        const time = new Date(data.attendance.timestamp).toLocaleTimeString('fr-FR');
        document.getElementById('attendance-time').textContent = `Enregistré à ${time}`;

        modal.classList.remove('hidden');
        setTimeout(() => modalContent.classList.remove('scale-95', 'opacity-0'), 10);

        // Cache le modal après 4 secondes
        setTimeout(() => {
            modalContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
            statusElement.textContent = "Prêt à scanner";
        }, 4000);
    }

    function showError(message) {
        // Affiche un message d'erreur à l'utilisateur
        statusElement.textContent = message;
        statusElement.classList.add('text-red-500');

        // Réinitialise le message après 4 secondes
        setTimeout(() => {
            statusElement.textContent = "Prêt à scanner";
            statusElement.classList.remove('text-red-500');
        }, 4000);
    }

    // Démarre le scanner au chargement de la page
    startScanner();
});
</script>
@endpush

