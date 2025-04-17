<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Room;
use App\Models\User;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Affiche le tableau de bord pour l'utilisateur connecté
     */
    public function index()
    {
        // Si l'utilisateur est admin, afficher le dashboard admin
        if (Auth::user()->isA('admin')) {
            return $this->adminDashboard();
        }

        // Sinon, afficher le dashboard employé
        return $this->employeeDashboard();
    }

    /**
     * Tableau de bord pour les administrateurs
     */
    private function adminDashboard()
    {
        // Statistiques globales
        $totalRooms = Room::count();
        $totalUsers = User::count();
        $totalReservations = Reservation::where('is_cancelled', false)->count();

        // Réservations par jour de la semaine (pour graphique)
        $reservationsByDay = Reservation::selectRaw('DAYOFWEEK(debut) as day, COUNT(*) as count')
            ->where('is_cancelled', false)
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();

        // Taux d'occupation des salles
        $rooms = Room::withCount(['reservations' => function ($query) {
            $query->where('is_cancelled', false);
        }])->get();

        // Obtenir les réservations à venir pour aujourd'hui
        $today = Carbon::today();
        $todayReservations = Reservation::with(['user', 'room'])
            ->whereDate('debut', $today)
            ->where('is_cancelled', false)
            ->orderBy('debut')
            ->get();

        return view('dashboard.admin', compact(
            'totalRooms',
            'totalUsers',
            'totalReservations',
            'reservationsByDay',
            'rooms',
            'todayReservations'
        ));
    }

    /**
     * Tableau de bord pour les employés
     */
    private function employeeDashboard()
    {
        $user = Auth::user();

        // Obtenir les réservations à venir de l'utilisateur
        $upcomingReservations = $user->reservations()
            ->with('room')
            ->where('debut', '>=', now())
            ->where('is_cancelled', false)
            ->orderBy('debut')
            ->take(5)
            ->get();

        // Obtenir les réservations passées de l'utilisateur
        $pastReservations = $user->reservations()
            ->with('room')
            ->where(function($query) {
                $query->where('debut', '<', now())
                      ->orWhere('is_cancelled', true);
            })
            ->orderBy('debut', 'desc')
            ->take(5)
            ->get();

        // Liste des salles disponibles aujourd'hui
        $rooms = Room::all();
        $today = Carbon::today();

        return view('dashboard.employee', compact(
            'upcomingReservations',
            'pastReservations',
            'rooms',
            'today'
        ));
    }
}
