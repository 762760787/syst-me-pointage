<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    /**
     * Affiche la liste de tous les employés.
     */
    public function index()
    {
        return Employee::orderBy('nom')->orderBy('prenom')->get();
    }

    /**
     * Crée un nouvel employé.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'poste' => 'required|string|max:255',
            // CORRECTION : Ajout de la validation pour l'URL de la photo
            'photo_url' => 'nullable|url|max:2048',
        ]);

        if ($validator->fails()) {
            // Utilisation du code 422 pour les erreurs de validation, c'est une meilleure pratique
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = Employee::create([
            'prenom' => $request->prenom,
            'nom' => $request->nom,
            'poste' => $request->poste,
            // CORRECTION : Enregistrement de l'URL de la photo
            'photo_url' => $request->photo_url,
            'qrcode_id' => (string) Str::uuid(),
        ]);

        return response()->json($employee, 201);
    }
}
// CORRECTION : L'accolade en trop a été supprimée d'ici

