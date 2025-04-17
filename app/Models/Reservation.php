<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Reservation extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignables.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'room_id',
        'user_id',
        'debut',
        'fin',
        'titre',
        'description',
        'is_cancelled',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'debut' => 'datetime',
        'fin' => 'datetime',
        'is_cancelled' => 'boolean',
    ];

    /**
     * Obtenir l'utilisateur qui a créé la réservation.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtenir la salle associée à la réservation.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Vérifier si la réservation est à venir
     */
    public function isUpcoming()
    {
        return $this->debut > Carbon::now();
    }

    /**
     * Vérifier si la réservation est en cours
     */
    public function isInProgress()
    {
        $now = Carbon::now();
        return $this->debut <= $now && $this->fin >= $now;
    }

    /**
     * Vérifier si la réservation est passée
     */
    public function isPast()
    {
        return $this->fin < Carbon::now();
    }

    /**
     * Obtenir la durée de la réservation en format lisible
     */
    public function getDurationAttribute()
    {
        $start = $this->debut;
        $end = $this->fin;

        $diffInMinutes = $start->diffInMinutes($end);

        if ($diffInMinutes < 60) {
            return $diffInMinutes . ' min';
        }

        $hours = floor($diffInMinutes / 60);
        $minutes = $diffInMinutes % 60;

        $result = $hours . 'h';
        if ($minutes > 0) {
            $result .= $minutes . 'min';
        }

        return $result;
    }

    /**
     * Obtenir la date de début au format français
     */
    public function getFormattedStartDateAttribute()
    {
        return $this->debut->format('d/m/Y');
    }

    /**
     * Obtenir l'heure de début au format français
     */
    public function getFormattedStartTimeAttribute()
    {
        return $this->debut->format('H:i');
    }

    /**
     * Obtenir l'heure de fin au format français
     */
    public function getFormattedEndTimeAttribute()
    {
        return $this->fin->format('H:i');
    }
}
