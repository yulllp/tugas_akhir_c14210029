<x-layout>
  <x-header :href="route('opnames.index')" title="Input Detail Stok Opname #{{ $schedule->id }}" />

  <div class="mx-auto max-w-screen-xl">
    @if (session('error'))
    <div id="alert-border-2" class="flex absolute w-full items-center p-4 mb-4 text-red-800 border-t-4 border-red-300 bg-red-50" role="alert">
      <svg class="flex-shrink-0 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path d="..." />
      </svg>
      <div class="ms-3 text-sm font-medium">{{ session('error') }}</div>
      <button type="button" class="ms-auto bg-red-50 text-red-500 hover:bg-red-200 rounded-lg p-1.5" data-dismiss-target="#alert-border-2">
        <svg class="w-3 h-3" fill="none" viewBox="0 0 14 14">
          <path d="..." />
        </svg>
      </button>
    </div>
    @endif

    <div class="bg-white shadow-md rounded-lg overflow-hidden dark:bg-gray-800">
      <form method="POST" action="{{ route('opnames.storeDetail', $schedule->id) }}">
        @csrf

        <!-- Schedule Info -->
        <div class="grid md:grid-cols-3 gap-4 p-4">
          {{-- Date & Time --}}
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-white">Tanggal & Waktu</label>
            <input
              type="text"
              value="{{ $schedule->date->format('d-m-Y H:i') }}"
              disabled
              class="bg-gray-100 text-sm w-full rounded border border-gray-300 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
          </div>

          {{-- Finish At --}}
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-white">Selesai</label>
            <input
              type="text"
              value="{{ $schedule->finish_at ? $schedule->finish_at->format('d-m-Y H:i') : '-' }}"
              disabled
              class="bg-gray-100 text-sm w-full rounded border border-gray-300 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
          </div>

          {{-- Catatan --}}
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-white">Catatan</label>
            <textarea
              name="note"
              disabled
              class="bg-gray-50 text-sm w-full rounded border border-gray-300 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ $schedule->description }}</textarea>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto px-4">
          <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3">Kode</th>
                <th class="px-4 py-3">Nama Produk</th>
                <th class="px-4 py-3 text-center">Stok Sistem</th>
                <th class="px-4 py-3 text-center">Stok Fisik</th>
                <th class="px-4 py-3 text-center">Selisih</th>
                <th class="px-4 py-3 text-center">Keterangan</th>
              </tr>
            </thead>
            <tbody>
              @if ($schedule->status === 'checked')
              @foreach ($details as $detail)
              <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                <td class="px-4 py-3">{{ $detail->product->productCode }}</td>
                <td class="px-4 py-3">{{ $detail->product->name }}</td>
                <td class="px-4 py-3 text-center">{{ $detail->stok_sistem }}</td>
                <td class="px-4 py-3 text-center">{{ $detail->stok_fisik }}</td>
                <td class="px-4 py-3 text-center">{{ $detail->difference }}</td>
                <td class="px-4 py-3">{{ $detail->description ?? '-' }}</td>
              </tr>
              @endforeach
              @else
              @foreach ($products as $product)
              <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                <td class="px-4 py-3">{{ $product->productCode }}</td>
                <td class="px-4 py-3">{{ $product->name }}</td>
                <td class="px-4 py-3 text-center">{{ $product->totalStok }}</td>

                <td class="px-4 py-3 text-center">
                  <input
                    type="number"
                    name="items[{{ $product->id }}][actual_stock]"
                    value="{{ $product->totalStok }}"
                    class="actualStock w-24 text-center text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded px-2 py-1">
                </td>

                <td class="px-4 py-3 text-center selisih">0</td>

                <td class="px-4 py-3">
                  <input
                    type="text"
                    name="items[{{ $product->id }}][description]"
                    class="w-full text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded px-2 py-1"
                    placeholder="Opsional">
                </td>

                <input type="hidden" name="items[{{ $product->id }}][product_id]" value="{{ $product->id }}">
                <input type="hidden" class="systemStock" value="{{ $product->totalStok }}">
              </tr>
              @endforeach
              @endif
            </tbody>
          </table>
        </div>

        @if ($schedule->status !== 'checked')
        <div class="px-4 mt-4 mb-6 flex justify-end gap-2">
          <button
            type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm transition duration-200">
            Simpan
          </button>
        </div>
        @else
        <div class="flex justify-end">
          <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-blue-800 bg-blue-100 rounded-full dark:bg-blue-900 dark:text-blue-300 m-3">
            Stok opname ini sudah diperiksa dan tidak dapat diubah.
          </span>
        </div>
        @endif
      </form>
    </div>
  </div>
</x-layout>

@if ($schedule->status !== 'checked')
<script>
  document.addEventListener('input', function() {
    document.querySelectorAll('tr').forEach(function(row) {
      const actualInput = row.querySelector('.actualStock');
      const systemStock = row.querySelector('.systemStock');
      const selisihTd = row.querySelector('.selisih');

      if (actualInput && systemStock && selisihTd) {
        const actual = parseInt(actualInput.value) || 0;
        const system = parseInt(systemStock.value) || 0;
        selisihTd.textContent = actual - system;
      }
    });
  });
</script>
@endif