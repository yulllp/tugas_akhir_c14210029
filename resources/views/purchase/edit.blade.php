<x-layout>
  <x-header :href="route('purchases.show', $purchase->id)" title="{{ $title }}" />
  <div class="mx-auto max-w-screen-xl">
    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
      <form action="{{ route('purchases.update', $purchase->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="p-4 md:p-5 space-y-4">
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Faktur Pembelian
              </label>
              <input
                type="text"
                id="fraktur"
                name="fraktur"
                value="{{ old('fraktur', $purchase->faktur) }}"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                       dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                required />
              @error('fraktur')
              <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
              @enderror
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Tanggal Pembelian
              </label>
              <input
                type="text"
                disabled
                value="{{ \Carbon\Carbon::parse($purchase->buyDate)->format('d-m-Y H:i') }}"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                       dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Total Bayar
              </label>
              <input
                type="text"
                disabled
                value="Rp {{ number_format($purchase->total, 0, ',', '.') }}"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                       dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Dibayar
              </label>
              <input
                type="text"
                disabled
                value="Rp {{ number_format($purchase->prePaid, 0, ',', '.') }}"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                       dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Status Kredit
              </label>
              <input
                type="text"
                disabled
                value="{{ ucfirst($purchase->status) }}"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                       dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
            </div>
            <div>
              <label for="shipping" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Status Pengiriman
              </label>
              <select
                name="shipping"
                id="shipping"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5
                       dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option
                  value="pending"
                  {{ old('shipping', $purchase->shipping) == 'pending' ? 'selected' : '' }}
                  @if($purchase->shipping === 'arrive') disabled @endif
                  >
                  Pending
                </option>
                <option
                  value="arrive"
                  {{ old('shipping', $purchase->shipping) == 'arrive' ? 'selected' : '' }}>
                  Arrive
                </option>
              </select>
              @error('shipping')
              <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
              @enderror
              @if($purchase->shipping === 'arrive')
              <p class="text-xs text-gray-500 mt-1">
                Barang sudah tiba – status tidak dapat dikembalikan ke “pending”.
              </p>
              @endif
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Tanggal Masuk
              </label>
              <input
                type="text"
                disabled
                value="{{ $purchase->entryDate
                  ? \Carbon\Carbon::parse($purchase->entryDate)->format('d-m-Y H:i')
                  : 'Belum tiba' }}"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                       dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
            </div>
            <div class="md:col-span-2">
              <label for="supplier" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Supplier
              </label>
              <input
                type="text"
                id="supplier"
                name="supplier"
                autocomplete="off"
                value="{{ old('supplier', $purchase->supplier->name ?? '') }}"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                       focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5
                       dark:bg-gray-600 dark:border-gray-500 dark:text-white"
                required>
              <input
                type="hidden"
                id="selected-supplier-id"
                name="supplier_id"
                value="{{ old('supplier_id', $purchase->supplier_id) }}">
              @error('supplier_id')
              <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
              @enderror
            </div>
          </div>

          {{-- detail items table --}}
          <div class="overflow-x-auto mt-6">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
              <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                  <th class="px-4 py-3">Produk</th>
                  <th class="px-4 py-3">Harga Beli</th>
                  <th class="px-4 py-3">Qty</th>
                  <th class="px-4 py-3">Subtotal</th>
                  <th class="px-4 py-3">Exp Date</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($purchase->productPurchase as $item)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                  <td class="px-4 py-3">{{ $item->product->name }}</td>
                  <td class="px-4 py-3">Rp {{ number_format($item->buyPrice, 0, ',', '.') }}</td>
                  <td class="px-4 py-3">{{ $item->qty }}</td>
                  <td class="px-4 py-3">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                  <td class="px-4 py-3">
                    {{ $item->expDate
                        ? \Carbon\Carbon::parse($item->expDate)->format('d-m-Y')
                        : '-' }}
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="5" class="text-center px-4 py-3">Tidak ada item.</td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="flex justify-end space-x-3">
            <a href="{{ route('purchases.show', $purchase) }}"
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
              Batal
            </a>
            <button type="submit"
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
              Simpan
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</x-layout>

<script>
  const suppliers = JSON.parse('{!! json_encode($suppliers) !!}'); // passed from controller
  const supplierNames = suppliers.map(supplier => ({
    label: supplier.name,
    value: supplier.name,
    id: supplier.id
  }));

  $(function() {
    $("#supplier").autocomplete({
      source: supplierNames,
      select: function(event, ui) {
        $('#supplier').val(ui.item.value);
        $('#selected-supplier-id').val(ui.item.id);
        return false;
      },
      change: function(event, ui) {
        if (!ui.item) {
          $('#supplier').val('');
          $('#selected-supplier-id').val('');
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