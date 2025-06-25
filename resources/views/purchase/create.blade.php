<x-layout>
  <h2 class="mx-auto max-w-screen-xl mb-4 text-2xl font-extrabold leading-none tracking-tight text-gray-900 md:text-3xl dark:text-white">
    {{ $title }}
  </h2>

  {{-- Session alerts (kept as-is) --}}
  @if (session('success'))
  <div id="alert-border-3"
    class="flex relative items-center p-4 mb-4 text-green-800 border-t-4 border-green-300 bg-green-50 dark:text-green-400 dark:bg-gray-800 dark:border-green-800"
    role="alert">
    <svg class="shrink-0 w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
      <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
    </svg>
    <div class="ms-3 text-sm font-medium">
      <span class="font-medium">Informasi!</span> {{ session('success') }}
    </div>
    <button type="button"
      class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700"
      data-dismiss-target="#alert-border-3"
      aria-label="Close">
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
    class="flex relative w-full items-center p-4 mb-4 text-red-800 border-t-4 border-red-300 bg-red-50 dark:text-red-400 dark:bg-gray-800 dark:border-red-800"
    role="alert">
    <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
      <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
    </svg>
    <div class="ms-3 text-sm font-medium">
      {{ session('error') }}
    </div>
    <button type="button"
      class="ms-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700"
      data-dismiss-target="#alert-border-2"
      aria-label="Close">
      <span class="sr-only">Dismiss</span>
      <svg class="w-3 h-3" aria-hidden="true" fill="none" viewBox="0 0 14 14">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
      </svg>
    </button>
  </div>
  @endif

  <div class="grid grid-cols-3 sm:gap-2 gap-5">
    {{-- LEFT SIDE: Live date/time + product‐list --}}
    <div class="col-span-3 xl:col-span-2">
      <div x-data="{ currentTime: '' }"
        x-init="
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
           "
        class="mb-4">
        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
          Tanggal & Waktu
        </label>
        <div class="text-gray-900 dark:text-white text-xl font-mono w-1/2 bg-gray-100 dark:bg-gray-700 p-2.5 rounded-lg">
          <span x-text="currentTime"></span>
        </div>
      </div>

      <div class="relative overflow-x-auto overflow-y-auto shadow-md sm:rounded-lg">
        <table id="transaction-table"
          class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th class="px-6 py-3">Nama Produk</th>
              <th class="px-6 py-3">Harga Beli</th>
              <th class="px-6 py-3">Qty</th>
              <th class="px-6 py-3">Profit</th>
              <th class="px-6 py-3">Expired</th>
              <th class="px-6 py-3">Subtotal</th>
              <th class="px-6 py-3">Aksi</th>
            </tr>
          </thead>
          <tbody id="temp-table-body">
          </tbody>
        </table>
        <div id="temp-loading" class="text-center my-4 hidden">
          <div class="inline-block w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <p class="text-sm text-gray-600 mt-2">Memuat data...</p>
        </div>
      </div>
    </div>

    {{-- RIGHT SIDE: Inputs + summary button --}}
    <div class="col-span-3 xl:col-span-1">
      <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
        <div class="p-4 md:p-5">

          {{-- PRODUCT AUTOCOMPLETE + DETAILS --}}
          <div class="grid gap-3 mb-2 md:grid-cols-2">
            <div class="col-span-2 relative">
              <label for="product-input" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Nama Barang
              </label>
              <input
                type="text"
                id="product-input"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                placeholder="Masukkan nama produk"
                autocomplete="off" />
              <input type="hidden" id="selected-product-id">
            </div>

            <div class="col-span-1">
              <label for="price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Harga Jual
              </label>
              <input
                type="number"
                id="price"
                readonly
                aria-label="disabled input"
                class="bg-gray-100 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400"
                placeholder="Harga" />
            </div>

            <div class="col-span-1">
              <label for="stock" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Stok Tersedia
              </label>
              <input
                type="number"
                id="stock"
                readonly
                aria-label="disabled input"
                class="bg-gray-100 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400"
                placeholder="Stok" />
            </div>

            <div class="col-span-1">
              <label for="qty" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Jumlah Beli
              </label>
              <input
                type="number"
                min="1"
                id="qty"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                placeholder="Jumlah" />
            </div>

            <div class="col-span-1">
              <label for="buyPrice" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Harga Beli
              </label>
              <input
                type="number"
                min="1"
                id="buyPrice"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                placeholder="Masukan harga beli" />
            </div>

            <div class="col-span-1">
              <label for="profit" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Profit
              </label>
              <input
                type="number"
                id="profit"
                readonly
                aria-label="disabled input"
                class="bg-gray-100 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400"
                placeholder="Profit" />
            </div>

            <div class="col-span-1">
              <label for="expDate" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Tanggal Expire
              </label>
              <div class="relative max-w-sm">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                  <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
                  </svg>
                </div>
                <input
                  id="expDate"
                  datepicker-format="dd-mm-yyyy"
                  name="expDate"
                  datepicker
                  datepicker-autohide
                  type="text"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                  placeholder="Pilih Tanggal"
                  required />
              </div>
            </div>

            <div class="col-span-2 flex justify-center w-full items-center">
              <button
                id="submit-btn"
                type="button"
                onclick="addProductRow()"
                class="flex items-center justify-center gap-2 focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                <span class="default-label">Tambah</span>
                <span class="loading-label hidden flex items-center">
                  <svg aria-hidden="true" class="w-4 h-4 mr-1 text-white animate-spin fill-white" viewBox="0 0 100 101" fill="none">
                    <path d="M100 50.6a50 50 0 1 1-9.2-29.2" fill="currentColor" />
                  </svg>
                  Menambahkan...
                </span>
              </button>
            </div>
          </div>

          <hr class="w-48 h-1 mx-auto my-4 bg-gray-100 border-0 rounded-sm md:my-4 dark:bg-gray-700">

          {{-- SUMMARY INPUTS --}}
          <div class="w-full flex justify-start items-center space-x-4 mb-3">
            <label for="total" class="block text-md font-medium text-gray-900 dark:text-white">Total:</label>
            <input
              type="text"
              id="total"
              readonly
              aria-label="disabled input"
              class="bg-gray-100 border text-center border-gray-300 text-gray-900 text-md rounded-lg block w-full p-2.5 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400"
              placeholder="Total" />
          </div>

          <div class="w-full flex items-center justify-start space-x-3 mb-3">
            <label for="faktur"
              class="block mb-2 items-center text-sm font-medium text-gray-900 dark:text-white">
              Faktur:
            </label>
            <input
              type="text"
              id="faktur"
              name="faktur"
              placeholder="Masukkan faktur..."
              required
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5
                 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
          </div>

          <div class="w-full flex items-center justify-start space-x-2 mb-3">
            <div class="sm:w-2/3 w-full relative flex items-center space-x-4">
              <label for="supplier" class="block text-sm font-medium text-gray-900 dark:text-white">Nama:</label>
              <input
                type="text"
                id="supplier"
                name="supplier"
                placeholder="Nama supplier"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white p-2 w-full"
                required />
              <div id="supplier-loading" class="absolute inset-y-0 right-3 flex items-center hidden">
                <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
              </div>
              <input type="hidden" id="selected-supplier-id">
            </div>
            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" id="credit-toggle" class="sr-only peer">
              <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600 dark:peer-checked:bg-blue-600"></div>
              <span class="ms-2 text-xs font-medium text-gray-900 dark:text-gray-300">Utang</span>
            </label>
          </div>

          <div class="flex items-center space-x-4 mb-3">
            <label for="shipping" class="block text-sm font-medium text-gray-900 dark:text-white">Status:</label>
            <select id="shipping"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
              required>
              <option selected value="pending">Belum Tiba</option>
              <option value="arrive">Sudah Tiba</option>
            </select>
          </div>

          <div class="flex items-center space-x-4 mb-3">
            <label for="prePaid" class="block text-sm font-medium text-gray-900 dark:text-white">Bayar:</label>
            <input
              type="text"
              min="0"
              id="prePaid"
              autocomplete="off"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-md rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full text-center p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
              placeholder="Nominal"
              required />
          </div>

          {{-- BUTTON TO SHOW SUMMARY --}}
          <div class="flex justify-center">
            <button
              type="button"
              id="summary"
              class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 inline-flex items-center">
              <svg id="summarySpinner" class="hidden w-4 h-4 mr-2 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span id="summaryText">Ringkasan</span>
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>

  {{-- SUMMARY MODAL --}}
  <div id="customModal"
    class="fixed inset-0 p-4 sm:p-0 z-50 hidden backdrop-blur-sm bg-white/30 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-700 rounded-lg shadow-lg w-full max-w-2xl p-6">
      <h2 class="text-xl text-gray-900 dark:text-white font-semibold mb-4">Ringkasan Pembelian</h2>

      <div class="space-y-1 text-sm mb-4 text-gray-900 dark:text-white">
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
          " class="text-xl text-gray-900 dark:text-white mb-2">
          <strong>Tanggal:</strong> <span x-text="currentTime"></span>
        </div>
        <div>
          <strong>Faktur:</strong>
          <span id="summary-faktur">-</span>
        </div>
        <div>
          <strong>Supplier:</strong>
          <span id="summary-supplier">-</span>
        </div>
        <div>
          <strong>Status Kredit:</strong>
          <span id="summary-status">-</span>
        </div>
        <div>
          <strong>Status Pengiriman:</strong>
          <span id="summary-shipping">-</span>
        </div>
        <div>
          <strong>Total:</strong>
          <span id="summary-total">-</span>
        </div>
        <div>
          <strong>Dibayar:</strong>
          <span id="summary-paid">-</span>
        </div>
      </div>

      <form id="final-transaction-form"
        method="POST"
        action="{{ route('purchases.store') }}">
        @csrf
        {{-- Final‐payload JSON (no date) --}}
        <input type="hidden" name="final_transaction" id="finalTransactionInput">

        <div class="flex justify-end space-x-2">
          <button type="button"
            id="cancelSummaryBtn"
            class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Batal
          </button>
          <button type="submit"
            id="submitTransactionBtn"
            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Konfirm
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Enable/disable all buttons during AJAX calls
    function toggleActionButtons(enable = true) {
      $('button').prop('disabled', !enable);
    }

    // Load temporary purchases (same as before)
    function loadTempPurchases() {
      $('#temp-loading').removeClass('hidden');
      $('#temp-table-body').html('');
      toggleActionButtons(false);

      $.ajax({
        url: "{{ route('purchases.temp') }}",
        method: 'GET',
        success: function(response) {
          let rows = '';
          let total = 0;
          response.forEach(item => {
            const profit = (
              (parseInt(item.price.toString().replace(/\D/g, '')) || 0) -
              (parseInt(item.buyPrice.toString().replace(/\D/g, '')) || 0)
            );
            total += parseInt(item.subtotal.toString().replace(/\D/g, '')) || 0;
            rows += `
              <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                <td class="px-6 py-4">${item.product_name}</td>
                <td class="px-6 py-4">${formatRupiah(item.buyPrice)}</td>
                <td class="px-6 py-4">${item.qty}</td>
                <td class="px-6 py-4">${formatRupiah(profit)}</td>
                <td class="px-6 py-4">${item.expDate ?? '-'}</td>
                <td class="px-6 py-4">${formatRupiah(item.subtotal)}</td>
                <td class="px-6 py-4">
                  <button onclick="deleteTempItem(${item.id})" class="text-red-600 hover:underline">Hapus</button>
                </td>
              </tr>`;
          });
          $('#temp-table-body').html(rows);
          $('#total').val(formatRupiah(total));
        },
        error: function(xhr) {
          console.error(xhr);
          alert('Gagal memuat data penjualan sementara.');
        },
        complete: function() {
          $('#temp-loading').addClass('hidden');
          toggleActionButtons(true);
        }
      });
    }

    loadTempPurchases();

    let productNames = [];

    function fetchLatestProducts() {
      $.ajax({
        url: "{{ route('get.products') }}",
        method: 'GET',
        cache: false,
        success: function(data) {
          productNames = data.map(product => ({
            label: product.name,
            value: product.name,
            id: product.id,
            price: product.price,
            stock: product.stock
          }));

          $("#product-input").autocomplete("option", "source", productNames);

          const selectedId = $('#selected-product-id').val();
          if (selectedId) {
            const current = productNames.find(p => p.id == selectedId);
            if (current) {
              $('#price').val(current.price);
              $('#stock').val(current.stock);
            }
          }
        }
      });
    }

    $(function() {
      $("#product-input").autocomplete({
        source: productNames,
        select: function(event, ui) {
          $('#product-input').val(ui.item.value);
          $('#selected-product-id').val(ui.item.id);
          $('#price').val(ui.item.price);
          $('#stock').val(ui.item.stock);
          return false;
        },
        change: function(event, ui) {
          if (!ui.item) {
            $('#product-input').val('');
            $('#selected-product-id').val('');
            $('#price').val('');
            $('#stock').val('');
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

      fetchLatestProducts();
      setInterval(fetchLatestProducts, 10000);
    });

    $(function() {
      $("#supplier").autocomplete({
        source: function(request, response) {
          $("#supplier-loading").removeClass('hidden');
          $.ajax({
            url: "{{ route('get.suppliers') }}",
            data: {
              term: request.term
            },
            success: function(data) {
              response(data.map(supplier => ({
                label: supplier.name,
                value: supplier.name,
                id: supplier.id
              })));
            },
            complete: function() {
              $("#supplier-loading").addClass('hidden');
            }
          });
        },
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

    const buyEl = document.getElementById('buyPrice');
    const sellEl = document.getElementById('price');
    const profEl = document.getElementById('profit');

    function formatRupiah(number) {
      return new Intl.NumberFormat("id-ID", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(number);
    }

    // format “Bayar” input
    function formatRupiahInput(el) {
      el.addEventListener('input', function() {
        let number = this.value.replace(/[^\d]/g, '');
        this.value = formatRupiah(number);
      });
      el.addEventListener('keypress', function(e) {
        if (!/[0-9]/.test(e.key)) e.preventDefault();
      });
    }
    formatRupiahInput(document.getElementById('prePaid'));

    function calcProfit() {
      if (!buyEl.value.trim() || !sellEl.value.trim()) {
        alert('Harga beli dan harga jual tidak boleh kosong!');
        profEl.value = '';
        buyEl.value = '';
        return;
      }
      const buy = parseInt(buyEl.value) || 0;
      const sell = parseInt(sellEl.value) || 0;
      profEl.value = (sell - buy);
    }
    buyEl.addEventListener('blur', calcProfit);

    function addProductRow() {
      $('#submit-btn').attr('disabled', true);
      $('#submit-btn .default-label').addClass('hidden');
      $('#submit-btn .loading-label').removeClass('hidden');
      toggleActionButtons(false);

      function resetButton() {
        $('#submit-btn').attr('disabled', false);
        $('#submit-btn .default-label').removeClass('hidden');
        $('#submit-btn .loading-label').addClass('hidden');
      }

      const inputName = $('#product-input').val();
      const product = productNames.find(p => p.value.toLowerCase() === inputName.toLowerCase());
      if (!product) {
        alert('Produk tidak ditemukan.');
        resetButton();
        toggleActionButtons(true);
        return;
      }

      const productId = product.id;
      const price = product.price;
      let qty = parseInt($('#qty').val());
      let buyPrice = parseInt($('#buyPrice').val());
      let expDate = $('#expDate').val() || null;

      if (!productId || qty <= 0 || isNaN(qty) || isNaN(buyPrice) || isNaN(price)) {
        alert('Periksa input: input tidak valid.');
        resetButton();
        toggleActionButtons(true);
        return;
      }

      $.ajax({
        url: "{{ route('purchases.temp.store') }}",
        method: "POST",
        data: {
          product_id: productId,
          qty: qty,
          buyPrice: buyPrice,
          expDate: expDate,
          _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if (response.success) {
            loadTempPurchases();
            resetButton();
            // clear inputs
            $('#product-input').val('');
            $('#qty').val('');
            $('#buyPrice').val('');
            $('#profit').val('');
            $('#expDate').val('');
            $('#price').val('');
            $('#stock').val('');
            $('#selected-product-id').val('');
          } else {
            alert(response.message || 'Gagal menambahkan produk.');
          }
        },
        error: function(xhr) {
          resetButton();
          toggleActionButtons(true);
          alert('Terjadi kesalahan saat menyimpan produk.');
        },
        complete: function() {
          resetButton();
        }
      });
    }

    function deleteTempItem(id) {
      if (!confirm('Apakah Anda yakin ingin menghapus item ini?')) return;
      $.ajax({
        url: "{{ route('purchases.temp.delete') }}",
        method: 'POST',
        data: {
          id: id,
          _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          if (response.success) {
            loadTempPurchases();
          } else {
            alert(response.message || 'Gagal menghapus item.');
          }
        },
        error: function() {
          alert('Terjadi kesalahan saat menghapus item.');
        }
      });
    }

    function showModal() {
      document.getElementById('customModal').classList.remove('hidden');
    }

    function hideModal() {
      document.getElementById('customModal').classList.add('hidden');
    }
    document.getElementById('cancelSummaryBtn').addEventListener('click', hideModal);

    const summaryBtn = document.getElementById('summary');
    const summarySpinner = document.getElementById('summarySpinner');
    const summaryText = document.getElementById('summaryText');

    summaryBtn.addEventListener('click', function() {
      summaryBtn.disabled = true;
      summarySpinner.classList.remove('hidden');
      summaryText.textContent = 'Memuat...';

      const xtotal = parseInt(document.getElementById('total').value.replace(/\D/g, '')) || 0;
      const xpaid = parseInt(document.getElementById('prePaid').value.replace(/\D/g, '')) || 0;
      const xsupplierId = document.getElementById('selected-supplier-id').value || null;
      let xisCredit = document.getElementById('credit-toggle').checked;
      const xisShipped = document.getElementById('shipping')?.value;
      const xfaktur = document.getElementById('faktur')?.value.trim();

      $.get("{{ route('purchases.temp') }}", function(tempItems) {
        toggleActionButtons(false);

        if (tempItems.length === 0) {
          alert('Tidak ada item dalam penjualan.');
          resetSummaryButton();
          toggleActionButtons(true);
          return;
        }

        if (!xisCredit && xpaid < xtotal) {
          alert('Pembayaran kurang dari total. Hanya diizinkan untuk kredit.');
          resetSummaryButton();
          toggleActionButtons(true);
          return;
        }

        if (!xsupplierId) {
          alert('Masukan nama supplier.');
          resetSummaryButton();
          toggleActionButtons(true);
          return;
        }

        if (!xisShipped || xisShipped === '') {
          alert('Masukan status pengiriman.');
          resetSummaryButton();
          toggleActionButtons(true);
          return;
        }

        if (!xfaktur) {
          alert('Masukan faktur pembelian.');
          resetSummaryButton();
          toggleActionButtons(true);
          return;
        }

        if (xisCredit && xpaid >= xtotal) {
          xisCredit = false;
          document.getElementById('credit-toggle').checked = false;
        }

        if (xpaid > xtotal) {
          alert('Nominal pembayaran tidak boleh melebihi total.');
          resetSummaryButton();
          toggleActionButtons(true);
          return;
        }

        const summaryfaktur = document.getElementById('summary-faktur');
        const summarySupplier = document.getElementById('summary-supplier');
        const summaryStatus = document.getElementById('summary-status');
        const summaryShippng = document.getElementById('summary-shipping');
        const summaryTotal = document.getElementById('summary-total');
        const summaryPaid = document.getElementById('summary-paid');
        const finalTransactionInput = document.getElementById('finalTransactionInput');

        summaryfaktur.textContent = xfaktur;
        summarySupplier.textContent = document.getElementById('supplier')?.value || '-';
        summaryStatus.textContent = xisCredit ? 'Kredit' : 'Lunas';
        summaryShippng.textContent = xisShipped;
        summaryTotal.textContent = formatRupiah(xtotal);
        summaryPaid.textContent = formatRupiah(xpaid);

        // Build final payload (no date)
        finalTransactionInput.value = JSON.stringify({
          faktur: xfaktur,
          total: xtotal,
          paid: xpaid,
          supplier_id: xsupplierId,
          credit: xisCredit,
          shipping: xisShipped
        });

        showModal();
        resetSummaryButton();
        toggleActionButtons(true);

      }).fail(function() {
        alert('Gagal mengambil data penjualan sementara.');
        resetSummaryButton();
        toggleActionButtons(true);
      });

      function resetSummaryButton() {
        summaryBtn.disabled = false;
        summarySpinner.classList.add('hidden');
        summaryText.textContent = 'Ringkasan';
      }
    });
  </script>
</x-layout>