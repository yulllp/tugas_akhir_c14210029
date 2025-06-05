<x-layout>
  <x-header :href="route('customers.index')" title="{{ $title }}"></x-header>
  <div class="mx-auto max-w-screen-xl gap-8 grid sm:grid-cols-2 grid-cols-1 mt-4">
    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden col-span-1">
      <form class="p-4 md:p-5" action="{{ route('customers.update', $customer) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="grid gap-6 mb-6 grid-cols-1">
          <div class="col-span-1">
            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama</label>
            <input type="text" value="{{ $customer->name }}" name="name" id="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Masukan nama" required="">
            @error('name')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
          </div>
          <div class="col-span-1">
            <label for="address" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Alamat</label>
            <input type="text" value="{{ $customer->address }}" name="address" id="address" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Alamat" required="">
            @error('address')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
          </div>
          <div class="col-span-1">
            <label for="phone" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">No. Telepon</label>
            <input type="text" value="{{ $customer->phone }}" name="phone" id="phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="No. Telepon" required="">
            @error('phone')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
          </div>
          <div class="col-span-1 mt-4">
            <button type="submit" class="w-full text-white flex justify-center items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
              Simpan
            </button>
          </div>
        </div>
      </form>
    </div>
    <div class="col-span-1 flex max-h-42 p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert">
      <svg class="shrink-0 inline w-4 h-4 me-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
      </svg>
      <span class="sr-only">Informasi</span>
      <div>
        <span class="font-medium">Pastikan syarat pembuatan pelanggan ini terpenuhi:</span>
        <ul class="mt-1.5 list-disc list-inside">
          <li>Nama pelanggan tidak boleh sama</li>
          <li>Periksa kembali daftar nama pelanggan agar menghindari pembuatn data pelanggan yang berlebihan</li>
          <li>Data pelanggan akan digunakan untuk memonitor transaksi dan utang pelanggan</li>
        </ul>
      </div>
    </div>
  </div>
</x-layout>