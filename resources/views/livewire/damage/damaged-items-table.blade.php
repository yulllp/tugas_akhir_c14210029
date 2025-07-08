<div>
    <section class="bg-gray-50 dark:bg-gray-900">
        <div class="mx-auto max-w-screen-xl">
            {{-- Success Alert --}}
            @if (session('success'))
            <div id="success-alert"
                class="flex items-center p-4 mb-4 text-green-800 border-t-4 border-green-300 bg-green-50 dark:text-green-400 dark:bg-gray-800 dark:border-green-800"
                role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                </svg>
                <div class="ms-3 text-sm font-medium">
                    <span class="font-medium">Informasi!</span> {{ session('success') }}
                </div>
            </div>
            @endif

            {{-- Error Alert --}}
            @if (session('error'))
            <div id="error-alert"
                class="flex items-center p-4 mb-4 text-red-800 border-t-4 border-red-300 bg-red-50 dark:text-red-400 dark:bg-gray-800 dark:border-red-800"
                role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                </svg>
                <div class="ms-3 text-sm font-medium">
                    {{ session('error') }}
                </div>
            </div>
            @endif

            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                {{-- Header: Search + Per Page --}}
                <div class="flex flex-col lg:flex-row items-center justify-between space-y-3 xl:space-y-0 xl:space-x-4 p-4">
                    <div class="w-full flex flex-col space-y-4 lg:space-y-0">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                            {{-- Search --}}
                            <div class="relative w-full lg:max-w-sm">
                                <label for="search" class="sr-only">Search</label>
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input wire:model.live.debounce.300ms="search" type="text" id="search"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                    placeholder="Search…" required>
                            </div>

                            {{-- Per Page --}}
                            <div class="flex items-center space-x-2">
                                <label class="text-sm font-medium text-gray-900 whitespace-nowrap dark:text-white">Per Page</label>
                                <div class="relative">
                                    <select wire:model.live="perPage" wire:loading.attr="disabled"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 w-24 md:w-auto dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option selected value="10">10</option>
                                        <option value="15">15</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                    <div wire:loading wire:target="perPage" class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-700 bg-opacity-50 rounded-lg">
                                        <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Filters + Date Range --}}
                        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between space-y-4 xl:space-y-0 mt-4">
                            {{-- Handling Status Buttons --}}
                            <div class="flex justify-center xl:justify-start">
                                <div class="inline-flex rounded-md shadow-sm overflow-hidden border border-gray-300 dark:border-gray-600">
                                    <button wire:click="setFilter('all')" wire:loading.attr="disabled"
                                        class="px-4 py-2 text-sm font-medium focus:outline-none transition-all {{ $filterStatus === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                                        Semua
                                    </button>
                                    <button wire:click="setFilter('belum_ditangani')" wire:loading.attr="disabled"
                                        class="px-4 py-2 text-sm font-medium border-l border-gray-300 dark:border-gray-600 focus:outline-none transition-all {{ $filterStatus === 'belum_ditangani' ? 'bg-blue-600 text-white' : 'bg-white text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                                        Belum Ditangani
                                    </button>
                                    <button wire:click="setFilter('buang')" wire:loading.attr="disabled"
                                        class="px-4 py-2 text-sm font-medium border-l border-gray-300 dark:border-gray-600 focus:outline-none transition-all {{ $filterStatus === 'buang' ? 'bg-blue-600 text-white' : 'bg-white text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                                        Buang
                                    </button>
                                    <button wire:click="setFilter('daur_ulang')" wire:loading.attr="disabled"
                                        class="px-4 py-2 text-sm font-medium border-l border-gray-300 dark:border-gray-600 focus:outline-none transition-all {{ $filterStatus === 'daur_ulang' ? 'bg-blue-600 text-white' : 'bg-white text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                                        Daur Ulang
                                    </button>
                                </div>
                            </div>

                            {{-- Date Range --}}
                            <div class="flex flex-col sm:flex-row items-center justify-start sm:space-x-4 space-y-2 sm:space-y-0">
                                <div class="relative w-full sm:w-auto">
                                    <label for="startDate"
                                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                                    <input type="date" id="startDate" wire:model.lazy="startDate" wire:loading.attr="disabled"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                </div>

                                <span class="text-gray-500 dark:text-gray-300">s.d.</span>

                                <div class="relative w-full sm:w-auto">
                                    <label for="endDate"
                                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                                    <input type="date" id="endDate" wire:model.lazy="endDate" wire:loading.attr="disabled"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3">#</th>
                                <th scope="col" class="px-4 py-3" wire:click="setSortBy('id')">ID</th>
                                <th scope="col" class="px-4 py-3" wire:click="setSortBy('type')">Tipe</th>
                                <th scope="col" class="px-4 py-3" wire:click="setSortBy('related_id')">Relasi</th>
                                <th scope="col" class="px-4 py-3">Produk</th>
                                <th scope="col" class="px-4 py-3">Qty</th>
                                <th scope="col" class="px-4 py-3" wire:click="setSortBy('date')">Tanggal</th>
                                <th scope="col" class="px-4 py-3">Deskripsi</th>
                                <th scope="col" class="px-4 py-3">Handling</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $index => $item)
                            <tr wire:key="{{ $item->id }}" class="border-b dark:border-gray-700">
                                <th scope="row" class="px-4 py-3">{{ $items->firstItem() + $index }}</th>
                                <td class="px-4 py-3">{{ $item->id }}</td>
                                <td class="px-4 py-3 capitalize">{{ $item->type }}</td>
                                <td class="px-4 py-3">
                                    <a href="#" class="text-blue-600 hover:underline">#{{ $item->related_id }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $item->product }}</td>
                                <td class="px-4 py-3">{{ $item->qty }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($item->date)->format('d-m-Y H:i') }}
                                </td>
                                <td class="px-4 py-3">{{ $item->description }}</td>
                                <td class="px-4 py-3">
                                    <div class="relative">
                                        <select
                                            wire:change="updateHandling('{{ $item->source }}', {{ $item->id }}, $event.target.value)"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2 min-w-[10rem] dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                            @if($updatingItemId===$item->id) disabled @endif
                                            >
                                            <option value="belum_ditangani" @selected($item->handling==='belum_ditangani')>Belum Ditangani</option>
                                            <option value="buang" @selected($item->handling==='buang')>Buang</option>
                                            <option value="daur_ulang" @selected($item->handling==='daur_ulang')>Daur Ulang</option>
                                        </select>

                                        @if($updatingItemId === $item->id)
                                        <div class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-700 bg-opacity-50 rounded-lg">
                                            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach

                            @if($items->isEmpty())
                            <tr>
                                <td colspan="8" class="px-4 py-3 text-center">Tidak ada item rusak.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="py-4 px-3">
                    {{ $items->links() }}
                </div>
            </div>
        </div>
    </section>

    <script>
        // Success alert
        const successAlert = document.getElementById('success-alert');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 300);
            }, 3000);
        }

        // Error alert
        const errorAlert = document.getElementById('error-alert');
        if (errorAlert) {
            setTimeout(() => {
                errorAlert.style.opacity = '0';
                setTimeout(() => errorAlert.remove(), 300);
            }, 5000);
        }
    </script>
</div>