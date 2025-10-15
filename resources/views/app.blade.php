{{-- <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Système de Pointage par QR Code</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        #reader { border: 2px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden; }
        .nav-link { transition: all 0.2s ease-in-out; }
        .nav-link.active { background-color: #4f46e5; color: white; }
        @media print {
            body * { visibility: hidden; }
            #print-area, #print-area * { visibility: visible; }
            #print-area { position: absolute; left: 0; top: 0; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; }
            #attendance-detail-modal-content, #attendance-detail-modal-content * { visibility: visible; }
            #attendance-detail-modal { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="flex h-screen bg-gray-100">
        <!-- BARRE DE NAVIGATION LATÉRALE -->
        <aside class="w-64 bg-white shadow-md flex flex-col">
            <div class="p-6 border-b">
                <h1 class="text-2xl font-bold text-gray-900">Pointage Pro</h1>
                <p class="text-sm text-gray-500">Menu Principal</p>
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <a href="#scanner" data-view="scanner-view" class="nav-link flex items-center space-x-3 px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200">
                    <i data-lucide="camera"></i> <span>Mode Scanner</span>
                </a>
                <a href="#employees" data-view="employees-view" class="nav-link flex items-center space-x-3 px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200">
                    <i data-lucide="users"></i> <span>Employés</span>
                </a>
                <a href="#attendance" data-view="attendance-view" class="nav-link flex items-center space-x-3 px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200">
                    <i data-lucide="calendar-check"></i> <span>Feuilles de Présence</span>
                </a>
            </nav>
        </aside>

        <!-- CONTENU PRINCIPAL -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-8">
                <div id="loading-overlay" class="hidden fixed inset-0 bg-white bg-opacity-75 z-50 flex items-center justify-center">
                    <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-indigo-600"></div>
                </div>

                <!-- VUE SCANNER -->
                <div id="scanner-view" class="page-view">
                    <div class="bg-white p-6 rounded-lg shadow-md max-w-2xl mx-auto">
                        <h2 class="text-xl font-semibold mb-4 text-center">Présentez votre badge QR Code</h2>
                        <div class="max-w-md mx-auto">
                            <div id="reader"></div>
                        </div>
                        <div id="scan-status" class="mt-4 text-center text-lg font-medium"></div>
                    </div>
                </div>

                <!-- VUE GESTION DES EMPLOYÉS -->
                <div id="employees-view" class="page-view hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Gestion des Employés</h2>
                        <button id="show-add-employee-form" class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow-sm hover:bg-indigo-700 flex items-center space-x-2">
                            <i data-lucide="user-plus"></i> <span>Ajouter un employé</span>
                        </button>
                    </div>
                    <div id="employee-list-container" class="bg-white p-6 rounded-lg shadow-md"></div>
                </div>

                <!-- VUE FORMULAIRE AJOUTER EMPLOYÉ -->
                <div id="add-employee-view" class="page-view hidden">
                    <h2 class="text-2xl font-bold mb-6">Ajouter un Nouvel Employé</h2>
                    <div class="bg-white p-6 rounded-lg shadow-md max-w-lg mx-auto">
                        <form id="add-employee-form" class="space-y-4">
                            <div>
                                <label for="prenom" class="block text-sm font-medium text-gray-700">Prénom</label>
                                <input type="text" id="prenom" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label for="nom" class="block text-sm font-medium text-gray-700">Nom</label>
                                <input type="text" id="nom" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label for="poste" class="block text-sm font-medium text-gray-700">Poste</label>
                                <input type="text" id="poste" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div class="flex justify-end space-x-3 pt-4">
                                <button type="button" id="cancel-add-employee" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300">Annuler</button>
                                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- VUE FEUILLES DE PRÉSENCE -->
                <div id="attendance-view" class="page-view hidden">
                    <div class="flex justify-between items-center mb-6">
                         <h2 class="text-2xl font-bold">Feuilles de Présence</h2>
                    </div>
                    <div id="attendance-sheets-container" class="bg-white p-6 rounded-lg shadow-md"></div>
                </div>
            </main>
        </div>
    </div>

    <!-- MODALS -->
    <div id="confirmation-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center hidden z-50 transition-opacity duration-300">
        <div id="confirmation-card" class="bg-white rounded-2xl shadow-2xl p-8 text-center max-w-sm w-full transform transition-all duration-300 scale-95 opacity-0">
            <img id="employee-photo" src="" alt="Photo de l'employé" class="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-gray-200 object-cover">
            <p class="text-2xl font-bold" id="employee-name"></p>
            <div id="status-badge" class="mt-4 text-white font-bold py-2 px-4 rounded-full inline-block text-lg"><span id="status-type"></span></div>
            <p class="text-gray-600 mt-2 text-xl" id="status-time"></p>
            <p class="text-sm text-gray-400 mt-4">Ce message disparaîtra automatiquement.</p>
        </div>
    </div>
    <div id="qrcode-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl text-center relative">
            <button onclick="closeModal('qrcode-modal')" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800"><i data-lucide="x"></i></button>
            <div id="print-area">
                <div class="p-4 border rounded-lg">
                    <h3 id="qrcode-employee-name" class="text-lg font-semibold mb-2"></h3>
                    <canvas id="qrcode-canvas"></canvas>
                    <p class="text-xs text-gray-500 mt-2">QR Code de pointage</p>
                </div>
            </div>
            <button onclick="printContent('print-area')" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center mx-auto space-x-2"><i data-lucide="printer"></i><span>Imprimer le Badge</span></button>
        </div>
    </div>
    <div id="attendance-detail-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
        <div id="attendance-detail-modal-content" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-xl font-bold">Détail de la journée du <span id="detail-date"></span></h3>
                 <button onclick="printContent('attendance-detail-modal-content')" class="text-gray-600 hover:text-gray-900 mx-4"><i data-lucide="printer"></i></button>
                <button onclick="closeModal('attendance-detail-modal')" class="text-gray-500 hover:text-gray-800"><i data-lucide="x"></i></button>
            </div>
            <div id="attendance-detail-list" class="overflow-y-auto"></div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const appState = {
            currentView: 'scanner-view',
            html5QrCode: null,
            lastScanTime: 0,
            scanCooldown: 3000,
        };

        const API_URL = '{{ url("/") }}/api';
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const loadingOverlay = document.getElementById('loading-overlay');
        const showLoading = () => loadingOverlay.classList.remove('hidden');
        const hideLoading = () => loadingOverlay.classList.add('hidden');

        // --- GESTION DE LA VUE ET DE LA NAVIGATION ---
        const navLinks = document.querySelectorAll('.nav-link');
        const pageViews = document.querySelectorAll('.page-view');

        async function switchView(viewId) {
            if (appState.currentView === viewId && viewId !== 'scanner-view') return;
            if (appState.currentView === 'scanner-view' && viewId !== 'scanner-view') stopScanner();

            pageViews.forEach(view => view.classList.add('hidden'));
            document.getElementById(viewId).classList.remove('hidden');

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.dataset.view === viewId) link.classList.add('active');
            });

            appState.currentView = viewId;
            showLoading();
            if (viewId === 'scanner-view') startScanner();
            else if (viewId === 'employees-view') await renderEmployeeList();
            else if (viewId === 'attendance-view') await renderAttendanceSheets();
            hideLoading();
        }

        navLinks.forEach(link => link.addEventListener('click', (e) => {
            e.preventDefault();
            switchView(e.currentTarget.dataset.view);
        }));

        // --- LOGIQUE DU SCANNER ---
        function startScanner() {
            if (!appState.html5QrCode) appState.html5QrCode = new Html5Qrcode("reader");
            if (appState.html5QrCode?.isScanning) return;
            appState.html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: { width: 250, height: 250 } }, onScanSuccess)
                .catch(err => console.error("Scanner start error.", err));
        }

        function stopScanner() {
            if (appState.html5QrCode?.isScanning) {
                appState.html5QrCode.stop().catch(err => console.error("Scanner stop error.", err));
            }
        }

        async function onScanSuccess(decodedText) {
            const now = Date.now();
            if (now - appState.lastScanTime < appState.scanCooldown) return;
            appState.lastScanTime = now;
            await processAttendance(decodedText);
        }

        async function processAttendance(qrcodeId) {
            try {
                const response = await fetch(`${API_URL}/pointage`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify({ qrcode_id: qrcodeId })
                });
                if (!response.ok) throw new Error('Employé non trouvé ou erreur serveur.');

                const result = await response.json();
                showConfirmation(result.employee, result.attendance);

            } catch (error) {
                console.error('Attendance Error:', error);
                document.getElementById('scan-status').textContent = error.message;
            }
        }

        function showConfirmation(employee, record) {
            document.getElementById('employee-photo').src = `https://placehold.co/200x200/RANDOM/333333?text=${employee.prenom.charAt(0)}${employee.nom.charAt(0)}`;
            document.getElementById('employee-name').textContent = `${employee.prenom} ${employee.nom}`;
            document.getElementById('status-type').textContent = record.type;
            document.getElementById('status-time').textContent = new Date(record.timestamp).toLocaleTimeString('fr-FR');

            const badge = document.getElementById('status-badge');
            badge.className = 'mt-4 text-white font-bold py-2 px-4 rounded-full inline-block text-lg';
            badge.classList.add(record.type === 'Arrivée' ? 'bg-green-500' : 'bg-red-500');

            const modal = document.getElementById('confirmation-modal');
            const card = document.getElementById('confirmation-card');
            modal.classList.remove('hidden');
            setTimeout(() => card.classList.remove('scale-95', 'opacity-0'), 10);
            setTimeout(() => {
                card.classList.add('scale-95', 'opacity-0');
                setTimeout(() => modal.classList.add('hidden'), 300);
            }, 3500);
        }

        // --- LOGIQUE DE GESTION DES EMPLOYÉS ---
        document.getElementById('show-add-employee-form').addEventListener('click', () => switchView('add-employee-view'));
        document.getElementById('cancel-add-employee').addEventListener('click', () => switchView('employees-view'));

        document.getElementById('add-employee-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const prenom = document.getElementById('prenom').value;
            const nom = document.getElementById('nom').value;
            const poste = document.getElementById('poste').value;

            try {
                showLoading();
                const response = await fetch(`${API_URL}/employees`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify({ prenom, nom, poste })
                });
                if (!response.ok) throw new Error('Erreur lors de la création de l\'employé.');
                const newEmployee = await response.json();
                e.target.reset();
                await switchView('employees-view');
                showQrCodeModal(newEmployee.qrcode_id, `${newEmployee.prenom} ${newEmployee.nom}`);
            } catch (error) {
                console.error('Add Employee Error:', error);
                alert(error.message);
            } finally {
                hideLoading();
            }
        });

        async function renderEmployeeList() {
            const container = document.getElementById('employee-list-container');
            try {
                const response = await fetch(`${API_URL}/employees`);
                const employees = await response.json();

                if (employees.length === 0) {
                    container.innerHTML = `<p class="text-center text-gray-500">Aucun employé enregistré.</p>`;
                    return;
                }
                container.innerHTML = `<div class="space-y-3">${employees.map(emp => `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border">
                        <div class="flex items-center space-x-3">
                            <img src="https://placehold.co/200x200/RANDOM/333333?text=${emp.prenom.charAt(0)}${emp.nom.charAt(0)}" alt="${emp.prenom}" class="w-10 h-10 rounded-full object-cover">
                            <div>
                                <p class="font-semibold">${emp.prenom} ${emp.nom}</p>
                                <p class="text-sm text-gray-500">${emp.poste}</p>
                            </div>
                        </div>
                        <button data-qrcode-id="${emp.qrcode_id}" data-name="${emp.prenom} ${emp.nom}" class="generate-qr-btn bg-white border border-gray-300 text-gray-700 px-3 py-1.5 rounded-md text-sm hover:bg-gray-100 flex items-center space-x-1.5">
                            <i data-lucide="qrcode" class="w-4 h-4"></i>
                            <span>Voir QR Code</span>
                        </button>
                    </div>`).join('')}</div>`;
                lucide.createIcons();
                container.querySelectorAll('.generate-qr-btn').forEach(btn => btn.addEventListener('click', e => {
                    const { qrcodeId, name } = e.currentTarget.dataset;
                    showQrCodeModal(qrcodeId, name);
                }));
            } catch (error) {
                container.innerHTML = `<p class="text-center text-red-500">Impossible de charger les employés.</p>`;
            }
        }

        // --- LOGIQUE FEUILLES DE PRÉSENCE ---
        async function renderAttendanceSheets() {
            const container = document.getElementById('attendance-sheets-container');
            try {
                const response = await fetch(`${API_URL}/attendances`);
                const sheets = await response.json();
                if (Object.keys(sheets).length === 0) {
                    container.innerHTML = `<p class="text-center text-gray-500">Aucun pointage enregistré.</p>`; return;
                }
                container.innerHTML = `<div class="space-y-2">${Object.keys(sheets).map(date => `
                    <div class="p-3 bg-gray-50 rounded-lg border flex justify-between items-center cursor-pointer hover:bg-gray-100 attendance-sheet-item" data-date="${date}">
                        <div>
                            <p class="font-semibold">${new Date(date).toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
                            <p class="text-sm text-gray-500">${sheets[date]} pointages</p>
                        </div>
                        <i data-lucide="chevron-right"></i>
                    </div>`).join('')}</div>`;
                lucide.createIcons();
                container.querySelectorAll('.attendance-sheet-item').forEach(item => item.addEventListener('click', e => {
                    showAttendanceDetail(e.currentTarget.dataset.date);
                }));
            } catch (error) {
                container.innerHTML = `<p class="text-center text-red-500">Impossible de charger les feuilles de présence.</p>`;
            }
        }

        async function showAttendanceDetail(date) {
            document.getElementById('detail-date').textContent = new Date(date).toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' });
            const detailList = document.getElementById('attendance-detail-list');
            try {
                const response = await fetch(`${API_URL}/attendances/${date}`);
                const employeesOnDay = await response.json();
                detailList.innerHTML = employeesOnDay.map(data => {
                    return `
                        <div class="p-4 border-b">
                            <div class="flex justify-between items-start">
                               <div class="flex items-center space-x-3">
                                    <img src="https://placehold.co/200x200/RANDOM/333333?text=${data.employee.prenom.charAt(0)}${data.employee.nom.charAt(0)}" class="w-10 h-10 rounded-full">
                                    <div><p class="font-bold">${data.employee.prenom} ${data.employee.nom}</p><p class="text-sm text-gray-500">${data.employee.poste}</p></div>
                               </div>
                               <div class="text-right"><p class="font-bold text-lg">${data.total_work_time}</p><p class="text-sm text-gray-500">Temps total</p></div>
                            </div>
                            <ul class="list-disc pl-5 mt-2 text-sm text-gray-600 space-y-1">
                                ${data.pairs.map(p => `<li>${p}</li>`).join('') || '<li>Données de pointage incomplètes</li>'}
                            </ul>
                        </div>`;
                }).join('');
            } catch (error) {
                detailList.innerHTML = `<p class="text-center text-red-500">Impossible de charger les détails.</p>`;
            }
            document.getElementById('attendance-detail-modal').classList.remove('hidden');
        }

        // --- GESTION DES MODALES ET AUTRES ---
        window.closeModal = (modalId) => document.getElementById(modalId).classList.add('hidden');
        window.printContent = (elementId) => { window.print(); };
        function showQrCodeModal(qrcodeId, name) {
            document.getElementById('qrcode-employee-name').textContent = name;
            new QRious({ element: document.getElementById('qrcode-canvas'), value: qrcodeId, size: 200, padding: 15 });
            document.getElementById('qrcode-modal').classList.remove('hidden');
        }

        // --- INITIALISATION ---
        lucide.createIcons();
        switchView('scanner-view');
    });
    </script>
</body>
</html> --}}
