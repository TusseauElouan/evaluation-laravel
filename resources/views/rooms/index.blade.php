<x-app-layout>
  <x-slot name="header">
      <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold text-gray-900">
              {{ __('Gestion des salles') }}
          </h2>
          @if(auth()->user()->isA('admin'))
              <a href="{{ route('rooms.create') }}"
                 class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition duration-150 shadow">
                  <i class="fas fa-plus"></i>
                  {{ __('Ajouter une salle') }}
              </a>
          @endif
      </div>
  </x-slot>

  <div class="py-12 bg-gray-50">
      <div class="max-w-7xl mx-auto px-6">
          @if(session('success'))
              <div class="mb-6 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg shadow-sm">
                  {{ session('success') }}
              </div>
          @endif

          @if(session('error'))
              <div class="mb-6 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg shadow-sm">
                  {{ session('error') }}
              </div>
          @endif

          <div class="bg-white shadow-xl rounded-2xl p-8">
              @if($rooms->isEmpty())
                  <p class="text-center text-gray-500 text-lg">{{ __('Aucune salle n\'a été ajoutée pour le moment.') }}</p>
              @else
                  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                      @foreach($rooms as $room)
                          <div class="bg-white border border-gray-100 rounded-2xl shadow-md p-6 hover:shadow-lg transition duration-300">
                              <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $room->nom }}</h3>

                              <ul class="text-sm text-gray-600 space-y-1 ml-1">
                                  <li><i class="fas fa-users mr-2 text-gray-400"></i>Capacité : <span class="font-medium">{{ $room->capacite }}</span> personnes</li>
                                  @if($room->surface)
                                      <li><i class="fas fa-vector-square mr-2 text-gray-400"></i>Surface : <span class="font-medium">{{ $room->surface }} m²</span></li>
                                  @endif
                              </ul>

                              @if($room->equipment)
                                  <div class="mt-4">
                                      <h4 class="text-sm font-semibold text-gray-700 mb-1">Équipements :</h4>
                                      <p class="text-sm text-gray-600">{{ $room->equipment }}</p>
                                  </div>
                              @endif

                              <div class="mt-6 flex justify-between items-center">
                                  <a href="{{ route('rooms.show', $room) }}"
                                     class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition">
                                      Détails
                                  </a>

                                  <div class="flex items-center gap-2">
                                      <a href="{{ route('reservations.create', ['room_id' => $room->id]) }}"
                                         class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-xl hover:bg-green-700 transition">
                                          Réserver
                                      </a>

                                      @if(auth()->user()->isA('admin'))
                                          <a href="{{ route('rooms.edit', $room) }}"
                                             class="inline-flex items-center px-3 py-2 text-sm text-white bg-yellow-500 rounded-xl hover:bg-yellow-600 transition">
                                              <i class="fas fa-edit"></i>
                                          </a>

                                          <form action="{{ route('rooms.destroy', $room) }}" method="POST" class="inline"
                                                onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette salle?');">
                                              @csrf
                                              @method('DELETE')
                                              <button type="submit"
                                                      class="inline-flex items-center px-3 py-2 text-sm text-white bg-red-600 rounded-xl hover:bg-red-700 transition">
                                                  <i class="fas fa-trash"></i>
                                              </button>
                                          </form>
                                      @endif
                                  </div>
                              </div>
                          </div>
                      @endforeach
                  </div>
              @endif
          </div>
      </div>
  </div>
</x-app-layout>
