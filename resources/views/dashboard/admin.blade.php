<x-app-layout>
  <x-slot name="header">
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ __('Tableau de bord administrateur') }}
      </h2>
  </x-slot>

  <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <!-- Statistiques générales -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
              <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                  <div class="text-gray-500 text-sm mb-1">Salles</div>
                  <div class="text-3xl font-bold text-gray-800">{{ $totalRooms }}</div>
              </div>
              <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                  <div class="text-gray-500 text-sm mb-1">Utilisateurs</div>
                  <div class="text-3xl font-bold text-gray-800">{{ $totalUsers }}</div>
              </div>
              <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                  <div class="text-gray-500 text-sm mb-1">Réservations</div>
                  <div class="text-3xl font-bold text-gray-800">{{ $totalReservations }}</div>
              </div>
              <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                  <div class="text-gray-500 text-sm mb-1">Taux d'occupation</div>
                  <div class="text-3xl font-bold text-gray-800">
                      {{ $totalReservations > 0 ? round(($totalReservations / ($totalRooms * 10)) * 100) : 0 }}%
                  </div>
              </div>
          </div>

          <!-- Réservations du jour -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
              <div class="p-6 bg-white border-b border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">Réservations d'aujourd'hui</h3>

                  @if($todayReservations->isEmpty())
                      <p class="text-center text-gray-500 py-4">Aucune réservation pour aujourd'hui</p>
                  @else
                      <div class="overflow-x-auto">
                          <table class="min-w-full divide-y divide-gray-200">
                              <thead class="bg-gray-50">
                                  <tr>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horaire</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salle</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Objet</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Réservé par</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                  </tr>
                              </thead>
                              <tbody class="bg-white divide-y divide-gray-200">
                                  @foreach($todayReservations as $reservation)
                                      <tr>
                                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                              {{ $reservation->debut->format('H:i') }} - {{ $reservation->fin->format('H:i') }}
                                          </td>
                                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                              {{ $reservation->room->nom }}
                                          </td>
                                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                              {{ $reservation->titre }}
                                          </td>
                                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                              {{ $reservation->user->prenom }} {{ $reservation->user->nom }}
                                          </td>
                                          <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                              <a href="{{ route('reservations.show', $reservation) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                  <i class="fas fa-eye"></i>
                                              </a>
                                              @if(!$reservation->isPast())
                                              <a href="{{ route('reservations.edit', $reservation) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                                  <i class="fas fa-edit"></i>
                                              </a>
                                              <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation?');">
                                                  @csrf
                                                  <button type="submit" class="text-red-600 hover:text-red-900">
                                                      <i class="fas fa-times-circle"></i>
                                                  </button>
                                              </form>
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

          <!-- Statistiques des salles -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Taux d'occupation des salles -->
              <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                  <div class="p-6 bg-white border-b border-gray-200">
                      <h3 class="text-lg font-semibold text-gray-800 mb-4">Taux d'occupation des salles</h3>

                      @if($rooms->isEmpty())
                          <p class="text-center text-gray-500 py-4">Aucune salle disponible</p>
                      @else
                          <div class="space-y-4">
                              @foreach($rooms as $room)
                                  <div>
                                      <div class="flex justify-between mb-1">
                                          <span class="text-sm font-semibold">{{ $room->nom }}</span>
                                          <span class="text-sm text-gray-500">{{ $room->reservations_count }} réservations</span>
                                      </div>
                                      <div class="w-full bg-gray-200 rounded-full h-2.5">
                                          @php
                                              $percentage = $totalReservations > 0 ? min(100, ($room->reservations_count / ($totalReservations / count($rooms) * 2)) * 100) : 0;
                                          @endphp
                                          <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                                      </div>
                                  </div>
                              @endforeach
                          </div>
                      @endif
                  </div>
              </div>

              <!-- Réservations par jour de la semaine -->
              <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                  <div class="p-6 bg-white border-b border-gray-200">
                      <h3 class="text-lg font-semibold text-gray-800 mb-4">Réservations par jour</h3>

                      @php
                          $daysOfWeek = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                          $maxCount = $reservationsByDay->max() ?: 1;
                      @endphp

                      <div class="space-y-4">
                          @foreach($daysOfWeek as $i => $day)
                              @php
                                  $count = $reservationsByDay[$i+1] ?? 0;
                                  $percentage = ($count / $maxCount) * 100;
                              @endphp
                              <div>
                                  <div class="flex justify-between mb-1">
                                      <span class="text-sm font-semibold">{{ $day }}</span>
                                      <span class="text-sm text-gray-500">{{ $count }} réservations</span>
                                  </div>
                                  <div class="w-full bg-gray-200 rounded-full h-2.5">
                                      <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                                  </div>
                              </div>
                          @endforeach
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div>
</x-app-layout>
