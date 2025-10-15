<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AppController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- ROUTES PUBLIQUES ---
// Ces routes sont accessibles à tout le monde.

// Affiche la page du scanner de QR Code
Route::get('/', [AppController::class, 'scanner'])->name('scanner');

// Traite les données envoyées par le scanner (quand un badge est scanné)
// C'est la route que nous avons déplacée ici pour que le scanner sur tablette fonctionne.
Route::post('/pointage', [AppController::class, 'storeAttendance'])->name('web.attendance.store');


// --- ROUTES D'AUTHENTIFICATION ---
// Gère automatiquement les pages de connexion, d'inscription, etc.
Auth::routes();


// --- ROUTES D'ADMINISTRATION (PROTÉGÉES) ---
// Toutes les routes dans ce groupe nécessitent que l'utilisateur soit connecté.
// Le préfixe '/admin' est ajouté à toutes les URLs.

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // Affiche le tableau de bord principal de l'administration
    Route::get('/dashboard', [AppController::class, 'dashboard'])->name('dashboard');

    // --- Gestion des Employés ---
    // Affiche la liste des employés
    Route::get('/employees', [AppController::class, 'employees'])->name('employees.index');
    // Affiche le formulaire pour ajouter un nouvel employé
    Route::get('/employees/create', [AppController::class, 'createEmployee'])->name('employees.create');
    // Enregistre un nouvel employé
    Route::post('/employees', [AppController::class, 'storeEmployee'])->name('employees.store');
    // Affiche le formulaire pour modifier un employé
    Route::get('/employees/{employee}/edit', [AppController::class, 'editEmployee'])->name('employees.edit');
    // Met à jour un employé
    Route::put('/employees/{employee}', [AppController::class, 'updateEmployee'])->name('employees.update');
    // Supprime un employé
    Route::delete('/employees/{employee}', [AppController::class, 'destroyEmployee'])->name('employees.destroy');

    // --- Feuilles de Présence ---
    // Affiche les feuilles de présence
    Route::get('/attendances', [AppController::class, 'attendances'])->name('attendances.index');

    Route::get('/historiquePointage', [AppController::class, 'history'])->name('history.index');

    Route::get('/admin/history/export', [AppController::class, 'exportHistory'])->name('history.export');

});


Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
