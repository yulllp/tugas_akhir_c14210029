<div>
    <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
        <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
            <div class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-7 flex-shrink-0">
                <div class="flex space-x-2">
                    <label class="w-full flex items-center text-sm font-medium text-gray-900 whitespace-nowrap dark:text-white">Per Page</label>
                    <select wire:model.live="perPage" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        <option selected value="5">5</option>
                        <option value="10">10</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3">#</th>
                        <th
                            scope="col"
                            class="px-4 py-3 cursor-pointer"
                            wire:click="setSortBy('buyPrice')">
                            Harga Beli
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 cursor-pointer"
                            wire:click="setSortBy('entryDate')">
                            Tanggal Masuk
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($buyRows as $idx => $row)
                    <tr class="border-b dark:border-gray-700">
                        <td class="px-4 py-3">
                            {{ $buyRows->firstItem() + $idx }}
                        </td>
                        <td class="px-4 py-3">
                            {{ number_format($row->buyPrice, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            {{ \Carbon\Carbon::parse($row->entryDate)->format('d-m-Y H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $buyRows->links() }}
        </div>
    </div>
</div>