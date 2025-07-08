{{-- resources/views/forecast/index.blade.php --}}
<x-layout>
  <h2 class="mx-auto max-w-screen-xl mb-8 text-2xl font-extrabold leading-none tracking-tight text-gray-900 md:text-3xl dark:text-white">
    Prediksi
  </h2>
  <div class="text-gray-900 dark:text-gray-100">
    <div class="mx-auto space-y-6">

      <form method="GET" action="{{ route('forecast.index') }}" class="space-y-4 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <label for="product_id" class="block text-lg font-medium">Pilih Produk:</label>
        <div class="flex gap-4">
          <select id="product_id" name="product_id"
            class="flex-1 py-2 px-3 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800
                         focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">-- Pilih Salah Satu --</option>
            @php $selectedProductId = request('product_id'); @endphp
            @foreach($products as $p)
            <option value="{{ $p->id }}" @if($selectedProductId==$p->id) selected @endif>
              {{ $p->name }}
            </option>
            @endforeach
          </select>
          <button type="submit"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
            Tampilkan
          </button>
        </div>
      </form>

      @if(isset($trainingResults['error']))
      <div class="bg-red-100 dark:bg-red-800 border border-red-200 dark:border-red-700
                    text-red-800 dark:text-red-200 p-4 rounded">
        {{ $trainingResults['error'] }}
      </div>
      @endif

      {{-- ──────────────── Section 2: Validation & Test Chart ──────────────── --}}
      @if($trainingResults && !isset($trainingResults['error']))
      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-6">
        <!-- Metrics -->
        <div class="grid grid-cols-2 gap-4">
          <div>Produk: <strong>{{ $trainingResults['product_name'] }}</strong></div>
          <div>Test selesai di tahun: <strong>{{ $trainingResults['validationYear'] }}</strong></div>
          <div>α (Alpha): <strong>{{ $trainingResults['alpha'] }}</strong></div>
          <div>β (Beta): <strong>{{ $trainingResults['beta'] }}</strong></div>
          <div>γ (Gamma): <strong>{{ $trainingResults['gamma'] }}</strong></div>
          <div>MAE: <strong>{{ number_format($trainingResults['mae'], 2) }}</strong></div>
          <div class="col-span-2">MAPE: <strong>{{ number_format($trainingResults['mape'], 2) }}%</strong></div>
        </div>

        <!-- Test-window Line Chart -->
        <div id="chart-validation"></div>

        @php
        $labelsTest = $trainingResults['testLabels'];
        $actualTest = $trainingResults['testSeries'];
        $forecastTest = array_map(fn($v) => (int) round($v), $trainingResults['testForecast']);;
        @endphp

        <script>
          document.addEventListener('DOMContentLoaded', function() {
            new ApexCharts(document.querySelector("#chart-validation"), {
              chart: {
                type: 'line',
                height: 300,
                toolbar: {
                  show: false
                }
              },
              series: [{
                  name: 'Aktual',
                  data: @json($actualTest)
                },
                {
                  name: 'Forecast',
                  data: @json($forecastTest)
                }
              ],
              xaxis: {
                categories: @json($labelsTest),
                labels: {
                  rotate: -45
                }
              },
              stroke: {
                width: [2, 2],
                curve: 'smooth'
              },
              markers: {
                size: [4, 4]
              },
              tooltip: {
                shared: true
              },
              legend: {
                position: 'top',
                horizontalAlign: 'right'
              }
            }).render();
          });
        </script>
      </div>
      @endif

      @if($realForecast)
      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-4">
        <h3 class="text-xl font-semibold">
          Real Forecast untuk Tahun {{ $realForecast['year'] }}
        </h3>
        <div id="chart-forecast"></div>

        @php
        $yearNext = $realForecast['year'];
        $labelsNext = [];
        for ($m = 1; $m <= 12; $m++) {
          $labelsNext[]=date('M Y', mktime(0,0,0,$m,1,$yearNext));
          }
          $forecastNext=array_map(fn($v)=> (int) round($v), $realForecast['forecast']);;
          @endphp

          <script>
            document.addEventListener('DOMContentLoaded', function() {
              new ApexCharts(document.querySelector("#chart-forecast"), {
                chart: {
                  type: 'line',
                  height: 300,
                  toolbar: {
                    show: false
                  }
                },
                series: [{
                  name: 'Forecast',
                  data: @json($forecastNext)
                }],
                xaxis: {
                  categories: @json($labelsNext),
                  labels: {
                    rotate: -45
                  }
                },
                stroke: {
                  width: [2],
                  curve: 'smooth'
                },
                markers: {
                  size: [4]
                },
                tooltip: {
                  shared: true
                },
                legend: {
                  show: false
                }
              }).render();
            });
          </script>
      </div>
      @endif

      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mt-8">
        <h3 class="text-xl font-semibold mb-4">Saran Stok Produk</h3>

        <form method="GET" action="{{ route('forecast.index') }}" class="inline-flex items-center mb-4 gap-4">
          <label for="suggestion_month" class="mr-2">Bulan Rencana:</label>
          @php $cy = now()->year; @endphp
          <input type="month"
            id="suggestion_month"
            name="suggestion_month"
            value="{{ $suggestionMonth }}"
            min="{{ $cy }}-01"
            max="{{ $cy }}-12"
            class="py-1 px-2 border …" />
          <label for="suggestion_type">Tampilkan:</label>
          <select name="suggestion_type" id="suggestion_type"
            class="py-1 px-2 min-w-[8rem] border rounded">
            <option value="all" @if($suggestionType=='all' ) selected @endif>Semua</option>
            <option value="up" @if($suggestionType=='up' ) selected @endif>Naik</option>
            <option value="down" @if($suggestionType=='down' )selected @endif>Turun</option>
          </select>
          <button type="submit" class="ml-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
            Tampilkan
          </button>
        </form>

        <table class="w-full table-auto border-collapse">
          <thead>
            <tr class="bg-gray-100 dark:bg-gray-700">
              <th class="px-4 py-2 text-left">Produk</th>
              <th class="px-4 py-2 text-left">Stok Saat Ini</th> 
              <th class="px-4 py-2 text-left">Penjualan Sebelumnya</th>
              <th class="px-4 py-2 text-left">Prediksi</th>
              <th class="px-4 py-2 text-left">Saran</th>
            </tr>
          </thead>
          <tbody>
            @foreach($suggestions as $s)
            <tr class="border-t border-gray-200 dark:border-gray-700">
              <!-- Produk -->
              <td class="px-4 py-2">{{ $s['name'] }}</td>

              <td class="px-4 py-2">{{ $s['current_stock'] }}</td>

              <!-- 2. Penjualan Sebelumnya -->
              <td class="px-4 py-2">
                @if($s['is_actual'])
                <span class="text-blue-600 font-medium">{{ $s['sales'] }}</span>
                @else
                @if(! $s['has_forecast'])
                <span class="text-yellow-600 font-semibold">⚠️ Tidak tersedia</span>
                @else
                <span class="text-orange-600 font-medium">{{ $s['sales'] }}</span>
                @endif
                @endif
              </td>

              <!-- 3. Prediksi -->
              <td class="px-4 py-2">
                @if(! $s['has_forecast'])
                <span class="text-yellow-600 font-semibold">⚠️ Tidak tersedia</span>
                @else
                {{ $s['predicted'] }}
                @endif
              </td>

              <!-- 4. Saran -->
              <td class="px-4 py-2 space-x-2">
                @if(! $s['has_forecast'])
                <span class="text-yellow-600 font-semibold">⚠️ Tidak tersedia</span>
                @else
                @if($s['suggestion_up'])
                {{-- ↑ Arrow up --}}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 text-green-600 inline">
                  <path fill-rule="evenodd" d="M15.22 6.268a.75.75 0 0 1 .968-.431l5.942 2.28a.75.75 0 0 1 .431.97l-2.28 5.94a.75.75 0 1 1-1.4-.537l1.63-4.251-1.086.484a11.2 11.2 0 0 0-5.45 5.173.75.75 0 0 1-1.199.19L9 12.312l-6.22 6.22a.75.75 0 0 1-1.06-1.061l6.75-6.75a.75.75 0 0 1 1.06 0l3.606 3.606a12.695 12.695 0 0 1 5.68-4.974l1.086-.483-4.251-1.632a.75.75 0 0 1-.432-.97Z" clip-rule="evenodd" />
                </svg>

                @else
                {{-- ↓ Arrow down --}}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 text-red-600 inline">
                  <path fill-rule="evenodd" d="M1.72 5.47a.75.75 0 0 1 1.06 0L9 11.69l3.756-3.756a.75.75 0 0 1 .985-.066 12.698 12.698 0 0 1 4.575 6.832l.308 1.149 2.277-3.943a.75.75 0 1 1 1.299.75l-3.182 5.51a.75.75 0 0 1-1.025.275l-5.511-3.181a.75.75 0 0 1 .75-1.3l3.943 2.277-.308-1.149a11.194 11.194 0 0 0-3.528-5.617l-3.809 3.81a.75.75 0 0 1-1.06 0L1.72 6.53a.75.75 0 0 1 0-1.061Z" clip-rule="evenodd" />
                </svg>

                @endif
                <span>{{ $s['suggestion_qty'] }}</span>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
          <span class="text-blue-600 font-semibold">Biru</span> = data aktual,
          <span class="text-orange-600 font-semibold">Oranye</span> = data prediksi.
        </p>
      </div>
    </div>
  </div>
</x-layout>