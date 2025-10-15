@extends('layouts.admin')

@section('title', 'Liste des Employés')

@section('content')
<div class="bg-white p-6 md:p-8 rounded-lg shadow-lg">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Gestion des Employés</h1>
        <a href="{{ route('admin.employees.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 font-semibold">
            Ajouter un employé
        </a>
    </div>

    <!-- Affiche les messages de succès (ajout, modification, suppression) -->
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p class="font-bold">Succès</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="py-3 px-4 uppercase font-semibold text-sm text-left">Photo</th>
                    <th class="py-3 px-4 uppercase font-semibold text-sm text-left">Nom Complet</th>
                    <th class="py-3 px-4 uppercase font-semibold text-sm text-left">Poste</th>
                    <th class="py-3 px-4 uppercase font-semibold text-sm text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @forelse ($employees as $employee)
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4">
                        {{-- CORRECTION: Utilisation de l'objet -> au lieu du tableau [] --}}
                        <img src="{{ $employee->photo_url }}" alt="Photo de {{ $employee->prenom }}" class="h-12 w-12 rounded-full object-cover">
                    </td>
                    {{-- CORRECTION: Affichage du prenom et nom --}}
                    <td class="py-3 px-4 font-medium">{{ $employee->prenom }} {{ $employee->nom }}</td>
                    <td class="py-3 px-4">{{ $employee->poste }}</td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center space-x-2">
                             {{-- CORRECTION: Passage des bons attributs à la fonction JS --}}
                            <button onclick="showQrCode('{{ $employee->qrcode_id }}', '{{ $employee->prenom }} {{ $employee->nom }}')" class="text-gray-500 hover:text-gray-800" title="Voir QR Code">
                               <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M5 5h2v2H5V5zm4 4H7V7h2v2zm2-2V5h2v2h-2zM5 11h2v2H5v-2zm2 2v-2H5v2h2zm-2 2H5v-2h2v2zm4 0h2v-2H9v2zm2-2v2h-2v-2h2zm2 2h2v-2h-2v2zm-4-4h2v-2H9v2zm2-2v2h-2V9h2zm4-4h2v2h-2V5zm-2 2v2h-2V7h2zM9 5H7v2h2V5zm4 0h2v2h-2V5z"/></svg>
                            </button>
                             {{-- NOUVEAU: Bouton Modifier --}}
                            <a href="{{ route('admin.employees.edit', $employee->id) }}" class="text-blue-500 hover:text-blue-800" title="Modifier">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                            </a>
                             {{-- NOUVEAU: Bouton Supprimer dans un formulaire sécurisé --}}
                            <form action="{{ route('admin.employees.destroy', $employee->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet employé ? Cette action est irréversible.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-800" title="Supprimer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-6 text-gray-500">
                        Aucun employé trouvé.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal pour le QR Code -->
<div id="qrcode-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-8 rounded-lg shadow-2xl max-w-sm w-full text-center">
        <h2 class="text-xl font-bold mb-2">Badge de <span id="modal-employee-name"></span></h2>
        <div id="qrcode" class="flex justify-center my-4"></div>
        <p class="text-xs text-gray-500 mb-6">Scannez ce code pour pointer.</p>
        <div class="flex justify-center space-x-4">
            <button onclick="printBadge()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Imprimer le Badge</button>
            <button onclick="closeModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400">Fermer</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    const modal = document.getElementById('qrcode-modal');
    const qrcodeContainer = document.getElementById('qrcode');
    const employeeNameSpan = document.getElementById('modal-employee-name');
    let currentEmployeeName = '';
    let qrcodeInstance = null;

    function showQrCode(qrcodeId, employeeName) {
        currentEmployeeName = employeeName;
        employeeNameSpan.textContent = employeeName;

        qrcodeContainer.innerHTML = '';
        qrcodeInstance = new QRCode(qrcodeContainer, {
            text: qrcodeId,
            width: 200,
            height: 200,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    function printBadge() {
        const qrCodeDataUrl = qrcodeContainer.querySelector('img').src;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head><title>Impression Badge</title>
                <style>
                    body { font-family: sans-serif; text-align: center; margin: 40px; }
                    .badge { border: 2px solid #333; padding: 20px; border-radius: 10px; display: inline-block; }
                    h1 { margin: 0 0 10px 0; }
                    img { margin-bottom: 10px; }
                </style>
                </head>
                <body>
                    <div class="badge">
                        <h1>${currentEmployeeName}</h1>
                        <img src="${qrCodeDataUrl}" alt="QR Code">
                        <p>Scannez pour pointer</p>
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
</script>
@endsection

