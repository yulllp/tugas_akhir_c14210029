<x-layout>
  <x-header :href="route('products.index')" title="{{ $title }}" />
  <div class="mx-auto max-w-screen-xl">
    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
      <form class="p-4 md:p-5" action="{{ route('products.store') }}" method="POST">
        @csrf
        <div class="grid gap-6 mb-6 md:grid-cols-2">
          <div class="col-span-2">
            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama</label>
            <input type="text" name="name" id="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Masukan nama" required="">
            @error('name')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
          </div>
          <div class="col-span-2 sm:col-span-1">
            <label for="kode" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Kode</label>
            <input type="text" name="kode" id="kode" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Kode" required="">
            @error('kode')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
          </div>
          <div class="col-span-2 sm:col-span-1">
            <label for="minStok" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Min. Stok</label>
            <input type="number" min="0" name="minStok" id="minStok" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Min. stok" required="">
            @error('minStok')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
          </div>
          <div class="col-span-2 sm:col-span-1">
            <label for="price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Harga Jual</label>
            <input type="number" min="0" name="price" id="price" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Harga" required="">
            @error('price')
            <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
          </div>
        </div>
        <div class="flex justify-end">
          <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
            Tambah
          </button>
        </div>
      </form>
    </div>
  </div>
</x-layout>