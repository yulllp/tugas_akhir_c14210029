<x-layout>
  <x-header :href="route('transactions.index')" :title="$title" />

  <div class="mx-auto max-w-screen-xl">
    <form
      action="{{ route('transactions.update', $transaction) }}"
      method="POST"
      class="bg-white dark:bg-gray-800 shadow-md sm:rounded-lg p-6 space-y-6">
      @csrf
      @method('PUT')
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label
            for="transaction_at"
            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Tanggal Penjualan
          </label>
          <input
            type="text"
            id="transaction_at"
            disabled
            value="{{ \Carbon\Carbon::parse($transaction->transaction_at)->format('d-m-Y H:i') }}"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                   dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
        </div>
        <div>
          <label
            for="total"
            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Total Bayar
          </label>
          <input
            type="text"
            id="total"
            disabled
            value="Rp {{ number_format($transaction->total, 0, ',', '.') }}"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                   dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
        </div>
        <div>
          <label
            for="prePaid"
            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Dibayar
          </label>
          <input
            type="text"
            id="prePaid"
            disabled
            value="Rp {{ number_format($transaction->prePaid, 0, ',', '.') }}"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                   dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
        </div>
        <div>
          <label
            for="status"
            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Kredit?
          </label>
          <input
            type="text"
            id="status"
            disabled
            value="{{ $transaction->status === 'unpaid' ? 'Ya' : 'Tidak' }}"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                   dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
        </div>
      </div>
      <div>
        <label
          for="customer"
          class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
          Nama Pembeli
        </label>
        <div class="relative">
          <input
            type="text"
            id="customer"
            name="customer"
            value="{{ $transaction->customer->name ?? '' }}"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                   dark:bg-gray-600 dark:border-gray-500 dark:text-white"
            autocomplete="off"
            placeholder="Mulai ketik nama..." />
          {{-- Spinner (hidden by default) --}}
          <div id="customer-loading" class="absolute right-3 top-1/2 -translate-y-1/2 hidden">
            <svg class="w-5 h-5 animate-spin text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
          </div>
        </div>
        <input
          type="hidden"
          id="selected-customer-id"
          name="selected_customer_id"
          value="{{ $transaction->customer->id ?? '' }}" />
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th class="px-4 py-3">Produk</th>
              <th class="px-4 py-3">Harga</th>
              <th class="px-4 py-3">Qty</th>
              <th class="px-4 py-3">Diskon</th>
              <th class="px-4 py-3">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($transaction->detailTransactions as $detail)
            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
              <td class="px-4 py-3">
                {{ $detail->product->name }}
              </td>
              <td class="px-4 py-3">
                Rp {{ number_format($detail->productPrice->sellPrice, 0, ',', '.') }}
              </td>
              <td class="px-4 py-3">
                {{ $detail->qty }}
              </td>
              <td class="px-4 py-3">
                Rp {{ number_format($detail->discount, 0, ',', '.') }}
              </td>
              <td class="px-4 py-3">
                Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="5" class="text-center px-4 py-3">
                Tidak ada detail penjualan.
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="flex justify-end pt-4">
        <button
          type="submit"
          class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300
                 font-medium rounded-lg text-sm px-5 py-2.5 text-center
                 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
          Simpan Perubahan
        </button>
      </div>
    </form>
  </div>

  {{-- jQuery Autocomplete snippet (make sure jQuery UI is loaded) --}}
  <script>
    $(function() {
      $("#customer").autocomplete({
        source: function(request, response) {
          $("#customer-loading").removeClass('hidden');
          $.ajax({
            url: "{{ route('get.customers') }}",
            data: {
              term: request.term
            },
            success: function(data) {
              response(data.map(customer => ({
                label: customer.name,
                value: customer.name,
                id: customer.id
              })));
            },
            complete: function() {
              $("#customer-loading").addClass('hidden');
            }
          });
        },
        select: function(event, ui) {
          $('#customer').val(ui.item.value);
          $('#selected-customer-id').val(ui.item.id);
          return false;
        },
        change: function(event, ui) {
          if (!ui.item) {
            $('#customer').val('');
            $('#selected-customer-id').val('');
          }
        },
        open: function() {
          $('.ui-autocomplete').css({
            'max-height': '6rem',
            'overflow-y': 'auto',
            'overflow-x': 'hidden',
            'z-index': 10000
          });
        }
      });
    });
  </script>
</x-layout>