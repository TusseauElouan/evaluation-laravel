<x-app-layout>
  <x-slot name="header">
      <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold text-gray-800">
              {{ __('Mes réservations') }}
          </h2>
          <a href="{{ route('reservations.create') }}" class="inline-flex items-center gap-x-2 px-5 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-wider hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition">
              <i class="fas fa-calendar-plus"></i> {{ __('Nouvelle réservation') }}
          </a>
      </div>
  </x-slot>

  <div class="py-12 bg-gray-50">
      <div class="max-w-7xl mx-auto px-6 space-y-8">
          @foreach (['success' => 'green', 'error' => 'red'] as $type => $color)
              @if(session($type))
                  <div class="bg-{{ $color }}-100 border border-{{ $color }}-400 text-{{ $color }}-800 px-4 py-3 rounded" role="alert">
                      <span class="block">{{ session($type) }}</span>
                  </div>
              @endif
          @endforeach

          <!-- Réservations à venir -->
          <div class="bg-white shadow-md rounded-xl p-6">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Réservations à venir</h3>

              @if($upcoming->isEmpty())
                  <p class="text-center text-gray-500">{{ __('Vous n\'avez aucune réservation à venir.') }}</p>
              @else
                  <div class="overflow-x-auto">
                      <table class="min-w-full divide-y divide-gray-200 text-sm">
                          <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-medium tracking-wider">
                              <tr>
                                  <th class="px-6 py-3 text-left">Date</th>
                                  <th class="px-6 py-3 text-left">Horaire</th>
                                  <th class="px-6 py-3 text-left">Salle</th>
                                  <th class="px-6 py-3 text-left">Objet</th>
                                  @if(auth()->user()->isA('admin'))
                                      <th class="px-6 py-3 text-left">Réservé par</th>
                                  @endif
                                  <th class="px-6 py-3 text-left">Actions</th>
                              </tr>
                          </thead>
                          <tbody class="bg-white divide-y divide-gray-200">
                              @foreach($upcoming as $reservation)
                                  <tr>
                                      <td class="px-6 py-4 text-gray-900 font-medium">{{ $reservation->debut->format('d/m/Y') }}</td>
                                      <td class="px-6 py-4 text-gray-600">{{ $reservation->debut->format('H:i') }} - {{ $reservation->fin->format('H:i') }}</td>
                                      <td class="px-6 py-4 text-gray-600">{{ $reservation->room->nom }}</td>
                                      <td class="px-6 py-4 text-gray-600">{{ Str::limit($reservation->titre, 30) }}</td>
                                      @if(auth()->user()->isA('admin'))
                                          <td class="px-6 py-4 text-gray-600">{{ $reservation->user->nom }} {{ $reservation->user->prenom }}</td>
                                      @endif
                                      <td class="px-6 py-4">
                                          <div class="flex items-center space-x-2">
                                              <a href="{{ route('reservations.show', $reservation) }}" class="text-blue-600 hover:text-blue-800" title="Voir">
                                                  <i class="fas fa-eye"></i>
                                              </a>
                                              <a href="{{ route('reservations.edit', $reservation) }}" class="text-yellow-600 hover:text-yellow-800" title="Modifier">
                                                  <i class="fas fa-edit"></i>
                                              </a>
                                              <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation?');">
                                                  @csrf
                                                  <button type="submit" class="text-red-600 hover:text-red-800" title="Annuler">
                                                      <i class="fas fa-times-circle"></i>
                                                  </button>
                                              </form>
                                          </div>
                                      </td>
                                  </tr>
                              @endforeach
                          </tbody>
                      </table>
                  </div>
              @endif
          </div>

          <!-- Réservations passées -->
          <div class="bg-white shadow-md rounded-xl p-6">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Réservations passées ou annulées</h3>

              @if($past->isEmpty())
                  <p class="text-center text-gray-500">{{ __('Aucune réservation passée ou annulée.') }}</p>
              @else
                  <div class="overflow-x-auto">
                      <table class="min-w-full divide-y divide-gray-200 text-sm">
                          <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-medium tracking-wider">
                              <tr>
                                  <th class="px-6 py-3 text-left">Date</th>
                                  <th class="px-6 py-3 text-left">Horaire</th>
                                  <th class="px-6 py-3 text-left">Salle</th>
                                  <th class="px-6 py-3 text-left">Objet</th>
                                  @if(auth()->user()->isA('admin'))
                                      <th class="px-6 py-3 text-left">Réservé par</th>
                                  @endif
                                  <th class="px-6 py-3 text-left">Statut</th>
                                  <th class="px-6 py-3 text-left">Actions</th>
                              </tr>
                          </thead>
                          <tbody class="bg-white divide-y divide-gray-200">
                              @foreach($past as $reservation)
                                  <tr>
                                      <td class="px-6 py-4 text-gray-900 font-medium">{{ $reservation->debut->format('d/m/Y') }}</td>
                                      <td class="px-6 py-4 text-gray-600">{{ $reservation->debut->format('H:i') }} - {{ $reservation->fin->format('H:i') }}</td>
                                      <td class="px-6 py-4 text-gray-600">{{ optional($reservation->room)->nom ?? 'Salle inconnue' }}</td>
                                      <td class="px-6 py-4 text-gray-600">{{ Str::limit($reservation->titre, 30) }}</td>
                                      @if(auth()->user()->isA('admin'))
                                          <td class="px-6 py-4 text-gray-600">{{ $reservation->user->nom }} {{ $reservation->user->prenom }}</td>
                                      @endif
                                      <td class="px-6 py-4">
                                          <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full
                                              {{ $reservation->is_cancelled ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' }}">
                                              {{ $reservation->is_cancelled ? 'Annulée' : 'Terminée' }}
                                          </span>
                                      </td>
                                      <td class="px-6 py-4">
                                          <a href="{{ route('reservations.show', $reservation) }}" class="text-blue-600 hover:text-blue-800" title="Voir">
                                              <i class="fas fa-eye"></i>
                                          </a>
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
</x-app-layout>
