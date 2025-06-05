<div>
    <section class="bg-gray-50 dark:bg-gray-900">
        <div class="mx-auto max-w-screen-xl">
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                <div class="flex flex-col lg:flex-row items-center justify-between space-y-3 xl:space-y-0 xl:space-x-4 p-4">
                    <div class="w-full flex flex-col space-y-4 lg:space-y-0">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                            <div class="flex flex-col sm:flex-row items-center justify-end sm:space-x-4 space-y-2 sm:space-y-0">
                                {{-- Start Date --}}
                                <div class="relative w-full sm:w-auto">
                                    <label for="startDate" class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                                    <input
                                        type="date"
                                        id="startDate"
                                        wire:model.lazy="startDate"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500
                       block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white
                       dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>

                                <span class="text-gray-500 dark:text-gray-300">s.d.</span>

                                {{-- End Date --}}
                                <div class="relative w-full sm:w-auto">
                                    <label for="endDate" class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                                    <input
                                        type="date"
                                        id="endDate"
                                        wire:model.lazy="endDate"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500
                       block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white
                       dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>
                            </div>
                            <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between lg:justify-end space-y-2 md:space-y-0 md:space-x-4 w-full lg:w-auto">
                                <div class="flex items-center space-x-2">
                                    <label class="text-sm font-medium text-gray-900 whitespace-nowrap dark:text-white">Per Page</label>
                                    <select wire:model.live="perPage" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 w-24 md:w-auto dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
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
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3" wire:click="setSortBy('id')">ID</th>
                                <th class="px-4 py-3" wire:click="setSortBy('payDate')">Tanggal Bayar</th>
                                <th class="px-4 py-3">Cicilan</th>
                                <th class="px-4 py-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payments as $index => $payment)
                            <tr wire:key="{{ $payment->id }}" class="border-b dark:border-gray-700">
                                <th class="px-4 py-3">{{ $payments->firstItem() + $index }}</th>
                                <td class="px-4 py-3">{{ $payment->id }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $payment->payDate }}</td>
                                <td class="px-4 py-3 font-semibold text-yellow-600 dark:text-yellow-400">{{ $payment->payment_total }}</td>
                                <td class="px-4 py-3">{{ $payment->description }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-center">Tidak ada data</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="py-4 px-3">
                    {{ $payments->links() }}
                </div>
            </div>
        </div>
    </section>
</div>