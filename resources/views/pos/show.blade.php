<x-layout>
  <x-header :href="route('transactions.index')" :title="$title" />

  @php
  $totalReturNominal = $transaction
  ->returs
  ->flatMap(fn($r) => $r->items)
  ->sum('subtotal');

  $netTotal = $transaction->total - $totalReturNominal;

  $prePaid = $transaction->prePaid;
  $creditPaidSoFar = $transaction->creditPayment->sum('payment_total');
  $alreadyPaid = $prePaid + $creditPaidSoFar;

  $refundTotal = $transaction
  ->returs
  ->sum('refund_amount');

  $remaining = $netTotal - $alreadyPaid;
  if ($remaining < 0) {
      $remaining = 0;
  }
  
  $percent = $netTotal > 0
  ? round($alreadyPaid / $netTotal * 100, 1)
  : 0;
  @endphp

  <div class="mx-auto max-w-screen-xl">
    @if (session('success'))
    <div id="alert-border-3" class="flex items-center p-4 mb-4 text-green-800 border-t-4 border-green-300 bg-green-50 dark:text-green-400 dark:bg-gray-800 dark:border-green-800" role="alert">
      <svg class="shrink-0 w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Z" />
      </svg>
      <div class="ms-3 text-sm font-medium">
        <span class="font-medium">Informasi!</span> {{ session('success') }}
      </div>
      <button type="button" class="ms-auto ... p-1.5" data-dismiss-target="#alert-border-3" aria-label="Close">
        <svg class="w-3 h-3" fill="none" viewBox="0 0 14 14">
          <path stroke="currentColor" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
        </svg>
      </button>
    </div>
    @endif
    <div class="my-2">
      <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ringkasan Pembayaran</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

          {{-- Sudah Dibayar --}}
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sudah Dibayar</p>
            <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
              Rp {{ number_format($alreadyPaid, 0, ',', '.') }}
            </p>
          </div>

          {{-- Total Refund --}}
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Refund</p>
            <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
              Rp {{ number_format($refundTotal, 0, ',', '.') }}
            </p>
          </div>

          {{-- Sisa Tagihan --}}
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sisa Tagihan</p>
            <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
              Rp {{ number_format($remaining, 0, ',', '.') }}
            </p>
          </div>

          {{-- Total Bersih --}}
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Bersih</p>
            <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">
              Rp {{ number_format($netTotal, 0, ',', '.') }}
            </p>
          </div>
        </div>
      </div>
    </div>
    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
      @if(Auth::check() && Auth::user()->role === 'owner')
      <div class="flex justify-end px-4 pt-4 space-x-5">
        <a href="{{ route('returs.create.transaction', $transaction) }}" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-3 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
          Retur barang
        </a>
        @if ($transaction->status == 'unpaid')
        <button type="button"
          data-modal-target="pay-modal"
          data-modal-toggle="pay-modal"
          class="px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg
                hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600
                dark:hover:bg-blue-700 dark:focus:ring-blue-800">
          Bayar Cicilan
        </button>
        @endif
        <a href="{{ route('transactions.edit', $transaction->id) }}" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-3 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="shrink-0 w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
          </svg>
        </a>
      </div>
      @endif
      <div class="p-4 md:p-5 space-y-4">
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tanggal Penjualan</label>
            <input type="text" disabled value="{{ $transaction->transaction_at ? \Carbon\Carbon::parse($transaction->transaction_at)->format('d-m-Y H:i') : '-' }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Total Bayar</label>
            <input type="text" disabled value="Rp {{ number_format($transaction->total, 0, ',', '.') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Dibayar</label>
            <input type="text" disabled value="Rp {{ number_format($transaction->prePaid, 0, ',', '.') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Kredit?</label>
            <input type="text" disabled value="{{ $transaction->status ? 'Ya' : 'Tidak' }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
          </div>
          <div class="md:col-span-2">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama Pembeli</label>
            <input type="text" disabled value="{{ $transaction->customer->name ?? '-' }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white" />
          </div>
          <div class="overflow-x-auto col-span-2">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
              <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                  <th scope="col" class="px-4 py-3">Produk</th>
                  <th scope="col" class="px-4 py-3">Harga</th>
                  <th scope="col" class="px-4 py-3">Qty</th>
                  <th scope="col" class="px-4 py-3">Diskon</th>
                  <th scope="col" class="px-4 py-3">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($transaction->detailTransactions as $detail)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                  <td class="px-4 py-3">{{ $detail->product->name }}</td>
                  <td class="px-4 py-3">Rp {{ number_format($detail->productPrice->sellPrice, 0, ',', '.') }}</td>
                  <td class="px-4 py-3">{{ $detail->qty }}</td>
                  <td class="px-4 py-3">Rp {{ number_format($detail->discount, 0, ',', '.') }}</td>
                  <td class="px-4 py-3">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                  <td colspan="5" class="text-center px-4 py-3">Tidak ada detail penjualan.</td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-6">
      <h2 class="text-sm font-semibold mb-2 text-gray-900 dark:text-white">Data Retur</h2>
      <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
        <div class="p-4 md:p-5 space-y-4">
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
              <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                  <th class="px-4 py-3">Tanggal Retur</th>
                  <th class="px-4 py-3">Produk</th>
                  <th class="px-4 py-3">Harga</th>
                  <th class="px-4 py-3">Qty</th>
                  <th class="px-4 py-3">Diskon</th>
                  <th class="px-4 py-3">Deskripsi</th>
                  <th class="px-4 py-3">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                @php $totalReturNominal = 0; @endphp
                @foreach ($transaction->returs as $retur)
                @foreach ($retur->items as $item)
                @php $totalReturNominal += $item->subtotal; @endphp
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                  <td class="px-4 py-3">{{ \Carbon\Carbon::parse($retur->retur_at)->format('d-m-Y H:i') }}</td>
                  <td class="px-4 py-3">{{ $item->product->name }}</td>
                  <td class="px-4 py-3">Rp {{ number_format($item->productPrice->sellPrice, 0, ',', '.') }}</td>
                  <td class="px-4 py-3">{{ $item->qty }}</td>
                  <td class="px-4 py-3">Rp {{ number_format($item->discount, 0, ',', '.') }}</td>
                  <td class="px-4 py-3">{{ $item->note ?? '-' }}</td>
                  <td class="px-4 py-3">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
                @endforeach
              </tbody>
              <tfoot class="bg-gray-100 dark:bg-gray-700">
                <tr>
                  <td colspan="6" class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Total Retur</td>
                  <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">Rp {{ number_format($totalReturNominal, 0, ',', '.') }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-4">
      <h3 class="text-sm font-semibold mb-2 text-gray-900 dark:text-white">Ringkasan Refund Retur</h3>
      <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
        <div class="p-4 md:p-5 space-y-4">
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
              <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                  <th class="px-4 py-3">Retur ID</th>
                  <th class="px-4 py-3">Tanggal Retur</th>
                  <th class="px-4 py-3">Nominal Refund</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($transaction->returs as $retur)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                  <td class="px-4 py-3">{{ $retur->id }}</td>
                  <td class="px-4 py-3">
                    {{ \Carbon\Carbon::parse($retur->retur_at)->format('d-m-Y H:i') }}
                  </td>
                  <td class="px-4 py-3">
                    Rp {{ number_format($retur->refund_amount, 0, ',', '.') }}
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="3" class="text-center px-4 py-3">Belum ada retur.</td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="mt-4">
    <label for="priceHistory" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Data Utang</label>
    <livewire:customer.credit.credit-table :id="$transaction->id">
  </div>
  </div>
