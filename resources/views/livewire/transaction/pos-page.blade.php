<div>
    <div class="grid grid-cols-3 sm:gap-2 gap-5">
        <div class="col-span-3 xl:col-span-2">
            <div x-data="{ currentTime: '' }" x-init="
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
" class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                    Tanggal & Waktu
                </label>
                <div class="text-gray-900 dark:text-white text-xl font-mono w-1/2 bg-gray-100 dark:bg-gray-700 p-2.5 rounded-lg">
                    <span x-text="currentTime"></span>
                </div>
            </div>
            <div class="relative overflow-x-auto overflow-y-auto shadow-md sm:rounded-lg">
                <table id="transaction-table" class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                Nama Produk
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Harga
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Diskon
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Qty
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Subtotal
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-span-3 xl:col-span-1">
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                <div class="p-4 md:p-5">
                    <div class="grid gap-3 mb-2 md:grid-cols-2">
                        <div x-data="{ open: false }" class="col-span-2 relative">
                            <label for="product-input" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama Barang</label>

                            <input
                                type="text"
                                id="product-input"
                                wire:model.debounce.300ms="productName"
                                x-on:focus="open = true"
                                x-on:click="open = true"
                                x-on:keydown.escape="open = false"
                                x-on:blur="setTimeout(() => open = false, 200)"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                placeholder="Masukkan nama produk"
                                autocomplete="off">

                            <input type="hidden" wire:model="selectedProductId">

                            {{-- Dropdown --}}
                            <ul
                                x-show="open"
                                class="absolute z-10 w-full bg-white border border-gray-300 mt-1 rounded-lg shadow max-h-40 overflow-y-auto text-sm">
                                @forelse ($products as $product)
                                <li
                                    wire:click="selectProduct({{ $product['id'] }}, '{{ addslashes($product['name']) }}')"
                                    x-on:click="open = false"
                                    class="px-4 py-2 cursor-pointer hover:bg-gray-200">
                                    {{ $product['name'] }}
                                </li>
                                @empty
                                <li class="px-4 py-2 text-gray-500">Tidak ada hasil</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="col-span-1 sm:col-span-1">
                            <label for="price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Harga</label>
                            <input type="number" name="price" id="price" wire:model="price" readonly
                                class="bg-gray-100 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="Harga">
                        </div>

                        <div class="col-span-1 sm:col-span-1">
                            <label for="stock" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Stok Tersedia</label>
                            <input type="number" name="stock" id="stock" wire:model="stock" readonly
                                class="bg-gray-100 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="Stok">
                        </div>

                        <div class="col-span-1 sm:col-span-1">
                            <label for="qty" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Jumlah Beli</label>
                            <input type="number" min="1" name="qty" id="qty" wire:model="qty"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                placeholder="Min. stok" required>
                        </div>

                        <div class="col-span-1 sm:col-span-1">
                            <label for="disc" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Diskon per Barang</label>
                            <input type="number" min="1" name="disc" value="0" id="disc" wire:model="disc"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                placeholder="Min. stok" required>
                        </div>

                        <div class="col-span-2 flex justify-center w-full items-center">
                            <button type="button" wire:click="addProductRow"
                                class="focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">Tambah</button>
                        </div>
                    </div>

                    <hr class="w-48 h-1 mx-auto my-4 bg-gray-100 border-0 rounded-sm md:my-4 dark:bg-gray-700">

                    <div class="w-full flex justify-start items-center space-x-4 mb-3">
                        <label for="total" class="block text-md font-medium text-gray-900 dark:text-white">Total:</label>
                        <input type="text" name="total" id="total" wire:model="total" disabled
                            class="bg-gray-100 border text-center border-gray-300 text-gray-900 text-md rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            placeholder="Total">
                    </div>

                    <div class="w-full flex items-center justify-start space-x-2 mb-3">
                        <div class="sm:w-2/3 w-full flex items-center space-x-4">
                            <label for="customer" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama: </label>
                            <input type="text" id="customer" name="customer" wire:model="customerName"
                                placeholder="Tanpa Nama"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 p-2 w-full">
                            <input type="hidden" id="selected-customer-id" wire:model="selectedCustomerId">
                        </div>

                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="credit-toggle" wire:model="isCredit" class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600 dark:peer-checked:bg-blue-600"></div>
                            <span class="ms-2 text-xs font-medium text-gray-900 dark:text-gray-300">Utang</span>
                        </label>
                    </div>

                    <div class="flex items-center space-x-4 mb-3">
                        <label for="prePaid" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Bayar:</label>
                        <input type="text" min="0" name="prePaid" id="prePaid" wire:model="prePaid"
                            autocomplete="off"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-md rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full text-center p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="Nominal" required>
                    </div>
                    <div class="flex justify-center">
                        <button type="button" id="summary" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">Ringkasan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>