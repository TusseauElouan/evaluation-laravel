<x-app-layout>
  {{-- En-tête de la page --}}
  <x-slot name="header">
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ __('Tableau de bord') }}
      </h2>
  </x-slot>

  <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

          {{-- Message de bienvenue --}}
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
              <div class="p-6 bg-white border-b border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800">Bonjour {{ Auth::user()->prenom }},</h3>
                  <p class="text-gray-600 mt-1">Bienvenue sur votre tableau de bord de réservation de salles.</p>
              </div>
          </div>

          {{-- Prochaines réservations --}}
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
              <div class="p-6 bg-white border-b border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">Vos prochaines réservations</h3>

                  @if($upcomingReservations->isEmpty())
                      <p class="text-center text-gray-500 py-4">Vous n'avez aucune réservation à venir</p>
                  @else
                      {{-- Tableau des réservations à venir --}}
                      <div class="overflow-x-auto">
                          <table class="min-w-full divide-y divide-gray-200">
                              <thead class="bg-gray-50">
                                  <tr>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horaire</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salle</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Objet</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                  </tr>
                              </thead>
                              <tbody class="bg-white divide-y divide-gray-200">
                                  @foreach($upcomingReservations as $reservation)
                                      <tr>
                                          <td class="px-6 py-4 text-sm text-gray-900">
                                              {{ $reservation->debut->format('d/m/Y') }}
                                          </td>
                                          <td class="px-6 py-4 text-sm text-gray-900">
                                              {{ $reservation->debut->format('H:i') }} - {{ $reservation->fin->format('H:i') }}
                                          </td>
                                          <td class="px-6 py-4 text-sm text-gray-900">
                                              {{ $reservation->room->nom }}
                                          </td>
                                          <td class="px-6 py-4 text-sm text-gray-900">
                                              {{ $reservation->titre }}
                                          </td>
                                          <td class="px-6 py-4 text-sm font-medium">
                                              {{-- Lien vers les détails --}}
                                              <a href="{{ route('reservations.show', $reservation) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                  <i class="fas fa-eye"></i>
                                              </a>
                                              {{-- Lien vers l'édition --}}
                                              <a href="{{ route('reservations.edit', $reservation) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                                  <i class="fas fa-edit"></i>
                                              </a>
                                              {{-- Formulaire d'annulation --}}
                                              <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation?');">
                                                  @csrf
                                                  <button type="submit" class="text-red-600 hover:text-red-900">
                                                      <i class="fas fa-times-circle"></i>
                                                  </button>
                                              </form>
                                          </td>
                                      </tr>
                                  @endforeach
                              </tbody>
                          </table>
                      </div>

                      {{-- Lien vers toutes les réservations --}}
                      <div class="mt-4 text-right">
                          <a href="{{ route('reservations.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                              Voir toutes mes réservations →
                          </a>
                      </div>
                  @endif
              </div>
          </div>

          {{-- Salles disponibles aujourd'hui --}}
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                  <div class="p-6 bg-white border-b border-gray-200">
                      <h3 class="text-lg font-semibold text-gray-800 mb-4">Salles disponibles aujourd'hui</h3>

                      <div class="space-y-3">
                          @forelse($rooms as $room)
                              <div class="border rounded-lg p-4">
                                  <div class="flex justify-between items-center">
                                      <div>
                                          <h4 class="font-semibold">{{ $room->nom }}</h4>
                                          <p class="text-sm text-gray-600">Capacité: {{ $room->capacite }} personnes</p>
                                          @if($room->surface)
                                              <p class="text-sm text-gray-600">Surface: {{ $room->surface }} m²</p>
                                          @endif
                                      </div>
                                      {{-- Lien pour réserver cette salle --}}
                                      <a href="{{ route('reservations.create', ['room_id' => $room->id]) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                          Réserver
                                      </a>
                                  </div>
                              </div>
                          @empty
                              <p class="text-center text-gray-500 py-4">Aucune salle disponible</p>
                          @endforelse
                      </div>

                      {{-- Lien vers toutes les salles --}}
                      <div class="mt-4 text-right">
                          <a href="{{ route('rooms.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                              Voir toutes les salles →
                          </a>
                      </div>
                  </div>
              </div>
          </div>

          {{-- Historique des réservations --}}
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
              <div class="p-6 bg-white border-b border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">Historique de réservations</h3>

                  @if($pastReservations->isEmpty())
                      <p class="text-center text-gray-500 py-4">Vous n'avez aucune réservation passée</p>
                  @else
                      {{-- Tableau des réservations passées --}}
                      <div class="overflow-x-auto">
                          <table class="min-w-full divide-y divide-gray-200">
                              <thead class="bg-gray-50">
                                  <tr>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horaire</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salle</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Objet</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                  </tr>
                              </thead>
                              <tbody class="bg-white divide-y divide-gray-200">
                                  @foreach($pastReservations as $reservation)
                                      <tr>
                                          <td class="px-6 py-4 text-sm text-gray-900">
                                              {{ $reservation->debut->format('d/m/Y') }}
                                          </td>
                                          <td class="px-6 py-4 text-sm text-gray-900">
                                              {{ $reservation->debut->format('H:i') }} - {{ $reservation->fin->format('H:i') }}
                                          </td>
                                          <td class="px-6 py-4 text-sm text-gray-900">
                                              {{ $reservation->room->nom }}
                                          </td>
                                          <td class="px-6 py-4 text-sm text-gray-900">
                                              {{ $reservation->titre }}
                                          </td>
                                          <td class="px-6 py-4">
                                              @if($reservation->is_cancelled)
                                                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                      Annulée
                                                  </span>
                                              @else
                                                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                      Terminée
                                                  </span>
                                              @endif
                                          </td>
                                      </tr>
                                  @endforeach
                              </tbody>
                          </table>
                      </div>
                  @endif
              </div>
          </div>
      </div>
  </div>
</x-app-layout>