</x-layout>

<div
  id="pay-modal"
  data-modal-backdrop="static"
  class="fixed inset-0 z-50 hidden flex items-center justify-center overflow-y-auto overflow-x-hidden bg-black/50">
  <div class="relative w-full max-w-lg p-4 h-full md:h-auto">
    <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
      <!-- Header -->
      <div class="p-4 border-b rounded-t dark:border-gray-600 flex justify-between items-center">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Pembayaran Penjualan #{{ $transaction->id }}
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Dibayar: <span class="font-medium">{{ number_format($alreadyPaid, 0, ',', '.') }}</span> /
            {{ number_format($netTotal, 0, ',', '.') }} ({{ $percent }}%)
          </p>
        </div>
        <button
          type="button"
          class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg w-8 h-8 inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-hide="pay-modal">
          <svg
            class="w-3 h-3"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 14 14"
            stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
          </svg>
          <span class="sr-only">Close modal</span>
        </button>
      </div>

      <form
        action="{{ route('credit-payments.store', $transaction) }}"
        method="POST"
        class="p-4 space-y-4">
        @csrf
        <input type="hidden" name="transaction_id" value="{{ $transaction->id }}" />

        <div>
          <label
            for="payDate"
            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Tanggal Bayar
          </label>
          <input
            type="datetime-local"
            id="payDate"
            name="payDate"
            required
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
        </div>

        <div>
          <label
            for="payment_total"
            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Nominal Pembayaran (maks {{ number_format($remaining, 0, ',', '.') }})
          </label>
          <input
            type="text"
            id="payment_total"
            name="payment_total"
            min="1"
            max="{{ $remaining }}"
            step="1"
            required
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
        </div>

        <div>
          <label
            for="description"
            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Deskripsi (opsional)
          </label>
          <textarea
            id="description"
            name="description"
            rows="3"
            class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"></textarea>
        </div>

        <button
          type="submit"
          class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
          Simpan Pembayaran
        </button>
      </form>
    </div>
  </div>
</div>

<script>
  const payInput = document.getElementById('payment_total');
  const maxAmount = parseInt(payInput.max, 10); // {{ $remaining }}
  const formatter = new Intl.NumberFormat('id-ID');
  let rawValue = '';

  // Live formatting + clamp to max
  payInput.addEventListener('input', (e) => {
    rawValue = e.target.value.replace(/\D/g, '');
    if (rawValue !== '' && parseInt(rawValue, 10) > maxAmount) {
      rawValue = maxAmount.toString();
      alert(`Nominal melebihi batas maksimal (${formatter.format(maxAmount)})`);
    }
    e.target.value = rawValue === '' ? '' : formatter.format(rawValue);
  });

  payInput.form.addEventListener('submit', (e) => {
    const amount = parseInt(rawValue || '0', 10);
    if (!amount) {
      e.preventDefault();
      alert('Nominal pembayaran tidak boleh kosong.');
    } else if (amount > maxAmount) {
      e.preventDefault();
      alert(`Nominal melebihi batas maksimal (${formatter.format(maxAmount)}).`);
    } else {
      payInput.value = amount;
    }
  });
</script>