@extends('layouts.admin')

@section('title', 'Modifier l\'Employé')

@section('content')
<div class="bg-white p-8 rounded-lg shadow-lg max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Modifier : {{ $employee->prenom }} {{ $employee->nom }}</h1>

    <form action="{{ route('admin.employees.update', $employee->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label for="prenom" class="block text-sm font-medium text-gray-700">Prénom</label>
            <input type="text" id="prenom" name="prenom" value="{{ old('prenom', $employee->prenom) }}" required class="mt-1 block w-full px-3 py-2 border @error('prenom') border-red-500 @else border-gray-300 @enderror rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            @error('prenom')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="nom" class="block text-sm font-medium text-gray-700">Nom</label>
            <input type="text" id="nom" name="nom" value="{{ old('nom', $employee->nom) }}" required class="mt-1 block w-full px-3 py-2 border @error('nom') border-red-500 @else border-gray-300 @enderror rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
             @error('nom')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="poste" class="block text-sm font-medium text-gray-700">Poste</label>
            <input type="text" id="poste" name="poste" value="{{ old('poste', $employee->poste) }}" required class="mt-1 block w-full px-3 py-2 border @error('poste') border-red-500 @else border-gray-300 @enderror rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
             @error('poste')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="photo_url" class="block text-sm font-medium text-gray-700">URL de la Photo</label>
            <input type="url" id="photo_url" name="photo_url" value="{{ old('photo_url', $employee->photo_url) }}" placeholder="https://example.com/photo.jpg" class="mt-1 block w-full px-3 py-2 border @error('photo_url') border-red-500 @else border-gray-300 @enderror rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
             @error('photo_url')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end space-x-4">
             <a href="{{ route('admin.employees.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 font-semibold">Annuler</a>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 font-semibold">Mettre à jour</button>
        </div>
    </form>
</div>
@endsection

