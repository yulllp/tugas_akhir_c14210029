  <x-layout>
    <h2 class="mx-auto max-w-screen-xl mb-8 text-2xl font-extrabold leading-none tracking-tight text-gray-900 md:text-3xl dark:text-white">
      Pengaturan
    </h2>

    @if (session('success'))
    <div id="alert-border-3" class="flex items-center p-4 mb-4 text-green-800 border-t-4 border-green-300 bg-green-50 dark:text-green-400 dark:bg-gray-800 dark:border-green-800" role="alert">
      <svg class="shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
      </svg>
      <div class="ms-3 text-sm font-medium">
        <span class="font-medium">Informasi!</span> {{ session('success') }}
      </div>
      <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700" data-dismiss-target="#alert-border-3" aria-label="Close">
        <span class="sr-only">Dismiss</span>
        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
        </svg>
      </button>
    </div>
    @endif

    <div class="mx-auto bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
      <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
        @csrf

        <div class="flex space-x-4 justify-between items-center">
          <label for="min_dp_percent" class="block mb-2 text-lg font-medium text-gray-700 dark:text-gray-300">
            Minimum DP (%):
          </label>
          <input
            type="number"
            id="min_dp_percent"
            name="min_dp_percent"
            value="{{ $minDp }}"
            min="0"
            max="100"
            required
            class="max-w-[8rem] text-center py-2 px-3 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
          @error('min_dp_percent')
          <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        <div class="flex space-x-4 justify-between items-center">
          <label for="credit_reminder_days" class="block mb-2 text-lg font-medium text-gray-700 dark:text-gray-300">
            Interval Pengingat Utang (hari):
          </label>
          <input
            type="number"
            id="credit_reminder_days"
            name="credit_reminder_days"
            value="{{ $x }}"
            min="1"
            required
            class="max-w-[6rem] text-center py-2 px-3 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
          @error('credit_reminder_days')
          <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        <div class="flex justify-end">
          <button
            type="submit"
            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md font-medium transition">
            Simpan
          </button>
        </div>
      </form>
    </div>
  </x-layout>