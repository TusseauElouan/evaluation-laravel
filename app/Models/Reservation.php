<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int $room_id
 * @property \Illuminate\Support\Carbon $debut
 * @property \Illuminate\Support\Carbon $fin
 * @property string|null $titre
 * @property string|null $description
 * @property bool $is_cancelled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $duration
 * @property-read mixed $formatted_end_time
 * @property-read mixed $formatted_start_date
 * @property-read mixed $formatted_start_time
 * @property-read \App\Models\Room $room
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\ReservationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereDebut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereFin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereIsCancelled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereRoomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereTitre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reservation whereUserId($value)
 * @mixin \Eloquent
 */
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
