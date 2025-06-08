<x-layout>
  <x-header :href="route('users.index')" title="{{ $title ?? 'Create User' }}"></x-header>

  <div class="mx-auto max-w-screen-xl grid md:grid-cols-2 grid-cols-1 mt-4 gap-8">
    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden col-span-1">
      <form class="p-4 md:p-5" action="{{ route('users.store') }}" method="POST">
        @csrf

        <div class="grid gap-4 grid-cols-1">
          <div class="col-span-1">
            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
              Nama
            </label>
            <input
              type="text"
              name="name"
              id="name"
              value="{{ old('name') }}"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                     focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 
                     dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white 
                     dark:focus:ring-primary-500 dark:focus:border-primary-500"
              placeholder="Masukkan nama"
              required
            >
            @error('name')
              <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div class="col-span-1">
            <label for="username" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
              Username
            </label>
            <input
              type="text"
              name="username"
              id="username"
              value="{{ old('username') }}"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                     focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 
                     dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white 
                     dark:focus:ring-primary-500 dark:focus:border-primary-500"
              placeholder="Masukkan username"
              required
            >
            @error('username')
              <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
          </div>

          <div class="col-span-1">
            <label for="role" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
              Role
            </label>
            <select
              name="role"
              id="role"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                     focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 
                     dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white 
                     dark:focus:ring-primary-500 dark:focus:border-primary-500"
              required
            >
              <option value="">— Pilih Role —</option>
              <option value="owner" {{ old('role') === 'owner' ? 'selected' : '' }}>Owner</option>
              <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
            @error('role')
              <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
          </div>

          {{-- Status --}}
          <div class="col-span-1">
            <label for="status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
              Status
            </label>
            <select
              name="status"
              id="status"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                     focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 
                     dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white 
                     dark:focus:ring-primary-500 dark:focus:border-primary-500"
              required
            >
              <option value="">— Pilih Status —</option>
              <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Aktif</option>
              <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>
                Tidak Aktif
              </option>
            </select>
            @error('status')
              <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
          </div>

          {{-- Submit Button --}}
          <div class="col-span-1 mt-4">
            <button
              type="submit"
              class="w-full text-white flex justify-center items-center bg-blue-700 
                     hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 
                     font-medium rounded-lg text-sm px-5 py-2.5 text-center 
                     dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
            >
              Tambah
            </button>
          </div>
        </div>
      </form>
    </div>

    {{-- Right column: Info Box --}}
    <div class="col-span-1 flex max-h-42 p-4 mb-4 text-sm text-blue-800 rounded-lg 
                bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert">
      <svg class="shrink-0 inline w-4 h-4 me-3 mt-[2px]" aria-hidden="true"
           xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
        <path
          d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 
             4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 
             1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 
             1 0 2Z"
        />
      </svg>
      <span class="sr-only">Informasi</span>
      <div>
        <span class="font-medium">Pastikan data user ini sudah sesuai:</span>
        <ul class="mt-1.5 list-disc list-inside">
          <li>Nama pengguna tidak boleh sama dengan yang sudah ada.</li>
          <li>Username harus unik dan mudah diingat.</li>
          <li>User ini akan menggunakan password default <code>12345678</code>. Silakan ubah nanti.</li>
        </ul>
      </div>
    </div>
  </div>
</x-layout>
