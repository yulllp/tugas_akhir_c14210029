<x-layout>
  <h2 class="mx-auto max-w-screen-xl mb-8 text-2xl font-extrabold leading-none tracking-tight text-gray-900 md:text-3xl dark:text-white">
    Laporan Arus Kas
  </h2>

  <div class="text-gray-800 dark:text-gray-100">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
      <div class="flex flex-wrap gap-3">
        <select
          id="range"
          class="rounded-lg border min-w-[8rem] border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2 w-fit text-sm shadow-sm focus:ring focus:ring-blue-200 dark:focus:ring-blue-800">
          <option value="today">Hari Ini</option>
          <option value="7days">7 Hari Lalu</option>
          <option value="1month">1 Bulan Lalu</option>
          <option value="1year">1 Tahun Lalu</option>
          <option value="all">Seluruhnya</option>
          <option value="custom">Pilih Tanggal</option>
        </select>
        <input
          type="date"
          id="start"
          class="hidden rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2 text-sm shadow-sm focus:ring focus:ring-blue-200 dark:focus:ring-blue-800" />
        <input
          type="date"
          id="end"
          class="hidden rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2 text-sm shadow-sm focus:ring focus:ring-blue-200 dark:focus:ring-blue-800" />
        <select
          id="activity"
          class="rounded-lg border min-w-[10rem] border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2 text-sm shadow-sm focus:ring focus:ring-blue-200 dark:focus:ring-blue-800">
          <option value="all">Semua Aktivitas</option>
          <option value="transaction">Transaksi</option>
          <option value="purchasing">Pembelian</option>
          <option value="other">Aktivitas Lain</option>
        </select>
        <select
          id="cash_type"
          class="rounded-lg border min-w-[8rem] border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2 text-sm shadow-sm focus:ring focus:ring-blue-200 dark:focus:ring-blue-800">
          <option value="all">Semua Kas</option>
          <option value="in">Kas Masuk</option>
          <option value="out">Kas Keluar</option>
        </select>
        <button
          id="filter"
          class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow-sm transition duration-150">
          Terapkan
        </button>
      </div>
      <button
        id="exportButton"
        class="bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold shadow-sm transition duration-150 text-white px-4 py-2">
        Cetak / Export
      </button>
    </div>

    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400 italic">
      <span>Note: Maksimal 3 tahun. Jika lebih dari 45 hari, grafik ditampilkan per bulan.</span>
    </div>

    <!-- SUMMARY CARDS -->
    <div
      id="summary"
      class="bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-6 mb-8">
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 text-center">
        <div class="text-green-600 dark:text-green-400">
          <p class="text-sm font-medium">Kas Masuk</p>
          <p id="total-in" class="text-lg font-bold">Rp 0</p>
        </div>
        <div class="text-red-600 dark:text-red-400">
          <p class="text-sm font-medium">Kas Keluar</p>
          <p id="total-out" class="text-lg font-bold">Rp 0</p>
        </div>
        <div>
          <p class="text-sm font-medium">Pendapatan Bersih</p>
          <p id="net-income" class="text-lg font-bold text-gray-800 dark:text-gray-200">Rp 0</p>
        </div>
      </div>
    </div>

    <!-- CHART CONTAINER -->
    <div class="w-full overflow-x-auto mb-8">
      <div
        id="cashflow-chart"
        class="min-w-[800px] overflow-x-auto h-[450px] bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-4"></div>
    </div>

    <!-- LOADING OVERLAY -->
    <div
      id="loading-overlay"
      class="fixed inset-0 bg-white/70 dark:bg-gray-900/70 z-50 hidden flex items-center justify-center">
      <div class="flex flex-col items-center gap-4">
        <svg
          class="animate-spin h-8 w-8 text-blue-600 dark:text-blue-400"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24">
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8v4l4-4-4-4v4a10 10 0 100 20 10 10 0 01-8-8z" />
        </svg>
        <p class="text-sm text-gray-700 dark:text-gray-300">Memuat data arus kas...</p>
      </div>
    </div>
  </div>

  <!-- EXPORT MODAL -->
  <div
    id="exportModal"
    class="fixed flex inset-0 z-50 hidden items-center justify-center bg-black/50 dark:bg-black/70">
    <div class="bg-white dark:bg-gray-800 dark:text-white rounded-lg w-96 p-6 space-y-4">
      <h2 class="text-lg font-semibold dark:text-white">Ekspor Laporan</h2>
      <form
        id="exportForm"
        method="GET"
        action="{{ route('cashflow.export') }}"
        target="_blank">
        <div class="space-y-2">
          <!-- Aktivitas checklist -->
          <label class="block">
            <input
              type="checkbox"
              name="activity[]"
              value="transactions"
              checked
              class="mr-2" />
            Transaksi
          </label>
          <label class="block">
            <input
              type="checkbox"
              name="activity[]"
              value="purchases"
              checked
              class="mr-2" />
            Pembelian
          </label>
        </div>
        <div class="mt-4 space-y-1">
          <label class="block text-sm dark:text-gray-300">Tanggal Mulai</label>
          <input
            type="date"
            name="start"
            class="w-full border rounded px-2 py-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
          <label class="block text-sm dark:text-gray-300">Tanggal Selesai</label>
          <input
            type="date"
            name="end"
            class="w-full border rounded px-2 py-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
        </div>
        <div class="mt-6 flex justify-end space-x-2">
          <button
            type="button"
            id="cancelExport"
            class="px-4 py-2 border rounded dark:border-gray-500 dark:text-white">
            Batal
          </button>
          <button
            type="submit"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
            Download Excel
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ============================================
       DATATABLE SECTION: Show entries + Table + Pagination
       ============================================ -->
  <div class="mt-8">
    <!-- Rows-per-page selector -->
    <div class="flex items-center justify-between mb-2">
      <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
        <label for="rowsPerPage">Show</label>
        <select
          id="rowsPerPage"
          class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-1 min-w-[4rem] text-sm shadow-sm focus:ring focus:ring-blue-200 dark:focus:ring-blue-800">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
        </select>
        <span>entries</span>
      </div>
      <div id="tablePagination" class="flex items-center space-x-1 text-sm"></div>
    </div>

    <!-- Table container -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 table-auto">
        <thead class="bg-gray-50 dark:bg-gray-800">
          <tr>
            <th
              scope="col"
              class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
              Tanggal
            </th>
            <th
              scope="col"
              class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
              Tipe
            </th>
            <th
              scope="col"
              class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
              Jumlah
            </th>
            <th
              scope="col"
              class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
              Arah
            </th>
          </tr>
        </thead>
        <tbody id="activitiesTableBody" class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
          <!-- Rows will be injected here by JavaScript -->
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================
       SCRIPTS (jQuery + Pagination Logic)
       ============================ -->
  <script>
    // Global state for activities and pagination
    let activitiesData = []; // will hold the full array from AJAX
    let currentPage = 1;
    let rowsPerPage = parseInt($('#rowsPerPage').val());

    // Utility: format number to IDR currency
    function formatCurrency(value) {
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
      }).format(value);
    }

    // Render the chart (same as before)
    function renderChart(data, groupBy) {
      const categories = data.map(d => d.period);
      const seriesNames = [
        'transactions',
        'credit_payments',
        'purchases',
        'credit_purchases',
        'customer_returns',
        'supplier_returns',
      ];
      const cashInSeries = {};
      const cashOutSeries = {};

      seriesNames.forEach(name => {
        cashInSeries[name] = [];
        cashOutSeries[name] = [];
      });

      let totalIn = 0;
      let totalOut = 0;

      data.forEach(row => {
        seriesNames.forEach(name => {
          const inVal = parseFloat(row.cash_in[name] ?? 0);
          const outVal = parseFloat(row.cash_out[name] ?? 0);
          if (inVal) totalIn += inVal;
          if (outVal) totalOut += outVal;

          cashInSeries[name].push(inVal);
          cashOutSeries[name].push(outVal);
        });
      });

      const chartOptions = {
        chart: {
          type: 'bar',
          stacked: true,
          height: 400,
          toolbar: {
            show: true
          },
        },
        xaxis: {
          categories: categories
        },
        tooltip: {
          intersect: false,
          shared: true,
          y: {
            formatter: formatCurrency
          },
        },
        yaxis: {
          labels: {
            formatter: formatCurrency
          },
        },
        plotOptions: {
          bar: {
            horizontal: false,
            borderRadius: 4,
          },
        },
        colors: ['#4CAF50', '#2E7D32', '#F44336', '#C62828', '#FF9800', '#6A1B9A'],
        series: [{
            name: 'Transaksi Tunai',
            data: cashInSeries.transactions
          },
          {
            name: 'Pembayaran Kredit',
            data: cashInSeries.credit_payments
          },
          {
            name: 'Pembelian Tunai',
            data: cashOutSeries.purchases
          },
          {
            name: 'Pelunasan Kredit',
            data: cashOutSeries.credit_purchases
          },
          {
            name: 'Retur Pelanggan',
            data: cashOutSeries.customer_returns
          },
          {
            name: 'Retur Supplier',
            data: cashInSeries.supplier_returns
          },
        ],
      };

      document.querySelector('#total-in').textContent = formatCurrency(totalIn);
      document.querySelector('#total-out').textContent = formatCurrency(totalOut);
      document.querySelector('#net-income').textContent = formatCurrency(totalIn + totalOut);

      if (window.cashflowChart) {
        window.cashflowChart.destroy();
      }
      window.cashflowChart = new ApexCharts(
        document.querySelector('#cashflow-chart'),
        chartOptions
      );
      window.cashflowChart.render();
    }

    function renderTablePage() {
      const startIdx = (currentPage - 1) * rowsPerPage;
      const endIdx = startIdx + rowsPerPage;
      const pageData = activitiesData.slice(startIdx, endIdx);

      const $tbody = $('#activitiesTableBody');
      $tbody.empty();

      if (pageData.length === 0) {
        $tbody.append(`
          <tr>
            <td colspan="4" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
              Tidak ada aktivitas untuk ditampilkan.
            </td>
          </tr>
        `);
      } else {
        pageData.forEach(item => {
          const date = item.date; 
          const type = item.type; 
          const amount = formatCurrency(parseFloat(item.amount));
          const direction =
            item.direction === 'in' ?
            '<span class="text-green-600 dark:text-green-400">Masuk</span>' :
            '<span class="text-red-600 dark:text-red-400">Keluar</span>';

          $tbody.append(`
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
              <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">${date}</td>
              <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">${type}</td>
              <td class="px-4 py-2 whitespace-nowrap text-sm text-right text-gray-700 dark:text-gray-200">${amount}</td>
              <td class="px-4 py-2 whitespace-nowrap text-center text-sm">${direction}</td>
            </tr>
          `);
        });
      }

      renderPaginationControls();
    }


    function renderPaginationControls() {
      const totalRows = activitiesData.length;
      const totalPages = Math.ceil(totalRows / rowsPerPage);
      const $container = $('#tablePagination');
      $container.empty();

      if (totalPages <= 1) {
        return; 
      }

      // Previous button
      const prevDisabled = currentPage === 1 ? 'opacity-50 cursor-not-allowed' : '';
      $container.append(`
        <button
          id="prevPage"
          class="px-2 py-1 rounded border dark:text-white text-gray-500 border-gray-300 dark:border-gray-700 ${
            prevDisabled
          }"
          ${currentPage === 1 ? 'disabled' : ''}
        >
          Previous
        </button>
      `);

 
      let startPage = Math.max(1, currentPage - 2);
      let endPage = Math.min(totalPages, startPage + 4);
      if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
      }

      for (let p = startPage; p <= endPage; p++) {
        const activeClass = p === currentPage ?
          'bg-blue-600 text-white' :
          'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800';
        $container.append(`
          <button
            class="mx-1 px-2 py-1 rounded border border-gray-300 dark:border-gray-700 ${activeClass}"
            data-page="${p}"
          >
            ${p}
          </button>
        `);
      }

      const nextDisabled =
        currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : '';
      $container.append(`
        <button
          id="nextPage"
          class="px-2 py-1 rounded border dark:text-white text-gray-500 border-gray-300 dark:border-gray-700 ${
            nextDisabled
          }"
          ${currentPage === totalPages ? 'disabled' : ''}
        >
          Next
        </button>
      `);
    }

    $(document).on('change', '#rowsPerPage', function() {
      rowsPerPage = parseInt($(this).val());
      currentPage = 1;
      renderTablePage();
    });

    $(document).on('click', '#tablePagination button[data-page]', function() {
      const newPage = parseInt($(this).data('page'));
      if (newPage !== currentPage) {
        currentPage = newPage;
        renderTablePage();
      }
    });

    $(document).on('click', '#prevPage', function() {
      if (currentPage > 1) {
        currentPage--;
        renderTablePage();
      }
    });
    $(document).on('click', '#nextPage', function() {
      const totalPages = Math.ceil(activitiesData.length / rowsPerPage);
      if (currentPage < totalPages) {
        currentPage++;
        renderTablePage();
      }
    });

    function fetchData(start = '', end = '', activity = 'all', cash_type = 'all') {
      $.ajax({
        url: "{{ route('reports.salesIndex') }}",
        data: {
          start,
          end,
          activity,
          cash_type,
          format: 'json'
        },
        success: function(response) {
          renderChart(response.data, response.group_by);

          activitiesData = Array.isArray(response.activities) ?
            response.activities :
            [];

          currentPage = 1;
          rowsPerPage = parseInt($('#rowsPerPage').val());
          renderTablePage();
        },
        error: function(xhr) {
          if (xhr.status === 422) {
            alert(xhr.responseJSON.error);
          } else {
            alert('Terjadi kesalahan saat mengambil data.');
          }
        },
        complete: function() {
          $('#loading-overlay').addClass('hidden');
        }
      });
    }

    // Range selector logic (same as before)
    $('#range').on('change', function() {
      const val = $(this).val();
      const today = new Date().toLocaleDateString('sv-SE', {
        timeZone: 'Asia/Jayapura'
      });

      $('#start, #end').addClass('hidden');

      switch (val) {
        case 'today':
          $('#start').val(today);
          $('#end').val(today);
          break;
        case '7days':
          $('#start').val(new Date(Date.now() - 6 * 864e5).toISOString().split('T')[0]);
          $('#end').val(today);
          break;
        case '1month':
          $('#start').val(new Date(new Date().setMonth(new Date().getMonth() - 1)).toISOString().split('T')[0]);
          $('#end').val(today);
          break;
        case '1year':
          $('#start').val(new Date(new Date().setFullYear(new Date().getFullYear() - 1)).toISOString().split('T')[0]);
          $('#end').val(today);
          break;
        case 'all':
          $('#start').val('');
          $('#end').val('');
          break;
        case 'custom':
          $('#start, #end').removeClass('hidden');
          break;
      }
    });


    $('#filter').on('click', function() {
      const start = $('#start').val();
      const end = $('#end').val();
      const activity = $('#activity').val();
      const cash_type = $('#cash_type').val();

      $('#loading-overlay').removeClass('hidden');
      fetchData(start, end, activity, cash_type);
    });


    $('#exportButton').on('click', () => {
      $('#exportModal').removeClass('hidden');
    });
    $('#cancelExport').on('click', () => {
      $('#exportModal').addClass('hidden');
    });

    $(document).ready(function() {
      $('#range').trigger('change');
      $('#filter').trigger('click');
    });
  </script>
</x-layout>