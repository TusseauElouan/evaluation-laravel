<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Les attributs qui sont mass assignables.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'capacite',
        'surface',
        'equipment',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capacite' => 'integer',
        'surface' => 'float',
    ];

    /**
     * Obtenir les réservations pour cette salle.
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Vérifier si la salle est disponible pour une plage horaire donnée
     */
    public function isAvailable($debut, $fin, $excludeReservationId = null)
    {
        $query = $this->reservations()
            ->where('is_cancelled', false)
            ->where(function($query) use ($debut, $fin) {
                $query->whereBetween('debut', [$debut, $fin])
                    ->orWhereBetween('fin', [$debut, $fin])
                    ->orWhere(function($query) use ($debut, $fin) {
                        $query->where('debut', '<=', $debut)
                              ->where('fin', '>=', $fin);
                    });
            });

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return !$query->exists();
    }
}
