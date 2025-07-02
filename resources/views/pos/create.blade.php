<x-layout>
  <h2 class="mx-auto max-w-screen-xl mb-4 text-2xl font-extrabold leading-none tracking-tight text-gray-900 md:text-3xl dark:text-white">POS</h2>
  @if (session('transaction_id'))
  <!-- 1) Make the overlay switch colors -->
  <div
    id="successModal"
    class="fixed inset-0 bg-white/30 dark:bg-black/30 backdrop-blur-sm flex items-center justify-center z-50">
    <!-- 2) Inner panel: white on light, gray-800 on dark; text likewise -->
    <div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white p-6 rounded-xl shadow-2xl max-w-sm w-full">
      <h2 class="text-lg font-semibold mb-4 text-center">
        Penjualan berhasil disimpan
      </h2>
      <div class="flex justify-center gap-4">
        <button
          id="printButton"
          data-transaction-id="{{ session('transaction_id') }}"
          class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition">
          Cetak
        </button>
        <button
          onclick="document.getElementById('successModal').remove()"
          class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-700 text-gray-800 dark:text-white px-4 py-2 rounded transition">
          Batal
        </button>
      </div>
    </div>
  </div>

  <script>
    const printBtn = document.getElementById('printButton');
    const latestTransactionId = parseInt(printBtn.dataset.transactionId, 10);

    function printTransaction(id) {
      window.open(`/print/${id}`, '_blank', 'width=400,height=600');
    }
    printBtn.addEventListener('click', function() {
      printTransaction(latestTransactionId);
    });
  </script>
  @endif
  @if (session('error'))
  <div id="alert-border-2" class="flex relative w-full items-center p-4 mb-4 text-red-800 border-t-4 border-red-300 bg-red-50 dark:text-red-400 dark:bg-gray-800 dark:border-red-800" role="alert">
    <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
      <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
    </svg>
    <div class="ms-3 text-sm font-medium">
      {{ session('error') }}
    </div>
    <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700" data-dismiss-target="#alert-border-2" aria-label="Close">
      <span class="sr-only">Dismiss</span>
      <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
      </svg>
    </button>
  </div>
  @endif
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
          <tbody id="temp-table-body">
          </tbody>
        </table>
        <div id="temp-loading" class="text-center my-4 hidden">
          <div class="inline-block w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <p class="text-sm text-gray-600 mt-2">Memuat data...</p>
        </div>
      </div>
    </div>
    <div class="col-span-3 xl:col-span-1">
      <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
        <div class="p-4 md:p-5">
          <div class="grid gap-3 mb-2 md:grid-cols-2">
            <div class="col-span-2 relative">
              <label for="product-input" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama Barang</label>
              <input type="text" id="product-input" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="Masukkan nama produk" autocomplete="off">
              <input type="hidden" id="selected-product-id">
            </div>
            <div class="col-span-1 sm:col-span-1">
              <label for="price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Harga</label>
              <input type="number" name="price" id="price" aria-label="disabled input" class="bg-gray-100 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Harga" readonly>
            </div>
            <div class="col-span-1 sm:col-span-1">
              <label for="stock" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Stok Tersedia</label>
              <input type="number" name="stock" id="stock" aria-label="disabled input" class="bg-gray-100 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Stok" readonly>
            </div>
            <div class="col-span-1 sm:col-span-1">
              <label for="qty" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Jumlah Beli</label>
              <input type="number" min="1" name="qty" id="qty" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Min. stok" required="">
            </div>
            <div class="col-span-1 sm:col-span-1">
              <label for="disc" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Diskon per Barang</label>
              <input type="number" min="1" name="disc" value="0" id="disc" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Min. stok" required="">
            </div>
            <div class="col-span-2 flex justify-center w-full items-center">
              <button id="submit-btn" type="button" onclick="addProductRow()" class="flex items-center justify-center gap-2 focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
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
          <div class="w-full flex justify-start items-center space-x-4 mb-3">
            <label for="total" class="block text-md font-medium text-gray-900 dark:text-white">Total:</label>
            <input type="text" name="total" id="total" aria-label="disabled input" class=" bg-gray-100 border text-center border-gray-300 text-gray-900 text-md rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Total" disabled>
          </div>
          <div class="w-full flex items-center justify-start space-x-2 mb-3">
            <div class="sm:w-2/3 relative w-full flex items-center space-x-4">
              <label for="customer" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama: </label>
              <input type="text" id="customer" name="customer" placeholder="Tanpa Nama" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 pr-4 dark:focus:border-primary-500 p-2 w-full">
              <div id="customer-loading" class="absolute inset-y-0 right-3 flex items-center hidden">
                <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
              </div>
              <input type="hidden" id="selected-customer-id">
            </div>
            <label class="inline-flex items-center cursor-pointer">
              <input type="checkbox" id="credit-toggle" value="" class="sr-only peer">
              <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600 dark:peer-checked:bg-blue-600"></div>
              <span class="ms-2 text-xs font-medium text-gray-900 dark:text-gray-300">Utang</span>
            </label>
          </div>
          <div class="flex items-center space-x-4 mb-3">
            <label for="prePaid" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Bayar:</label>
            <input type="text" min="0" name="prePaid" id="prePaid" autocomplete="off" class="bg-gray-50 border border-gray-300 text-gray-900 text-md rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full text-center p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Nominal" required="">
          </div>
          <div class="flex justify-center">
            <button type="button" id="summary" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 inline-flex items-center">
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

  <div id="delete-confirm-modal" class="fixed z-50 inset-0 hidden backdrop-blur-sm bg-opacity-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Konfirmasi Penghapusan</h2>
      <input type="hidden" id="delete-temp-id">
      <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
        <input id="supervisor-username" autocomplete="off" type="text" class="w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring focus:ring-green-300" />
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
        <input id="supervisor-password" type="password" class="w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring focus:ring-green-300" />
      </div>
      <div class="flex justify-end gap-2">
        <button onclick="closeDeleteModal()" class="px-4 py-2 rounded-md text-sm text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500">Batal</button>
        <button onclick="confirmDelete()" class="px-4 py-2 rounded-md text-sm text-white bg-red-600 hover:bg-red-700">Hapus</button>
      </div>
    </div>
  </div>

  <div id="credit-auth-modal" class="fixed z-50 inset-0 hidden backdrop-blur-sm bg-opacity-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Konfirmasi Kredit</h2>
      <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
        <input id="credit-supervisor-username" type="text" autocomplete="off"
          class="w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring focus:ring-green-300" />
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
        <input id="credit-supervisor-password" type="password"
          class="w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring focus:ring-green-300" />
      </div>
      <div class="flex justify-end gap-2">
        <button onclick="closeCreditModal()" class="px-4 py-2 rounded-md text-sm text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500">Batal</button>
        <button onclick="confirmCreditAuth()" class="px-4 py-2 rounded-md text-sm text-white bg-blue-600 hover:bg-blue-700">Aktifkan</button>
      </div>
    </div>
  </div>

  <div id="customModal" class="fixed flex inset-0 p-4 sm:p-0 z-50 hidden backdrop-blur-sm bg-white/30 top-0 left-0 items-center justify-center">
    <div class="bg-white dark:bg-gray-700 rounded-lg shadow-lg w-full max-w-2xl p-6">
      <h2 class="text-xl text-gray-900 dark:text-white font-semibold mb-4">Ringkasan Penjualan</h2>

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
          <strong>Pembeli:</strong> <span id="summary-customer">-</span>
        </div>
        <div>
          <strong>Status:</strong> <span id="summary-status">-</span>
        </div>
        <div>
          <strong>Total:</strong> <span id="summary-total">-</span>
        </div>
        <div>
          <strong>Dibayar:</strong> <span id="summary-paid">-</span>
        </div>
        <div class="text-2xl font-bold text-green-600">
          <strong>Kembali:</strong> <span id="summary-change">-</span>
        </div>
      </div>

      <form id="final-transaction-form" method="POST" action="{{ route('transactions.store') }}">
        @csrf
        <input type="hidden" name="final_transaction" id="finalTransactionInput">
        <div class="flex justify-end space-x-2">
          <button type="button" id="cancelSummaryBtn" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Batal
          </button>
          <button type="submit" id="submitTransactionBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Konfirm
          </button>
      </form>
    </div>
  </div>

  <div id="remainingModal" class="fixed inset-0 hidden bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-sm w-full p-6">
      <h3 class="text-lg font-semibold mb-4 dark:text-white">Sisa Utang Pelanggan</h3>
      <div id="remainingModalBody" class="mb-6 text-gray-900 dark:text-gray-200">
        <!-- injected content -->
      </div>
      <div class="text-right">
        <button onclick="closeRemainingModal()"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
          OK
        </button>
      </div>
    </div>
  </div>
