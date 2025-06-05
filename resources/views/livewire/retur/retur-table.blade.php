<div>
    <section class="bg-gray-50 dark:bg-gray-900">
        <div class="mx-auto max-w-screen-xl">
            @if (session('success'))
            <div id="alert-border-3"
                class="flex items-center p-4 mb-4 text-green-800 border-t-4 border-green-300 bg-green-50 dark:text-green-400 dark:bg-gray-800 dark:border-green-800"
                role="alert">
                <svg class="shrink-0 w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                </svg>
                <div class="ms-3 text-sm font-medium">
                    <span class="font-medium">Informasi!</span> {{ session('success') }}
                </div>
                <button type="button"
                    class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700"
                    data-dismiss-target="#alert-border-3" aria-label="Close">
                    <span class="sr-only">Dismiss</span>
                    <svg class="w-3 h-3" aria-hidden="true" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                </button>
            </div>
            @endif

            @if (session('error'))
            <div id="alert-border-2"
                class="flex absolute w-full items-center p-4 mb-4 text-red-800 border-t-4 border-red-300 bg-red-50 dark:text-red-400 dark:bg-gray-800 dark:border-red-800"
                role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                </svg>
                <div class="ms-3 text-sm font-medium">
                    {{ session('error') }}
                </div>
                <button type="button"
                    class="ms-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700"
                    data-dismiss-target="#alert-border-2" aria-label="Close">
                    <span class="sr-only">Dismiss</span>
                    <svg class="w-3 h-3" aria-hidden="true" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                </button>
            </div>
            @endif

            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                <div class="flex flex-col lg:flex-row items-center justify-between space-y-3 xl:space-y-0 xl:space-x-4 p-4">
                    <div class="w-full flex flex-col space-y-4 lg:space-y-0">
                        <!-- Top Row: Search + Per Page -->
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                            <div class="relative w-full lg:max-w-sm">
                                <label for="simple-search" class="sr-only">Search</label>
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input wire:model.live.debounce.300ms="search" type="text" id="simple-search"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    placeholder="Search" required>
                            </div>

                            <div class="flex items-center space-x-2">
                                <label class="text-sm font-medium text-gray-900 whitespace-nowrap dark:text-white">Per Page</label>
                                <select wire:model.live="perPage"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 w-24 md:w-auto dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                    <option selected value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>

                        <!-- Bottom Row: Filters + Date Range -->
                        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between space-y-4 xl:space-y-0 mt-4">
                            <!-- Type Filter Buttons -->
                            <div class="flex justify-center xl:justify-start">
                                <div class="inline-flex rounded-md shadow-sm overflow-hidden border border-gray-300 dark:border-gray-600">
                                    <button wire:click="setFilter('all')"
                                        class="px-4 py-2 text-sm font-medium focus:outline-none transition-all {{ $filterType === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                                        All Retur
                                    </button>
                                    <button wire:click="setFilter('customer')"
                                        class="px-4 py-2 text-sm font-medium border-l border-gray-300 dark:border-gray-600 focus:outline-none transition-all {{ $filterType === 'customer' ? 'bg-blue-600 text-white' : 'bg-white text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                                        Customer
                                    </button>
                                    <button wire:click="setFilter('supplier')"
                                        class="px-4 py-2 text-sm font-medium border-l border-gray-300 dark:border-gray-600 focus:outline-none transition-all {{ $filterType === 'supplier' ? 'bg-blue-600 text-white' : 'bg-white text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                                        Supplier
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row items-center justify-start sm:space-x-4 space-y-2 sm:space-y-0">
                                {{-- Start Date --}}
                                <div class="relative w-full sm:w-auto">
                                    <label for="startDate"
                                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                                    <input type="date" id="startDate" wire:model.lazy="startDate"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>

                                <span class="text-gray-500 dark:text-gray-300">s.d.</span>

                                {{-- End Date --}}
                                <div class="relative w-full sm:w-auto">
                                    <label for="endDate"
                                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                                    <input type="date" id="endDate" wire:model.lazy="endDate"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3">#</th>
                                <th scope="col" class="px-4 py-3" wire:click="setSortBy('id')">ID</th>
                                <th scope="col" class="px-4 py-3" wire:click="setSortBy('return_date')">Tanggal</th>
                                <th scope="col" class="px-4 py-3">Tipe</th>
                                <th scope="col" class="px-4 py-3">Relasi</th>
                                <th scope="col" class="px-4 py-3">Total Item</th>
                                <th scope="col" class="px-4 py-3">Pembuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($returs as $index => $retur)
                            <tr wire:key="{{ $retur->id }}" class="border-b dark:border-gray-700">
                                <th scope="row" class="px-4 py-3">{{ $returs->firstItem() + $index }}</th>
                                <td class="px-4 py-3">{{ $retur->id }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $retur->return_date->format('d-m-Y H:i') }}
                                </td>
                                <td class="px-4 py-3 capitalize">{{ $retur->return_type }}</td>
                                <td class="px-4 py-3">
                                    @if ($retur->transaction_id)
                                    <a href="{{ route('transactions.show', $retur->transaction_id) }}"
                                        class="text-blue-600 hover:underline">
                                        Transaksi #{{ $retur->transaction_id }}
                                    </a>
                                    @elseif ($retur->purchase_id)
                                    <a href="{{ route('purchases.show', $retur->purchase_id) }}"
                                        class="text-blue-600 hover:underline">
                                        Pembelian #{{ $retur->purchase_id }}
                                    </a>
                                    @else
                                    -
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $retur->items->sum('qty') }} item</td>
                                <td class="px-4 py-3">{{ $retur->user->name ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="py-4 px-3">
                    {{ $returs->links() }}
                </div>
            </div>
        </div>
    </section>
</div>