<div>
    <section class="bg-gray-50 dark:bg-gray-900">
        <div class="mx-auto max-w-screen-xl space-y-6">

            <!-- Summary Card (unchanged) -->
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Ringkasan Kredit
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-300">Total Tagihan</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($summaryTotals['totalTagihan'], 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-300">Sudah Dibayar</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($summaryTotals['totalPaid'], 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-300">Total Refund</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($summaryTotals['totalRefund'], 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-300">Total Sisa Utang</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($summaryTotals['totalRemaining'], 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Combined Filter + Table Card -->
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg px-6 pt-6 space-y-6">
                <div class="space-y-4">
                    <div class="flex flex-col space-y-4">
                        <div class="w-full md:w-1/3">
                            <label for="customerSelect" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Pilih Pelanggan
                            </label>
                            <select
                                wire:model.live="customer_id"
                                id="customerSelect"
                                class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg
                                       focus:ring-blue-500 focus:border-blue-500 w-full p-2.5
                                       dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                <option value="">— Semua Pelanggan —</option>
                                @foreach($customers as $cust)
                                <option value="{{ $cust->id }}">{{ $cust->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between space-y-2 md:space-y-0 md:space-x-4 w-full">
                            <!-- Search Input -->
                            <div class="w-full md:w-1/3">
                                <label for="searchInput" class="sr-only">Search</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg
                                            aria-hidden="true"
                                            class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                            fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817
                           a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <input
                                        wire:model.live.debounce.300ms="search"
                                        type="text"
                                        id="searchInput"
                                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg
                       focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5
                       dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                        placeholder="Cari ID transaksi atau nama pelanggan" />
                                </div>
                            </div>

                            <!-- Per Page Selector -->
                            <div class="flex flex-col md:flex-row items-center space-y-2 md:space-y-0 md:space-x-4 w-full md:w-auto">
                                <label class="text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                    Per Page
                                </label>
                                <select
                                    wire:model.live="perPage"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 w-24 md:w-auto dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    <option selected value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-400 uppercase">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th wire:click="setSortBy('id')" class="px-4 py-3 cursor-pointer">
                                    ID
                                </th>
                                <th wire:click="setSortBy('id')" class="px-4 py-3 cursor-pointer">
                                    Transaksi ID
                                </th>
                                <th class="px-4 py-3">Pelanggan</th>
                                <th class="px-4 py-3">Total Tagihan</th>
                                <th class="px-4 py-3">Sudah Dibayar</th>
                                <th class="px-4 py-3">Sisa</th>
                                <th class="px-4 py-3">Refund</th>
                                <th class="px-4 py-3">Persentase (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $index => $trx)
                            @php
                            // 1) Hitung total Retur nominal
                            $totalReturNominal = $trx
                            ->returs
                            ->flatMap(fn($r) => $r->items)
                            ->sum('subtotal');

                            // 2) Net total setelah retur
                            $netTotal = $trx->total - $totalReturNominal;
                            if ($netTotal < 0) {
                                $netTotal=0;
                                }

                                // 3) PrePaid dan kredit yang sudah dibayar
                                $prePaid=$trx->prePaid;
                                $creditPaidSoFar = $trx->creditPayment->sum('payment_total');
                                $alreadyPaid = $prePaid + $creditPaidSoFar;
                                if ($alreadyPaid < 0) {
                                    $alreadyPaid=0;
                                    }

                                    // 4) Sisa utang
                                    $remaining=$netTotal - $alreadyPaid;
                                    if ($remaining < 0) {
                                    $remaining=0;
                                    }

                                    // 5) Persentase pembayaran
                                    $percent=$netTotal> 0
                                    ? round($alreadyPaid / $netTotal * 100, 1)
                                    : 0;

                                    // 6) Total refund (jumlah subtotal retur)
                                    $refundAmount = $totalReturNominal;
                                    @endphp

                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700" wire:key="{{ $trx->id }}">
                                        <!-- Index # -->
                                        <td class="px-4 py-3">{{ $transactions->firstItem() + $index }}</td>

                                        <!-- Credit ID -->
                                        <td class="px-4 py-3">{{ $trx->id }}</td>

                                        <!-- Transaction ID -->
                                        <td class="px-4 py-3">
                                            <a href="{{ route('transactions.show', $trx->id) }}" class="text-blue-600 hover:underline">
                                                #{{ $trx->id }}
                                            </a>
                                        </td>

                                        <!-- Pelanggan -->
                                        <td class="px-4 py-3">{{ $trx->customer->name ?? '-' }}</td>

                                        <!-- Total Tagihan -->
                                        <td class="px-4 py-3">
                                            Rp {{ number_format($netTotal, 0, ',', '.') }}
                                        </td>

                                        <!-- Sudah Dibayar -->
                                        <td class="px-4 py-3">
                                            Rp {{ number_format($alreadyPaid, 0, ',', '.') }}
                                        </td>

                                        <!-- Sisa -->
                                        <td class="px-4 py-3">
                                            Rp {{ number_format($remaining, 0, ',', '.') }}
                                        </td>

                                        <!-- Refund -->
                                        <td class="px-4 py-3">
                                            Rp {{ number_format($refundAmount, 0, ',', '.') }}
                                        </td>

                                        <!-- Persentase (%) -->
                                        <td class="px-4 py-3">{{ $percent }}%</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center px-4 py-6 text-gray-500 dark:text-gray-400">
                                            Tidak ada transaksi kredit (unpaid) untuk pelanggan ini.
                                        </td>
                                    </tr>
                                    @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4">
                    {{ $transactions->links() }}
                </div>
            </div>

        </div>
    </section>
</div>