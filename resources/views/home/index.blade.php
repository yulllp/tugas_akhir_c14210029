{{-- resources/views/home.blade.php --}}
<x-layout>
  {{-- Page Title --}}
  <h2 class="mx-auto max-w-screen-xl mb-8 text-2xl font-extrabold leading-none tracking-tight text-gray-900 md:text-3xl dark:text-white">
    Beranda
  </h2>

  {{-- Main Content --}}
  <div class="text-gray-900 dark:text-gray-100">
    <div class="container mx-auto max-w-screen-xl">
      {{-- Welcome Banner --}}
      <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-semibold">
          Selamat Datang, {{ Auth::user()->name ?? 'User' }}
        </h1>
      </div>

      {{-- Three Metrics --}}
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 lg:gap-6 mb-8">
        {{-- Barang Terjual Hari Ini --}}
        <div class="p-4 sm:p-6 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition">
          <h2 class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">
            Barang Terjual Hari Ini
          </h2>
          <p class="mt-2 text-xl sm:text-2xl font-bold">
            {{ number_format($itemsSoldToday, 0, ',', '.') }}
          </p>
        </div>

        {{-- Pendapatan Hari Ini --}}
        <div class="p-4 sm:p-6 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition">
          <h2 class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">
            Pendapatan Hari Ini
          </h2>
          <p class="mt-2 text-xl sm:text-2xl font-bold">
            Rp {{ number_format($revenueToday, 0, ',', '.') }}
          </p>
        </div>

        {{-- Total Customer --}}
        <div class="p-4 sm:p-6 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition">
          <h2 class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">
            Total Customer
          </h2>
          <p class="mt-2 text-xl sm:text-2xl font-bold">
            {{ number_format($totalCustomers, 0, ',', '.') }}
          </p>
        </div>
      </div>

      {{-- Top 5 Best‐Selling Products Table --}}
      <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow mb-8">
        {{-- Table Header --}}
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-base sm:text-lg font-semibold text-gray-700 dark:text-gray-200">
            Daftar Barang Terlaris (Hari Ini)
          </h3>
        </div>

        {{-- Table Body --}}
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                No.
              </th>
              <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Nama Produk
              </th>
              <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Terjual
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @if($topProducts->isEmpty())
            <tr>
              <td colspan="3" class="px-4 sm:px-6 py-3 text-center text-gray-500 dark:text-gray-400">
                Tidak ada data hari ini.
              </td>
            </tr>
            @else
            @foreach($topProducts as $index => $prod)
            <tr>
              <td class="px-4 sm:px-6 py-2 sm:py-3 whitespace-nowrap text-sm sm:text-base text-gray-700 dark:text-gray-200">
                {{ $index + 1 }}
              </td>
              <td class="px-4 sm:px-6 py-2 sm:py-3 whitespace-nowrap text-sm sm:text-base text-gray-700 dark:text-gray-200">
                {{ $prod->product_name }}
              </td>
              <td class="px-4 sm:px-6 py-2 sm:py-3 whitespace-nowrap text-sm sm:text-base font-semibold text-gray-900 dark:text-gray-100">
                {{ number_format($prod->total_sold, 0, ',', '.') }}
              </td>
            </tr>
            @endforeach
            @endif
          </tbody>
        </table>

        <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gray-50 dark:bg-gray-700 text-right">
          <a href="{{ route('reports.stockIndex') }}" class="text-sm sm:text-base text-blue-600 dark:text-blue-400 hover:underline">
            Lihat Lebih…
          </a>
        </div>
      </div>
    </div>
  </div>
</x-layout>