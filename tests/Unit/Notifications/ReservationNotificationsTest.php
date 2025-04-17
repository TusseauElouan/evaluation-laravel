<?php

namespace Tests\Unit\Notifications;

use App\Models\Room;
use App\Models\User;
use App\Models\Reservation;
use App\Notifications\ReservationConfirmation;
use App\Notifications\ReservationCancelled;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReservationNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $room;
    protected $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'prenom' => 'Test',
            'nom' => 'User',
            'email' => 'test@example.com'
        ]);

        // Create a test room
        $this->room = Room::factory()->create([
            'nom' => 'Salle de Test',
            'capacite' => 10
        ]);

        // Create a test reservation
        $this->reservation = Reservation::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'debut' => Carbon::tomorrow()->setHour(10)->setMinute(0),
            'fin' => Carbon::tomorrow()->setHour(12)->setMinute(0),
            'titre' => 'Réunion Test',
            'description' => 'Description de test'
        ]);
    }

    /** @test */
    public function reservation_confirmation_notification_contains_correct_data()
    {
        // Create the notification
        $notification = new ReservationConfirmation($this->reservation);

        // Get the mail representation
        $mailData = $notification->toMail($this->user);

        // Check that the notification has the correct content
        $this->assertStringContainsString('Bonjour ' . $this->user->prenom, $mailData->greeting);
        $this->assertStringContainsString('Confirmation de votre réservation', $mailData->subject);
        $this->assertStringContainsString('Salle: ' . $this->room->nom, $mailData->render());
        $this->assertStringContainsString('Date: ' . $this->reservation->debut->format('d/m/Y'), $mailData->render());
        $this->assertStringContainsString('Heure de début: ' . $this->reservation->debut->format('H:i'), $mailData->render());
        $this->assertStringContainsString('Heure de fin: ' . $this->reservation->fin->format('H:i'), $mailData->render());

        // Check the action button is present
        $this->assertEquals('Voir les détails', $mailData->actionText);
        $this->assertEquals(route('reservations.show', $this->reservation), $mailData->actionUrl);
    }

    /** @test */
    public function reservation_cancellation_notification_contains_correct_data()
    {
        // Create the notification
        $notification = new ReservationCancelled($this->reservation);

        // Get the mail representation
        $mailData = $notification->toMail($this->user);

        // Check that the notification has the correct content
        $this->assertStringContainsString('Bonjour ' . $this->user->prenom, $mailData->greeting);
        $this->assertStringContainsString('Annulation de votre réservation', $mailData->subject);
        $this->assertStringContainsString('Salle: ' . $this->room->nom, $mailData->render());
        $this->assertStringContainsString('Date: ' . $this->reservation->debut->format('d/m/Y'), $mailData->render());
        $this->assertStringContainsString('Heure de début: ' . $this->reservation->debut->format('H:i'), $mailData->render());
        $this->assertStringContainsString('Heure de fin: ' . $this->reservation->fin->format('H:i'), $mailData->render());

        // Check the action button is present
        $this->assertEquals('Voir mes réservations', $mailData->actionText);
        $this->assertEquals(route('reservations.index'), $mailData->actionUrl);
    }

    /** @test */
    public function notifications_are_sent_when_expected()
    {
        Notification::fake();

        // Simulate creating a new reservation
        $this->user->notify(new ReservationConfirmation($this->reservation));

        // Check that a confirmation notification was sent
        Notification::assertSentTo(
            $this->user,
            ReservationConfirmation::class,
            function ($notification, $channels) {
                return $notification->getReservation()->id === $this->reservation->id;
            }
        );

        // Simulate cancelling a reservation
        $this->user->notify(new ReservationCancelled($this->reservation));

        // Check that a cancellation notification was sent
        Notification::assertSentTo(
            $this->user,
            ReservationCancelled::class,
            function ($notification, $channels) {
                return $notification->getReservation()->id === $this->reservation->id;
            }
        );
    }

    /** @test */
    public function reservation_confirmation_notification_array_representation_is_correct()
    {
        // Create the notification
        $notification = new ReservationConfirmation($this->reservation);

        // Get the array representation
        $arrayData = $notification->toArray($this->user);

        // Check that the array contains the correct data
        $this->assertEquals($this->reservation->id, $arrayData['reservation_id']);
        $this->assertEquals($this->room->nom, $arrayData['room_name']);
        $this->assertEquals($this->reservation->debut->format('Y-m-d H:i'), $arrayData['debut']);
        $this->assertEquals($this->reservation->fin->format('Y-m-d H:i'), $arrayData['fin']);
    }

    /** @test */
    public function reservation_cancellation_notification_array_representation_is_correct()
    {
        // Create the notification
        $notification = new ReservationCancelled($this->reservation);

        // Get the array representation
        $arrayData = $notification->toArray($this->user);

        // Check that the array contains the correct data
        $this->assertEquals($this->reservation->id, $arrayData['reservation_id']);
        $this->assertEquals($this->reservation->debut->format('Y-m-d H:i'), $arrayData['debut']);
        $this->assertEquals($this->reservation->fin->format('Y-m-d H:i'), $arrayData['fin']);
    }
}
