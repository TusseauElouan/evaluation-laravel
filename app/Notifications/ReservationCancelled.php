<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reservation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('reservations.index');

        return (new MailMessage)
                    ->subject('Annulation de votre réservation')
                    ->greeting('Bonjour ' . $notifiable->prenom . ',')
                    ->line('Votre réservation a été annulée.')
                    ->line('Détails de la réservation annulée:')
                    ->line('Salle: ' . $this->reservation->room->nom)
                    ->line('Date: ' . $this->reservation->debut->format('d/m/Y'))
                    ->line('Heure de début: ' . $this->reservation->debut->format('H:i'))
                    ->line('Heure de fin: ' . $this->reservation->fin->format('H:i'))
                    ->action('Voir mes réservations', $url)
                    ->line('La salle est maintenant disponible pour cette plage horaire.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'room_name' => $this->reservation->room->name,
            'debut' => $this->reservation->debut->format('Y-m-d H:i'),
            'fin' => $this->reservation->fin->format('Y-m-d H:i'),
        ];
    }
}
