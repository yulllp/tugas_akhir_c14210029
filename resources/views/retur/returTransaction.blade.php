<x-layout>
  <x-header :href="route('transactions.show', $transaction->id)" title="Retur Penjualan #{{ $transaction->id }}" />
  <div class="mx-auto max-w-screen-xl">
    @if (session('error'))
    <div id="alert-border-2" class="flex absolute w-full items-center p-4 mb-4 text-red-800 border-t-4 border-red-300 bg-red-50 dark:text-red-400 dark:bg-gray-800 dark:border-red-800" role="alert">
      <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
      </svg>
      <div class="ms-3 text-sm font-medium">
        {{ session('error') }}
      </div>
      <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700" data-dismiss-target="#alert-border-2" aria-label="Close">
        <span class="sr-only">Dismiss</span>
        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
        </svg>
      </button>
    </div>
    @endif
    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
      <form id="returnForm" method="POST" action="{{ route('returs.store.transaction') }}">
        @csrf
        <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">
        <input type="hidden" name="return_type" value="customer">

        <div class="grid md:grid-cols-2 gap-4 p-4 items-center">
          <div x-data="{ currentTime: '' }"
            x-init="
               setInterval(() => {
                 const options = {
                   timeZone: 'Asia/Jayapura',
                   year: 'numeric',
                   month: '2-digit',
                   day: '2-digit',
                   hour: '2-digit',
                   minute: '2-digit',
                   second: '2-digit',
                   hour12: false
                 };
                 currentTime = new Intl.DateTimeFormat('en-GB', options).format(new Date());
               }, 1000);
             "
            class="text-xl text-gray-900 dark:text-white mb-4 px-4">
            <strong>Tanggal:</strong> <span x-text="currentTime"></span>
          </div>
          <div class="flex space-x-3 items-center">
            <label for="description" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Deskripsi: </label>
            <textarea id="description" rows="2" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder=""></textarea>
          </div>
        </div>
        <!-- Items Table -->
        <div class="overflow-x-auto px-4">
          <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
              <tr>
                <th scope="col" class="px-6 py-3">Produk</th>
                <th scope="col" class="px-6 py-3">Qty Beli</th>
                <th scope="col" class="px-6 py-3">Qty Retur</th>
                <th scope="col" class="px-6 py-3">Kondisi</th>
                <th scope="col" class="px-6 py-3">Catatan</th>
                <th scope="col" class="px-6 py-3">Harga Satuan</th>
                <th scope="col" class="px-6 py-3">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($transaction->detailTransactions as $item)
              <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                <td class="px-4 py-3">{{ $item->product->name }}</td>
                <td class="px-4 py-3 text-center">{{ $item->qty }}</td>
                @php
                $alreadyReturned = $item->returnedQty();
                $remainingQty = $item->qty - $alreadyReturned;
                @endphp

                <td class="px-4 py-3 text-center">
                  @if ($remainingQty <= 0)
                    <span class="text-gray-500 italic">Sudah diretur semua</span>
                    @else
                    <input type="number"
                      name="items[{{ $item->id }}][return_quantity]"
                      min="0"
                      max="{{ $remainingQty }}"
                      value="0"
                      class="returQty w-20 text-center text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded px-2 py-1">
                    @endif
                </td>
                @if ($remainingQty <= 0)
                  <td class="px-4 py-3 text-center text-gray-500 italic">—</td>
                  <td class="px-4 py-3 text-center text-gray-500 italic">—</td>
                  @else
                  <td class="px-4 py-3 text-center">
                    <select name="items[{{ $item->id }}][condition]"
                      class="w-full text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded px-2 py-1">
                      <option value="good">Baik</option>
                      <option value="damaged">Rusak</option>
                    </select>
                  </td>
                  <td class="px-4 py-3">
                    <input type="text" name="items[{{ $item->id }}][note]"
                      class="w-full text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded px-2 py-1"
                      placeholder="Opsional">
                  </td>
                  @endif
                  <td class="px-4 py-3 text-right">
                    Rp {{ number_format($item->productPrice->sellPrice) }}
                    @if ($item->discount > 0)
                    <span class="text-xs text-red-500 block">-Rp {{ number_format($item->discount) }}</span>
                    @endif
                  </td>
                  <td class="px-4 py-3 text-right subtotal">Rp 0</td>

                  <!-- Hidden fields -->
                  <input type="hidden" name="items[{{ $item->id }}][product_id]" value="{{ $item->product_id }}">
                  <input type="hidden" name="items[{{ $item->id }}][price]" value="{{ $item->productPrice->sellPrice }}">
                  <input type="hidden" name="items[{{ $item->id }}][disc]" value="{{ $item->discount }}">
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <!-- Total and Actions -->
        <div class="px-4 mt-4 text-right text-gray-800 dark:text-gray-100">
          <strong>Total Retur: Rp <span id="totalRetur">0</span></strong>
        </div>

        <div class="px-4 mt-4 mb-4 flex justify-end gap-2">
          <button type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm transition duration-200">
            Simpan Retur
          </button>
        </div>
      </form>
    </div>
  </div>
</x-layout>

<script>
  function formatRupiah(angka) {
    return angka.toLocaleString('id-ID');
  }

  function updateSubtotals() {
    let total = 0;

    $('.returQty').each(function() {
      const $row = $(this).closest('tr');
      const qty = parseInt($(this).val()) || 0;
      const price = parseInt($row.find('[name$="[price]"]').val()) || 0;
      const disc = parseInt($row.find('[name$="[disc]"]').val()) || 0;

      let subtotal = (qty * price) - (qty * disc);
      if (subtotal < 0) subtotal = 0;

      $row.find('.subtotal').text('Rp ' + formatRupiah(subtotal));
      total += subtotal;
    });

    $('#totalRetur').text(formatRupiah(total));
  }

  $(document).ready(function() {
    $('.returQty').on('input', function() {
      const maxQty = parseInt($(this).attr('max'));
      const current = parseInt($(this).val()) || 0;

      if (current > maxQty) {
        $(this).val(maxQty);
      } else if (current < 0) {
        $(this).val(0);
      }

      updateSubtotals();
    });
  });
</script>