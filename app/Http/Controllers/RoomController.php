<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::all();
        return view('rooms.index', compact('rooms'));
    }

    /**
     * Show the form for creating a new room.
     */
    public function create()
    {
        // Vérifier si l'utilisateur est administrateur
        if (!$this->isAdmin()) {
            return redirect()->route('rooms.index')
                ->with('error', 'Vous n\'avez pas les droits pour créer une salle.');
        }

        return view('rooms.create');
    }

    /**
     * Store a newly created room in storage.
     */
    public function store(Request $request)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!$this->isAdmin()) {
            return redirect()->route('rooms.index')
                ->with('error', 'Vous n\'avez pas les droits pour créer une salle.');
        }

        $validated = $request->validate([
            'nom' => 'required|unique:rooms|max:255',
            'capacite' => 'required|integer|min:1',
            'surface' => 'nullable|numeric',
            'equipment' => 'nullable|string',
        ]);

        Room::create($validated);

        return redirect()->route('rooms.index')
            ->with('success', 'Salle créée avec succès.');
    }

    /**
     * Display the specified room.
     */
    public function show(Room $room)
    {
        $reservations = $room->reservations()
            ->where('debut', '>=', now())
            ->orderBy('debut')
            ->get();

        return view('rooms.show', compact('room', 'reservations'));
    }

    /**
     * Show the form for editing the specified room.
     */
    public function edit(Room $room)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!$this->isAdmin()) {
            return redirect()->route('rooms.index')
                ->with('error', 'Vous n\'avez pas les droits pour modifier une salle.');
        }

        return view('rooms.edit', compact('room'));
    }

    /**
     * Update the specified room in storage.
     */
    public function update(Request $request, Room $room)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!$this->isAdmin()) {
            return redirect()->route('rooms.index')
                ->with('error', 'Vous n\'avez pas les droits pour modifier une salle.');
        }

        $validated = $request->validate([
            'nom' => 'required|max:255|unique:rooms,name,' . $room->id,
            'capacite' => 'required|integer|min:1',
            'surface' => 'nullable|numeric',
            'equipment' => 'nullable|string',
        ]);

        $room->update($validated);

        return redirect()->route('rooms.index')
            ->with('success', 'Salle mise à jour avec succès.');
    }

    /**
     * Remove the specified room from storage.
     */
    public function destroy(Room $room)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!$this->isAdmin()) {
            return redirect()->route('rooms.index')
                ->with('error', 'Vous n\'avez pas les droits pour supprimer une salle.');
        }

        // Vérifier si des réservations futures existent pour cette salle
        $hasUpcomingReservations = $room->reservations()
            ->where('debut', '>=', now())
            ->exists();

        if ($hasUpcomingReservations) {
            return redirect()->route('rooms.index')
                ->with('error', 'Impossible de supprimer cette salle car elle a des réservations à venir.');
        }

        $room->delete();

        return redirect()->route('rooms.index')
            ->with('success', 'Salle supprimée avec succès.');
    }

    /**
     * Vérifie si l'utilisateur connecté est un administrateur
     */
    private function isAdmin()
    {
        return Auth::user()->isA('admin');
    }
}
