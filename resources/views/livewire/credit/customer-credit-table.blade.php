<div>
    <section class="bg-gray-50 dark:bg-gray-900">
        <div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between space-y-4 md:space-y-0">
                <div>
                    <label for="customerSelect" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                        Pilih Pelanggan
                    </label>
                    <select wire:model="customer_id"
                        id="customerSelect"
                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 w-full md:w-64 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        <option value="">— Semua Pelanggan —</option>
                        @foreach($customers as $cust)
                        <option value="{{ $cust->id }}">{{ $cust->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="text-gray-800 dark:text-gray-200 text-lg font-semibold">
                    @if($customer_id)
                    Total Kredit: <span class="text-blue-600 dark:text-blue-400">
                        Rp {{ number_format($this->totalCredit, 0, ',', '.') }}
                    </span>
                    @else
                    <span class="italic text-gray-500 dark:text-gray-400">Pilih pelanggan di atas untuk melihat total kredit.</span>
                    @endif
                </div>
            </div>
            <div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
                <div class="w-full md:w-1/3">
                    <label for="searchInput" class="sr-only">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817
                         a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input wire:model.debounce.300ms="search"
                            type="text"
                            id="searchInput"
                            class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg
                          focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5
                          dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                            placeholder="Cari ID transaksi atau nama pelanggan" />
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-900 dark:text-white">Per Halaman</label>
                    <select wire:model="perPage"
                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg
                         focus:ring-blue-500 focus:border-blue-500 block w-20 p-2.5
                         dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-md sm:rounded-lg overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th wire:click="setSortBy('id')" class="px-4 py-3 cursor-pointer">
                                Credit ID
                            </th>
                            <th wire:click="setSortBy('id')" class="px-4 py-3 cursor-pointer">
                                Transaction ID
                            </th>
                            <th wire:click="setSortBy('transaction_at')" class="px-4 py-3 cursor-pointer">
                                Progress
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $index => $trx)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700" wire:key="{{ $trx->id }}">
                            {{-- 1) Index # (accounting for pagination) --}}
                            <td class="px-4 py-3">
                                {{ $transactions->firstItem() + $index }}
                            </td>

                            {{-- 2) Credit ID = transaction->id (you can rename if needed) --}}
                            <td class="px-4 py-3">
                                {{ $trx->id }}
                            </td>

                            {{-- 3) Transaction ID (linked to show page) --}}
                            <td class="px-4 py-3">
                                <a href="{{ route('transactions.show', $trx->id) }}"
                                    class="text-blue-600 hover:underline">
                                    #{{ $trx->id }}
                                </a>
                            </td>

                            {{-- 4) Progress = percent paid --}}
                            <td class="px-4 py-3">
                                @php
                                $percent = $trx->total > 0
                                ? round(($trx->prePaid / $trx->total) * 100, 1)
                                : 0;
                                @endphp
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mr-2">
                                        <div class="bg-blue-600 h-2 rounded-full"
                                            style="width: {{ $percent }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $percent }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center px-4 py-6 text-gray-500 dark:text-gray-400">
                                Tidak ada transaksi kredit (unpaid) untuk pelanggan ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 4) Pagination --}}
            <div class="py-4 px-3">
                {{ $transactions->links() }}
            </div>
        </div>
    </section>
</div>