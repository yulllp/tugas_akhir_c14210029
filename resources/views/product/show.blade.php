<x-layout>
  <x-header :href="route('products.index')" title="{{ $title }}" />
  <div class="mx-auto max-w-screen-xl">
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
    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
      <div class="flex justify-end px-4 pt-4 space-x-5">
        @if(Auth::check() && Auth::user()->role === 'owner')
        <a href="{{ route('products.edit', $product->id) }}" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-3 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="shrink-0 w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
          </svg>
        </a>
        @endif
      </div>
      <div class="p-4 md:p-5">
        <div class="grid gap-6 mb-6 md:grid-cols-2">
          <div class="col-span-2">
            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama</label>
            <input type="text" disabled value="{{ $product->name }}" name="name" id="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Masukan nama" required="">
          </div>
          <div class="col-span-1 sm:col-span-1">
            <label for="kode" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Kode</label>
            <input type="text" disabled value="{{ $product->productCode }}" name="kode" id="kode" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Kode" required="">
          </div>
          <div class="col-span-1 sm:col-span-1">
            <label for="minStok" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Min. Stok</label>
            <input type="number" disabled value="{{ $product->minStok }}" min="0" name="minStok" id="minStok" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Min. stok" required="">
          </div>
          <div class="col-span-2 sm:col-span-1">
            <label for="initialStok" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Stok</label>
            <input type="number" disabled value="{{ $product->totalStok }}" min="0" name="initialStok" id="initialStok" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Stok" required="">
          </div>
          <div class="col-span-2 sm:col-span-1">
            <label for="price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Harga Jual</label>
            <input type="number" disabled value="{{ $product->productPrices()->orderBy('created_at', 'desc')->first()->sellPrice }}" min="0" name="price" id="price" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Harga" required="">
          </div>
          <div class="col-span-2">
            <label for="priceHistory" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Harga Jual terakhir</label>
            <livewire:product.product-table-price :id="$product->id" />
          </div>
          <div class="col-span-2">
            <label for="priceBuy" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Harga Beli terakhir</label>
            <livewire:product.product-table-buy :id="$product->id" />
          </div>
        </div>
      </div>
    </div>
  </div>
</x-layout>