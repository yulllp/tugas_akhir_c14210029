<x-layout>
  <div class="container mx-auto">
    <h2 class="mx-auto max-w-screen-xl mb-8 text-2xl font-extrabold leading-none tracking-tight text-gray-900 dark:text-white">
      Log Aktivitas
    </h2>

    <div class="overflow-x-auto shadow-lg rounded-lg">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-100 dark:bg-gray-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">
              Tanggal
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">
              User
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">
              Deskripsi
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">
              Log Name
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">
              Subject
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">
              Properties
            </th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($activities as $activity)
          @php
          $modalId = "properties-modal-{$activity->id}";
          $props = $activity->properties->toArray();
          @endphp

          <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <!-- Tanggal -->
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
              {{ $activity->created_at->format('d M Y H:i') }}
            </td>

            <!-- User -->
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
              {{ optional($activity->causer)->name ?? 'System' }}
            </td>

            <!-- Deskripsi -->
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
              {{ $activity->description }}
            </td>

            <!-- Log Name -->
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
              {{ $activity->log_name }}
            </td>

            <!-- Subject -->
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
              @if($activity->subject)
              {{ class_basename($activity->subject_type) }} (ID: {{ $activity->subject_id }})
              @else
              —
              @endif
            </td>

            <!-- Properties Button -->
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <button
                onclick="openModal('{{ $modalId }}')"
                class="px-3 py-1 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition">
                Detail
              </button>

              <!-- Modal Overlay -->
              <div
                id="{{ $modalId }}"
                class="fixed inset-0 z-50 hidden flex items-center justify-center bg-opacity-40 backdrop-blur-sm"
                onclick="closeModal('{{ $modalId }}')"
                aria-hidden="true">
                <div
                  class="bg-white dark:bg-gray-900 rounded-lg w-full max-w-lg mx-4 shadow-xl overflow-hidden"
                  onclick="event.stopPropagation()">
                  <!-- Modal Header -->
                  <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                      Properties Detail
                    </h2>
                    <button
                      onclick="closeModal('{{ $modalId }}')"
                      class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-xl font-bold leading-none"
                      aria-label="Close modal">
                      &times;
                    </button>
                  </div>

                  <!-- Modal Body -->
                  <div class="px-6 py-5 max-h-80 overflow-y-auto">
                    @if(count($props) > 0)
                    @foreach($props as $key => $value)
                    <div class="mb-4">
                      {{-- Tampilkan kunci utama --}}
                      <span class="font-medium text-gray-800 dark:text-gray-200">{{ $key }}:</span>

                      @if(is_array($value))
                      {{-- Jika nested JSON/array, tampilkan di dalam container terindentasi --}}
                      <div class="ml-4 mt-2 space-y-2">
                        @foreach($value as $subKey => $subValue)
                        <div class="flex">
                          <span class="font-medium text-gray-700 dark:text-gray-300">{{ $subKey }}:</span>
                          <span class="ml-2 text-gray-600 dark:text-gray-400">
                            {{ $subValue }}
                          </span>
                        </div>
                        @endforeach
                      </div>
                      @else
                      {{-- Jika hanya nilai scalar --}}
                      <span class="ml-2 text-gray-700 dark:text-gray-300">
                        {{ $value }}
                      </span>
                      @endif
                    </div>
                    @endforeach
                    @else
                    <p class="text-gray-500 italic dark:text-gray-400">No properties available.</p>
                    @endif
                  </div>

                  <!-- Modal Footer -->
                  <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 text-right">
                    <button
                      onclick="closeModal('{{ $modalId }}')"
                      class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-600 transition">
                      Close
                    </button>
                  </div>
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
              Belum ada aktivitas tercatat.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-6">
      {{ $activities->links() }}
    </div>
  </div>

  <!-- Plain JavaScript to open/close modals -->
  <script>
    function openModal(id) {
      const modal = document.getElementById(id);
      if (modal) modal.classList.remove('hidden');
    }

    function closeModal(id) {
      const modal = document.getElementById(id);
      if (modal) modal.classList.add('hidden');
    }
  </script>
</x-layout>