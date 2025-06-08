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
          $forecastNext=array_map(fn($v) => (int) round($v), $realForecast['forecast']);;
          @endphp

          <script>
          document.addEventListener('DOMContentLoaded', function() {
          new ApexCharts(document.querySelector("#chart-forecast"), {
          chart: { type: 'line', height: 300, toolbar: { show: false } },
          series: [
          { name: 'Forecast', data: @json($forecastNext) }
          ],
          xaxis: { categories: @json($labelsNext), labels:{ rotate:-45 } },
          stroke: { width:[2], curve:'smooth' },
          markers:{ size:[4] },
          tooltip:{ shared:true },
          legend:{ show:false }
          }).render();
          });
          </script>
      </div>
      @endif

    </div>
  </div>
</x-layout>