<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index()
    {
        // Si l'utilisateur est admin, afficher toutes les réservations
        if (Auth::user()->isA('admin')) {
            $upcoming = Reservation::with(['user', 'room'])
                ->where('debut', '>=', now())
                ->where('is_cancelled', false)
                ->orderBy('debut')
                ->get();

            $past = Reservation::with(['user', 'room'])
                ->where('debut', '<', now())
                ->orWhere('is_cancelled', true)
                ->orderBy('debut', 'desc')
                ->get();
        } else {
            // Sinon, afficher seulement les réservations de l'utilisateur
            $upcoming = Auth::user()->reservations()
                ->with('room')
                ->where('debut', '>=', now())
                ->where('is_cancelled', false)
                ->orderBy('debut')
                ->get();

            $past = Auth::user()->reservations()
                ->with('room')
                ->where('debut', '<', now())
                ->orWhere('is_cancelled', true)
                ->orderBy('debut', 'desc')
                ->get();
        }

        return view('reservations.index', compact('upcoming', 'past'));
    }

    /**
     * Show the form for creating a new reservation.
     */
    public function create(Request $request)
    {
        $rooms = Room::all();
        $roomId = $request->input('room_id');
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        return view('reservations.create', compact('rooms', 'roomId', 'date'));
    }

    /**
     * Store a newly created reservation in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'debut' => 'required|date_format:Y-m-d H:i',
            'din' => 'required|date_format:Y-m-d H:i|after:debut',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Vérifier si la salle est disponible pour cette plage horaire
        $conflictingReservation = Reservation::where('room_id', $validated['room_id'])
            ->where('is_cancelled', false)
            ->where(function($query) use ($validated) {
                $query->whereBetween('debut', [$validated['debut'], $validated['fin']])
                    ->orWhereBetween('fin', [$validated['debut'], $validated['fin']])
                    ->orWhere(function($query) use ($validated) {
                        $query->where('debut', '<=', $validated['debut'])
                              ->where('fin', '>=', $validated['fin']);
                    });
            })->first();

        if ($conflictingReservation) {
            return back()->withInput()->withErrors([
                'debut' => 'La salle est déjà réservée pendant cette plage horaire.'
            ]);
        }

        $reservation = new Reservation();
        $reservation->room_id = $validated['room_id'];
        $reservation->user_id = Auth::id();
        $reservation->debut = $validated['debut'];
        $reservation->fin = $validated['fin'];
        $reservation->titre = $validated['titre'];
        $reservation->description = $validated['description'] ?? null;
        $reservation->save();

        // Envoyer une notification de confirmation
        Auth::user()->notify(new ReservationConfirmation($reservation));

        return redirect()->route('reservations.index')
            ->with('success', 'Réservation créée avec succès.');
    }

    /**
     * Display the specified reservation.
     */
    public function show(Reservation $reservation)
    {
        // Vérifier si l'utilisateur a le droit de voir cette réservation
        if (!Auth::user()->isA('admin') && Auth::id() !== $reservation->user_id) {
            return redirect()->route('reservations.index')
                ->with('error', 'Vous n\'avez pas le droit de consulter cette réservation.');
        }

        return view('reservations.show', compact('reservation'));
    }

    /**
     * Show the form for editing the specified reservation.
     */
    public function edit(Reservation $reservation)
    {
        // Vérifier si l'utilisateur a le droit de modifier cette réservation
        if (!Auth::user()->isA('admin') && Auth::id() !== $reservation->user_id) {
            return redirect()->route('reservations.index')
                ->with('error', 'Vous n\'avez pas le droit de modifier cette réservation.');
        }

        // Vérifier si la réservation est dans le futur
        if ($reservation->debut < now()) {
            return redirect()->route('reservations.index')
                ->with('error', 'Vous ne pouvez pas modifier une réservation passée.');
        }

        $rooms = Room::all();

        return view('reservations.edit', compact('reservation', 'rooms'));
    }

    /**
     * Update the specified reservation in storage.
     */
    public function update(Request $request, Reservation $reservation)
    {
        // Vérifier si l'utilisateur a le droit de modifier cette réservation
        if (!Auth::user()->isA('admin') && Auth::id() !== $reservation->user_id) {
            return redirect()->route('reservations.index')
                ->with('error', 'Vous n\'avez pas le droit de modifier cette réservation.');
        }

        // Vérifier si la réservation est dans le futur
        if ($reservation->debut < now()) {
            return redirect()->route('reservations.index')
                ->with('error', 'Vous ne pouvez pas modifier une réservation passée.');
        }

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'debut' => 'required|date_format:Y-m-d H:i',
            'fin' => 'required|date_format:Y-m-d H:i|after:debut',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Vérifier si la salle est disponible pour cette plage horaire
        $conflictingReservation = Reservation::where('room_id', $validated['room_id'])
            ->where('id', '!=', $reservation->id)
            ->where('is_cancelled', false)
            ->where(function($query) use ($validated) {
                $query->whereBetween('debut', [$validated['debut'], $validated['fin']])
                    ->orWhereBetween('fin', [$validated['debut'], $validated['fin']])
                    ->orWhere(function($query) use ($validated) {
                        $query->where('debut', '<=', $validated['debut'])
                              ->where('fin', '>=', $validated['fin']);
                    });
            })->first();

        if ($conflictingReservation) {
            return back()->withInput()->withErrors([
                'debut' => 'La salle est déjà réservée pendant cette plage horaire.'
            ]);
        }

        $reservation->update($validated);

        return redirect()->route('reservations.index')
            ->with('success', 'Réservation mise à jour avec succès.');
    }

    /**
     * Cancel the specified reservation.
     */
    public function cancel(Reservation $reservation)
    {
        // Vérifier si l'utilisateur a le droit d'annuler cette réservation
        if (!Auth::user()->isA('admin') && Auth::id() !== $reservation->user_id) {
            return redirect()->route('reservations.index')
                ->with('error', 'Vous n\'avez pas le droit d\'annuler cette réservation.');
        }

        // Vérifier si la réservation est dans le futur
        if ($reservation->debut < now()) {
            return redirect()->route('reservations.index')
                ->with('error', 'Vous ne pouvez pas annuler une réservation passée.');
        }

        $reservation->is_cancelled = true;
        $reservation->save();

        // Envoyer une notification d'annulation
        $reservation->user->notify(new ReservationCancelled($reservation));

        return redirect()->route('reservations.index')
            ->with('success', 'Réservation annulée avec succès.');
    }

    /**
     * Check room availability
     */
    public function checkAvailability(Request $request)
    {
        $roomId = $request->input('room_id');
        $date = $request->input('date');

        if (!$roomId || !$date) {
            return response()->json(['error' => 'Paramètres manquants'], 400);
        }

        $room = Room::findOrFail($roomId);

        $reservations = Reservation::where('room_id', $roomId)
            ->whereDate('debut', $date)
            ->where('is_cancelled', false)
            ->orderBy('debut')
            ->get()
            ->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'start' => $reservation->debut,
                    'end' => $reservation->fin,
                    'titre' => $reservation->titre,
                    'user' => $reservation->user->name,
                ];
            });

        return response()->json([
            'room' => $room,
            'reservations' => $reservations
        ]);
    }
}
