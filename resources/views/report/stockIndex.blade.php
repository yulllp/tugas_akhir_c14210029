<x-layout>
  <h2 class="mx-auto max-w-screen-xl mb-8 text-2xl font-extrabold leading-none tracking-tight text-gray-900 md:text-3xl dark:text-white">
    Laporan Stok
  </h2>
  <div
    id="exportModal"
    class="fixed inset-0 backdrop-blur-sm dark:bg-gray-900 dark:bg-opacity-70 flex items-center justify-center hidden z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-md p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Export Laporan Stok
        </h3>
        <button
          id="closeExportModal"
          class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 text-2xl leading-none">
          &times;
        </button>
      </div>

      <form action="{{ route('report.stock.export') }}" method="POST" class="space-y-4">
        @csrf
        <div class="flex flex-wrap gap-4">
          <div class="w-full md:w-1/2">
            <label for="start" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Start Date
            </label>
            <input
              type="date"
              name="start"
              id="start"
              value="{{ old('start', now()->startOfMonth()->toDateString()) }}"
              required
              class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div class="w-full md:w-1/2">
            <label for="end" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              End Date
            </label>
            <input
              type="date"
              name="end"
              id="end"
              value="{{ old('end', now()->endOfMonth()->toDateString()) }}"
              required
              class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
        </div>

        <div class="flex justify-end">
          <button
            type="submit"
            class="inline-flex items-center px-6 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400 focus:outline-none">
            Download Excel
          </button>
        </div>
      </form>
    </div>
  </div>
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
      <p class="text-sm text-gray-700 dark:text-gray-300">Memuat data...</p>
    </div>
  </div>

  <div class="max-w-7xl mx-auto space-y-10">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
      <h2 class="text-2xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Produk Terlaris</h2>

      <div class="flex flex-wrap gap-6 mb-6">
        <div>
          <label for="top-limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teratas</label>
          <select id="top-limit" class="mt-1 block w-32 rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="5" selected>5 Teratas</option>
            <option value="10">10 Teratas</option>
            <option value="all">Semua</option>
          </select>
        </div>
        <div>
          <label for="top-range" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rentang Waktu</label>
          <select id="top-range" class="mt-1 block w-48 rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="all_time" selected>Sepanjang Waktu</option>
            <option value="this_week">Minggu Ini</option>
            <option value="this_month">Bulan Ini</option>
            <option value="this_year">Tahun Ini</option>
          </select>
        </div>
        <div class="self-end">
          <button id="btn-refresh-top" class="px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white rounded-md hover:bg-indigo-700 dark:hover:bg-indigo-400">
            Terapkan
          </button>
        </div>
      </div>
      <div id="chart-top-container" class="mb-6">
        <div id="chart-top" class="w-full h-64"></div>
      </div>
      <div>
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <label for="topRowsPerPage">Show</label>
            <select id="topRowsPerPage" class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-1 min-w-[4rem] text-sm shadow-sm focus:ring focus:ring-blue-200 dark:focus:ring-blue-800">
              <option value="5" selected>5</option>
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
            </select>
            <span>entries</span>
          </div>
          <div id="topTablePagination" class="flex items-center space-x-1 text-sm"></div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 table-auto">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rank</th>
                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Produk</th>
                <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah Terjual</th>
              </tr>
            </thead>
            <tbody id="tbody-top" class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
      <div class="flex justify-between">
        <h2 class="text-2xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Pergerakan Stok Produk</h2>
        <button
          id="openExportModal"
          class="px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white rounded-md hover:bg-indigo-700 dark:hover:bg-indigo-400 focus:outline-none">
          Download Excel
        </button>
      </div>

      <div class="flex flex-wrap gap-6 mb-6">
        <div>
          <label for="prod-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Produk</label>
          <select id="prod-select" class="mt-1 block w-64 rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
            @foreach(\App\Models\Product::orderBy('name')->get() as $p)
            <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="mov-range" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rentang Waktu</label>
          <select id="mov-range" class="mt-1 block w-48 rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="today" selected>Hari Ini</option>
            <option value="this_week">Minggu Ini</option>
            <option value="this_month">Bulan Ini</option>
            <option value="this_year">Tahun Ini</option>
            <option value="all_time">Sepanjang Waktu</option>
            <option value="custom">Kustom</option>
          </select>
        </div>
        <div id="custom-dates" class="hidden flex gap-4">
          <div>
            <label for="mov-start" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mulai</label>
            <input type="date" id="mov-start" class="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div>
            <label for="mov-end" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Akhir</label>
            <input type="date" id="mov-end" class="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
          </div>
        </div>
        <div class="self-end">
          <button id="btn-refresh-mov" class="px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white rounded-md hover:bg-indigo-700 dark:hover:bg-indigo-400">
            Terapkan
          </button>
        </div>
      </div>
      <div id="mov-summary" class="flex flex-wrap gap-6 mb-6">
        <div>
          <span class="text-gray-700 dark:text-gray-300">Sebelum: </span>
          <strong id="stock-before" class="text-gray-900 dark:text-gray-100">0</strong>
        </div>
        <div>
          <span class="text-gray-700 dark:text-gray-300">Perubahan: </span>
          <strong id="stock-change" class="text-gray-900 dark:text-gray-100">0</strong>
        </div>
        <div>
          <span class="text-gray-700 dark:text-gray-300">Akhir: </span>
          <strong id="stock-last" class="text-gray-900 dark:text-gray-100">0</strong>
        </div>
      </div>

      <div id="chart-mov-container" class="mb-6">
        <div id="chart-mov" class="w-full h-64"></div>
      </div>

      <div>
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <label for="movRowsPerPage">Show</label>
            <select id="movRowsPerPage" class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-1 min-w-[4rem] text-sm shadow-sm focus:ring focus:ring-blue-200 dark:focus:ring-blue-800">
              <option value="5" selected>5</option>
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
            </select>
            <span>entries</span>
          </div>
          <div id="movTablePagination" class="flex items-center space-x-1 text-sm"></div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 table-auto">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipe</th>
                <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah</th>
                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Deskripsi</th>
              </tr>
            </thead>
            <tbody id="tbody-mov" class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">

            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    $(document).ready(function() {
      const $modal = $('#exportModal');
      $('#openExportModal').on('click', function() {
        $modal.removeClass('hidden');
      });
      $('#closeExportModal').on('click', function() {
        $modal.addClass('hidden');
      });

      function showLoading() {
        $('#loading-overlay').removeClass('hidden');
      }

      function hideLoading() {
        $('#loading-overlay').addClass('hidden');
      }

      let chartTopOptions = {
        chart: {
          type: 'bar',
          height: 300,
          toolbar: {
            show: false
          }
        },
        series: [{
          name: 'Jumlah Terjual',
          data: []
        }],
        xaxis: {
          categories: []
        },
        theme: {
          mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
        }
      };
      let chartTop = new ApexCharts(document.querySelector("#chart-top"), chartTopOptions);
      chartTop.render();

      let chartMovOptions = {
        chart: {
          type: 'bar',
          height: 300,
          stacked: true,
          toolbar: {
            show: false
          }
        },
        series: [],
        xaxis: {
          categories: []
        },
        tooltip: {
          shared: true,
          intersect: false
        },
        theme: {
          mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
        }
      };
      let chartMov = new ApexCharts(document.querySelector("#chart-mov"), chartMovOptions);
      chartMov.render();

      let topData = [];
      let topCurrentPage = 1;

      function renderTopTable() {
        const rowsPerPage = parseInt($('#topRowsPerPage').val());
        const start = (topCurrentPage - 1) * rowsPerPage;
        const paginated = topData.slice(start, start + rowsPerPage);

        $('#tbody-top').empty();
        paginated.forEach((item, idx) => {
          let rank = start + idx + 1;
          let badge = '';
          if (rank === 1) badge = `<span class="inline-block bg-yellow-400 text-white text-xs font-semibold px-2 py-1 rounded-full">1st</span>`;
          else if (rank === 2) badge = `<span class="inline-block bg-gray-400 text-white text-xs font-semibold px-2 py-1 rounded-full">2nd</span>`;
          else if (rank === 3) badge = `<span class="inline-block bg-yellow-800 text-white text-xs font-semibold px-2 py-1 rounded-full">3rd</span>`;
          else badge = `<span class="text-gray-700 dark:text-gray-300">${rank}</span>`;

          let row = `
            <tr>
              <td class="px-4 py-2">${badge}</td>
              <td class="px-4 py-2 text-gray-700 dark:text-gray-200">${item.name}</td>
              <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-200">${item.net_sold}</td>
            </tr>`;
          $('#tbody-top').append(row);
        });
        renderTopPaginationControls();
      }

      function renderTopPaginationControls() {
        const rowsPerPage = parseInt($('#topRowsPerPage').val());
        const totalRows = topData.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        const $container = $('#topTablePagination');
        $container.empty();
        if (totalPages <= 1) return;

        // Previous button
        const prevDisabled = topCurrentPage === 1 ? 'opacity-50 cursor-not-allowed' : '';
        $container.append(`
          <button
            id="prevTopPage"
            class="px-2 py-1 rounded border dark:text-white text-gray-500 border-gray-300 dark:border-gray-700 ${prevDisabled}"
            ${topCurrentPage === 1 ? 'disabled' : ''}>
            Previous
          </button>
        `);

        let startPage = Math.max(1, topCurrentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4) {
          startPage = Math.max(1, endPage - 4);
        }

        for (let p = startPage; p <= endPage; p++) {
          const activeClass = p === topCurrentPage ?
            'bg-blue-600 text-white' :
            'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800';
          $container.append(`
            <button
              class="mx-1 px-2 py-1 rounded border border-gray-300 dark:border-gray-700 ${activeClass}"
              data-page="${p}">
              ${p}
            </button>
          `);
        }

        const nextDisabled = topCurrentPage === totalPages ? 'opacity-50 cursor-not-allowed' : '';
        $container.append(`
          <button
            id="nextTopPage"
            class="px-2 py-1 rounded border dark:text-white text-gray-500 border-gray-300 dark:border-gray-700 ${nextDisabled}"
            ${topCurrentPage === totalPages ? 'disabled' : ''}>
            Next
          </button>
        `);

        $('#prevTopPage').off('click').on('click', () => {
          if (topCurrentPage > 1) {
            topCurrentPage--;
            renderTopTable();
          }
        });
        $('#nextTopPage').off('click').on('click', () => {
          if (topCurrentPage < totalPages) {
            topCurrentPage++;
            renderTopTable();
          }
        });
        $container.find('button[data-page]').off('click').on('click', function() {
          const p = parseInt($(this).data('page'));
          topCurrentPage = p;
          renderTopTable();
        });
      }

      let movData = [];
      let movCurrentPage = 1;

      function renderMovTable() {
        const rowsPerPage = parseInt($('#movRowsPerPage').val());
        const start = (movCurrentPage - 1) * rowsPerPage;
        const paginated = movData.slice(start, start + rowsPerPage);

        $('#tbody-mov').empty();
        if (paginated.length === 0) {
          $('#tbody-mov').append(`
      <tr>
        <td colspan="4" class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">
          Tidak ada data
        </td>
      </tr>
    `);
        } else {
          paginated.forEach(item => {
            let colorClass = item.qty < 0 ? 'text-red-600' : 'text-green-600';
            let row = `
        <tr>
          <td class="px-4 py-2 text-gray-700 dark:text-gray-200">${item.date}</td>
          <td class="px-4 py-2 text-gray-700 dark:text-gray-200">${item.type}</td>
          <td class="px-4 py-2 text-right ${colorClass}">${item.qty}</td>
          <td class="px-4 py-2 text-gray-700 dark:text-gray-200">${item.description}</td>
        </tr>`;
            $('#tbody-mov').append(row);
          });
        }
        renderMovPaginationControls();
      }

      function renderMovPaginationControls() {
        const rowsPerPage = parseInt($('#movRowsPerPage').val());
        const totalRows = movData.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        const $container = $('#movTablePagination');
        $container.empty();
        if (totalPages <= 1) return;

        // Previous button
        const prevDisabled = movCurrentPage === 1 ? 'opacity-50 cursor-not-allowed' : '';
        $container.append(`
          <button
            id="prevMovPage"
            class="px-2 py-1 rounded border dark:text-white text-gray-500 border-gray-300 dark:border-gray-700 ${prevDisabled}"
            ${movCurrentPage === 1 ? 'disabled' : ''}>
            Previous
          </button>
        `);

        let startPage = Math.max(1, movCurrentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4) {
          startPage = Math.max(1, endPage - 4);
        }

        for (let p = startPage; p <= endPage; p++) {
          const activeClass = p === movCurrentPage ?
            'bg-blue-600 text-white' :
            'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800';
          $container.append(`
            <button
              class="mx-1 px-2 py-1 rounded border border-gray-300 dark:border-gray-700 ${activeClass}"
              data-page="${p}">
              ${p}
            </button>
          `);
        }

        const nextDisabled = movCurrentPage === totalPages ? 'opacity-50 cursor-not-allowed' : '';
        $container.append(`
          <button
            id="nextMovPage"
            class="px-2 py-1 rounded border dark:text-white text-gray-500 border-gray-300 dark:border-gray-700 ${nextDisabled}"
            ${movCurrentPage === totalPages ? 'disabled' : ''}>
            Next
          </button>
        `);

        $('#prevMovPage').off('click').on('click', () => {
          if (movCurrentPage > 1) {
            movCurrentPage--;
            renderMovTable();
          }
        });
        $('#nextMovPage').off('click').on('click', () => {
          if (movCurrentPage < totalPages) {
            movCurrentPage++;
            renderMovTable();
          }
        });
        $container.find('button[data-page]').off('click').on('click', function() {
          const p = parseInt($(this).data('page'));
          movCurrentPage = p;
          renderMovTable();
        });
      }

      function fetchTopSelling() {
        showLoading();
        let limit = $('#top-limit').val();
        let range = $('#top-range').val();

        $.ajax({
          url: "{{ route('stats.topSelling') }}",
          data: {
            limit: limit,
            range: range
          },
          method: 'GET',
          success: function(data) {
            topData = data.map(item => ({
              name: item.name,
              net_sold: item.net_sold
            }));
            chartTop.updateOptions({
              xaxis: {
                categories: data.map(item => item.name)
              },
              series: [{
                name: 'Jumlah Terjual',
                data: data.map(item => item.net_sold)
              }]
            });
            topCurrentPage = 1;
            renderTopTable();
          },
          error: function(xhr, status, error) {
            console.error('Error fetchTopSelling:', error);
            topData = [];
            renderTopTable();
          },
          complete: function() {
            hideLoading();
          }
        });
      }

      function fetchProductMovement() {
        showLoading();
        let prodId = $('#prod-select').val();
        let range = $('#mov-range').val();
        let params = {
          product_id: prodId,
          range: range
        };

        if (range === 'custom') {
          params.start = $('#mov-start').val();
          params.end = $('#mov-end').val();
        }

        $.ajax({
          url: "{{ route('stats.productMovement') }}",
          data: params,
          method: 'GET',
          success: function(response) {
            let stockBefore = response.stock_before;
            let stockChange = response.stock_change;
            let stockLast = response.stock_last;
            $('#stock-before').text(stockBefore);
            $('#stock-change').text(stockChange);
            $('#stock-last').text(stockLast);

            let movements = response.movements;
            if (!movements || movements.length === 0) {
              // Tidak ada aktivitas: kosongkan chart dan table
              chartMov.updateOptions({
                series: [],
                xaxis: {
                  categories: []
                }
              });
              movData = [];
              renderMovTable();
              return;
            }

            let rawDateTimes = movements.map(item => item.date);
            let allDatesOnly = rawDateTimes.map(dt => dt.split(' ')[0]);

            let uniqueDates = Array.from(new Set(allDatesOnly));
            uniqueDates.sort((a, b) => {
              // a, b = "dd-mm-YYYY"
              let pa = a.split('-'),
                pb = b.split('-');
              let da = new Date(pa[2], pa[1] - 1, pa[0]);
              let db = new Date(pb[2], pb[1] - 1, pb[0]);
              return da - db;
            });

            let types = Array.from(new Set(movements.map(item => item.type)));

            let mapByDay = {};
            uniqueDates.forEach(dateOnly => {
              mapByDay[dateOnly] = {};
              types.forEach(t => {
                mapByDay[dateOnly][t] = 0;
              });
            });

            movements.forEach(item => {
              let dateOnly = item.date.split(' ')[0];

              if (mapByDay[dateOnly] && typeof mapByDay[dateOnly][item.type] !== 'undefined') {
                mapByDay[dateOnly][item.type] += item.qty;
              }
            });

            let series = types.map(t => ({
              name: t,
              data: uniqueDates.map(d => mapByDay[d][t])
            }));

            chartMov.updateOptions({
              xaxis: {
                categories: uniqueDates
              },
              series: series
            });

            movData = movements.map(item => ({
              date: item.date,
              type: item.type,
              qty: item.qty,
              description: item.description
            }));
            movCurrentPage = 1;
            renderMovTable();
          },
          error: function(xhr, status, error) {
            console.error('Error fetchProductMovement:', error);
            chartMov.updateOptions({
              series: [],
              xaxis: {
                categories: []
              }
            });
            movData = [];
            renderMovTable();
          },
          complete: function() {
            hideLoading();
          }
        });
      }


      function formatTanggalIndonesia(dateString) {
        let dt = new Date(dateString);
        return dt.toLocaleDateString('id-ID', {
          day: 'numeric',
          month: 'long',
          year: 'numeric'
        });
      }


      $('#mov-range').on('change', function() {
        if ($(this).val() === 'custom') {
          $('#custom-dates').removeClass('hidden');
        } else {
          $('#custom-dates').addClass('hidden');
        }
      });
      $('#topRowsPerPage').on('change', () => {
        topCurrentPage = 1;
        renderTopTable();
      });
      $('#movRowsPerPage').on('change', () => {
        movCurrentPage = 1;
        renderMovTable();
      });
      $('#btn-refresh-top').on('click', fetchTopSelling);
      $('#btn-refresh-mov').on('click', fetchProductMovement);

      fetchTopSelling();
      fetchProductMovement();
    });
  </script>
</x-layout>