</x-layout>

<script>
  const currentUserRole = @json(Auth::user() - > role);
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  function toggleActionButtons(enable = true) {
    $('button').prop('disabled', !enable);
  }

  function loadTempTransactions() {
    $('#temp-loading').removeClass('hidden'); // Show loader
    $('#temp-table-body').html('');
    toggleActionButtons(false);
    $.ajax({
      url: "{{ route('transactions.temp') }}", // endpoint ini akan mengembalikan JSON dari temp_transaction milik user saat ini
      method: 'GET',
      success: function(response) {
        let rows = '';
        let total = 0;
        response.forEach(item => {
          total += parseInt(item.subtotal.toString().replace(/\D/g, '')) || 0;
          rows += `
          <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
            <td class="px-6 py-4">${item.product_name}</td>
            <td class="px-6 py-4">${formatRupiah(item.price)}</td>
            <td class="px-6 py-4">${formatRupiah(item.disc)}</td>
            <td class="px-6 py-4">${item.qty}</td>
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
        $('#temp-loading').addClass('hidden'); // Hide loader in both success & error
        toggleActionButtons(true);
      }
    });
  }

  loadTempTransactions();

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

        // Update the autocomplete source dynamically
        $("#product-input").autocomplete("option", "source", productNames);

        // Update fields if same product still selected
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
    // Initialize autocomplete (with empty source)
    $("#product-input").autocomplete({
      source: productNames, // will be dynamically updated
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

    // Initial fetch + 1s interval
    fetchLatestProducts();
    setInterval(fetchLatestProducts, 10000);
  });

  $(function() {
    $("#customer").autocomplete({
      source: function(request, response) {
        $("#customer-loading").removeClass('hidden');
        $.ajax({
          url: "{{ route('get.customers') }}", // make sure this route returns JSON list
          data: {
            term: request.term
          },
          success: function(data) {
            response(data.map(customer => ({
              label: customer.name,
              value: customer.name,
              id: customer.id
            })));
          },
          complete: function() {
            $("#customer-loading").addClass('hidden'); // Hide spinner
          }
        });
      },
      select: function(event, ui) {
        $('#customer').val(ui.item.value);
        $('#selected-customer-id').val(ui.item.id);
        return false;
      },
      change: function(event, ui) {
        if (!ui.item) {
          $('#customer').val('');
          $('#selected-customer-id').val('');
          isCredit = false;
          creditToggle.checked = false;
          customerInput.disabled = false;
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

  let creditToggle = document.getElementById('credit-toggle');
  const customerInput = document.getElementById('customer');
  const summaryBtn = document.getElementById('summary');

  let isCredit = false;
  let pendingToggleState = false;

  creditToggle.addEventListener('change', function() {
    // always require a customer first
    if (!customerInput.value.trim()) {
      alert('Harap masukkan nama pembeli untuk penjualan kredit.');
      this.checked = false;
      return;
    }

    const customerId = $('#selected-customer-id').val();
    const custName = customerInput.value;

    //  — Owner: no modal, just toggle + show remaining
    if (currentUserRole === 'owner') {
      isCredit = this.checked;
      customerInput.disabled = this.checked;
      if (isCredit) {
        fetchAndShowRemaining(customerId, custName);
      }
      return;
    }

    // — Non‑owner: need supervisor auth first
    if (this.checked) {
      this.checked = false;
      pendingToggleState = true;
      showCreditModal();
    } else {
      isCredit = false;
      customerInput.disabled = false;
    }
  });


  function showCreditModal() {
    document.getElementById('credit-supervisor-username').value = '';
    document.getElementById('credit-supervisor-password').value = '';
    document.getElementById('credit-auth-modal').classList.remove('hidden');
  }

  function closeCreditModal() {
    document.getElementById('credit-auth-modal').classList.add('hidden');
    pendingToggleState = false;
    isCredit = false;
  }

  function confirmCreditAuth() {
    const usernameInput = document.getElementById('credit-supervisor-username');
    const passwordInput = document.getElementById('credit-supervisor-password');

    const username = usernameInput.value;
    const password = passwordInput.value;

    if (!username || !password) {
      alert('Username dan password wajib diisi.');
      return;
    }

    $.ajax({
      url: "{{ route('transactions.auth.credit') }}",
      method: "POST",
      data: {
        username,
        password
      },
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      success: function(response) {
        if (response.success) {
          document.getElementById('credit-auth-modal').classList.add('hidden');
          creditToggle.checked = true;
          isCredit = true;

          const customerId = $('#selected-customer-id').val();
          const custName = $('#customer').val();
          fetchAndShowRemaining(custId, custName);
        } else {
          usernameInput.value = '';
          passwordInput.value = '';
          alert(response.message || 'Otorisasi gagal.');
        }
      },
      error: function() {
        alert('Terjadi kesalahan saat mengotorisasi.');
        usernameInput.value = '';
        passwordInput.value = '';
      }
    });
  }

  function fetchAndShowRemaining(customerId, customerName) {
    $.ajax({
      url: "{{ route('credit.remaining') }}",
      method: 'GET',
      data: {
        customer_id: customerId
      },
      headers: {
        'X-CSRF-TOKEN': csrfToken
      },
      success(data) {
        const rupiah = new Intl.NumberFormat('id-ID').format(data.remaining);
        // build the markup
        const html = `
        <p><strong>${customerName}</strong></p>
        <p>Sisa utang: <span class="font-semibold">Rp ${rupiah}</span></p>
      `;
        $('#remainingModalBody').html(html);
        $('#remainingModal').removeClass('hidden');
      },
      error() {
        $('#remainingModalBody').html('<p>Gagal mengambil data sisa utang.</p>');
        $('#remainingModal').removeClass('hidden');
      }
    });
  }

  function closeRemainingModal() {
    $('#remainingModal').addClass('hidden');
  }


  customerInput.addEventListener('input', function() {
    if (this.value.trim() === '') {
      isCredit = false;
      creditToggle.checked = false;
      $('#selected-customer-id').val('');
    }
  });

  function formatRupiah(number) {
    return new Intl.NumberFormat("id-ID", {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(number);
  }

  function formatRupiahInput(el) {
    el.addEventListener('input', function() {
      // Remove any non-digit characters
      let number = this.value.replace(/[^\d]/g, '');
      // Format it
      this.value = formatRupiah(number);
    });

    // Optional: prevent typing letters entirely
    el.addEventListener('keypress', function(e) {
      if (!/[0-9]/.test(e.key)) {
        e.preventDefault();
      }
    });
  }

  formatRupiahInput(document.getElementById('prePaid'));

  function resetInputs() {
    $('#product-input').val('');
    $('#qty').val('');
    $('#disc').val('0');
    $('#price').val('');
    $('#stock').val('');
    $('#selected-product-id').val('');
  }

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
    const stock = parseInt(product.stock);

    let qty = parseInt($('#qty').val());
    let disc = parseInt($('#disc').val()) || 0;

    if (qty > stock) {
      alert('Jumlah tidak boleh lebih dari stok!');
      resetButton();
      toggleActionButtons(true);
      return;
    }

    if (!productId || qty <= 0 || disc < 0 || isNaN(qty) || isNaN(disc)) {
      alert('Periksa input: input tidak valid.');
      resetButton();
      toggleActionButtons(true);
      return;
    }

    // Send via AJAX
    $.ajax({
      url: "{{ route('transactions.temp.store') }}",
      method: "POST",
      data: {
        product_id: productId,
        qty: qty,
        disc: disc,
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        if (response.success) {
          loadTempTransactions(); // refresh table from server
          resetInputs();
          // recalculateTotal();
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
    // if current user is owner, delete immediately
    if (currentUserRole === 'owner') {
      $.ajax({
        url: "{{ route('transactions.temp.deleteWithAuth') }}",
        method: 'POST',
        data: {
          id: id,
          // send no username/password so back‑end should also bypass
          _token: $('meta[name="csrf-token"]').attr('content')
        },
        success(resp) {
          if (resp.success) {
            loadTempTransactions();
          } else {
            alert(resp.message || 'Gagal menghapus item.');
          }
        },
        error() {
          alert('Terjadi kesalahan saat mencoba menghapus item.');
        }
      });
      return;
    }

    // otherwise, show the supervisor auth modal as before
    $('#delete-temp-id').val(id);
    $('#supervisor-username').val('');
    $('#supervisor-password').val('');
    $('#delete-confirm-modal').removeClass('hidden');
  }

  function closeDeleteModal() {
    $('#delete-confirm-modal').addClass('hidden');
  }

  function confirmDelete() {
    const id = $('#delete-temp-id').val();
    const usernameInput = $('#supervisor-username');
    const passwordInput = $('#supervisor-password');

    const username = usernameInput.val().trim();
    const password = passwordInput.val();

    if (!username || !password) {
      alert('Masukkan username dan password.');
      return;
    }

    $.ajax({
      url: "{{ route('transactions.temp.deleteWithAuth') }}",
      method: 'POST',
      data: {
        id: id,
        username: username,
        password: password,
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        if (response.success) {
          loadTempTransactions();
          closeDeleteModal();
        } else {
          alert(response.message || 'Gagal menghapus item.');
          usernameInput.val('');
          passwordInput.val('');
        }
      },
      error: function(xhr) {
        alert('Terjadi kesalahan saat mencoba menghapus item.');
        usernameInput.val('');
        passwordInput.val('');
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

  const summarySpinner = document.getElementById('summarySpinner');
  const summaryText = document.getElementById('summaryText');

  summaryBtn.addEventListener('click', function() {

    summaryBtn.disabled = true;
    summarySpinner.classList.remove('hidden');
    summaryText.textContent = 'Memuat...';

    const xtotal = parseInt(document.getElementById('total').value.replace(/\D/g, '')) || 0;
    let xpaid = parseInt(document.getElementById('prePaid').value.replace(/\D/g, '')) || 0;
    const xcustomerId = document.getElementById('selected-customer-id').value || null;
    let xisCredit = document.getElementById('credit-toggle').checked;

    $.get("{{ route('transactions.temp') }}", function(tempItems) {

      toggleActionButtons(false);

      if (tempItems.length === 0) {
        alert('Tidak ada item dalam penjualan.');
        resetButton();
        toggleActionButtons(true);
        return;
      }

      if (!xisCredit && xpaid < xtotal) {
        alert('Pembayaran kurang dari total. Hanya diizinkan untuk kredit.');
        resetButton();
        toggleActionButtons(true);
        return;
      }

      if (xisCredit && xpaid >= xtotal) {
        xisCredit = false;
        creditToggle.checked = false;
      }

      const customInput = document.querySelector('input[name="transaction_at"]');

      const summaryCustomer = document.getElementById('summary-customer');
      const summaryStatus = document.getElementById('summary-status');
      const summaryTotal = document.getElementById('summary-total');
      const summaryPaid = document.getElementById('summary-paid');
      const summaryChange = document.getElementById('summary-change');
      const summaryItems = document.getElementById('summary-items');
      const finalTransactionInput = document.getElementById('finalTransactionInput');

      const change = Math.max(0, xpaid - xtotal);

      summaryCustomer.textContent = document.getElementById('customer')?.value || '-';
      summaryStatus.textContent = xisCredit ? 'Kredit' : 'Lunas';
      summaryTotal.textContent = formatRupiah(xtotal);
      summaryPaid.textContent = formatRupiah(xpaid);
      summaryChange.textContent = formatRupiah(change);

      xpaid = Math.min(xpaid, xtotal);

      finalTransactionInput.value = JSON.stringify({
        total: xtotal,
        paid: xpaid,
        customer_id: xcustomerId,
        credit: xisCredit
        // You no longer need to send items, they're in DB already
      });

      console.log(finalTransactionInput);

      showModal();
      resetButton();
      toggleActionButtons(true);

    }).fail(function() {
      alert('Gagal mengambil data penjualan sementara.');
      resetButton();
      toggleActionButtons(true);
    });

    function resetButton() {
      summaryBtn.disabled = false;
      summarySpinner.classList.add('hidden');
      summaryText.textContent = 'Ringkasan';
    }
  });
</script>