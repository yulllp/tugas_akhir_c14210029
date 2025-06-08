<?php
namespace App\Helpers;

class HoltWinters
{
    /**
     * multiplicative Holt-Winters smoothing + one‐year forecasting
     *
     * @param array $data         1‐D array of historical data (index 0..N-1)
     * @param int   $seasonLength length of one season (12 for monthly data yearly)
     * @param float $alpha        smoothing for level
     * @param float $beta         smoothing for trend
     * @param float $gamma        smoothing for season
     * @return array { 
     *     'fitted'   => array of length N (fitted values on training), 
     *     'forecast' => array of length seasonLength (the next 12 forecasts), 
     *     'level'    => final level, 
     *     'trend'    => final trend, 
     *     'season'   => seasonal indices array 
     * }
     */
    public static function multiplicative(
        array $data, 
        int   $seasonLength = 12, 
        float $alpha = 0.2, 
        float $beta  = 0.01, 
        float $gamma = 0.01
    ): array {
        $n = count($data);
        if ($n < 2*$seasonLength) {
            throw new \Exception("Need at least 2 * seasonLength data points to initialize.");
        }

        // ─── 1) INITIALIZE LEVEL AND TREND ───────────────────────────────────────────
        // initial season averages (two seasons)
        $seasonAvg1 = array_sum(array_slice($data,           0, $seasonLength)) / $seasonLength;
        $seasonAvg2 = array_sum(array_slice($data, $seasonLength, $seasonLength)) / $seasonLength;
        $initialTrend = ($seasonAvg2 - $seasonAvg1) / $seasonLength;
        $initialLevel = $data[0];

        // build an “index” for first 2*seasonLength so we can derive initial seasonal indices
        $index = [];
        for ($i = 0; $i < 2*$seasonLength; $i++) {
            $index[$i] = $data[$i] / ($initialLevel + ($i + 1) * $initialTrend);
        }

        // initial season array size = n + seasonLength (so we can index into season[i + seasonLength])
        $season = array_fill(0, $n + $seasonLength, 0.0);
        for ($i = 0; $i < $seasonLength; $i++) {
            // average of the two index points for this season position
            $season[$i] = ($index[$i] + $index[$i + $seasonLength]) / 2.0;
        }
        // Normalize season so they average to 1
        $sumSeason = array_sum(array_slice($season, 0, $seasonLength));
        $seasonFactor = $seasonLength / $sumSeason;
        for ($i = 0; $i < $seasonLength; $i++) {
            $season[$i] *= $seasonFactor;
        }

        // ─── 2) RUN THROUGH DATA TO COMPUTE LEVEL, TREND, SEASON, FITTED ────────────
        $fitted = array_fill(0, $n, 0.0);
        $level  = $initialLevel;
        $trend  = $initialTrend;

        for ($t = 0; $t < $n; $t++) {
            $prevLevel = $level;
            $prevTrend = $trend;
            $prevSeason = $season[$t];

            // 2a) update level
            $level = $alpha * ($data[$t] / $prevSeason)
                   + (1 - $alpha) * ($prevLevel + $prevTrend);

            // 2b) update trend
            $trend = $beta * ($level - $prevLevel) 
                   + (1 - $beta) * $prevTrend;

            // 2c) update season for next year at position t + seasonLength
            $season[$t + $seasonLength] = 
                  $gamma * ($data[$t] / $level)
                + (1 - $gamma) * $prevSeason;

            // 2d) fitted value for time t
            $fitted[$t] = ($level + $trend) * $prevSeason;
        }

        // ─── 3) FORECAST NEXT seasonLength POINTS ───────────────────────────────────
        $forecast = array_fill(0, $seasonLength, 0.0);
        for ($m = 1; $m <= $seasonLength; $m++) {
            // point at time n + m: (level + m*trend) * season[n + (m - seasonLength)]
            $seasonIndex = $season[$n + ($m - $seasonLength)];
            $forecast[$m - 1] = ($level + $m * $trend) * $seasonIndex;
        }

        return [
            'fitted'   => $fitted,
            'forecast' => $forecast,
            'level'    => $level,
            'trend'    => $trend,
            'season'   => $season, 
        ];
    }


    /**
     * Run a grid search over alpha, beta, gamma to minimize MAPE on a hold‐out test set.
     *
     * @param array $trainData   length‐24 training array
     * @param array $testData    length‐12 test array
     * @param array $alphaGrid   e.g. [0.1, 0.2, 0.3, …]
     * @param array $betaGrid    e.g. [0.01, 0.05, 0.1, …]
     * @param array $gammaGrid   e.g. [0.01, 0.05, …]
     * @param int   $seasonLength (12)
     * @return array {
     *   'alpha'  => best α,
     *   'beta'   => best β,
     *   'gamma'  => best γ,
     *   'mape'   => best MAPE,
     *   'mae'    => MAE using that triple,
     *   'forecast' => array[12] (the 12 forecasts for the test set)
     * }
     */
    public static function gridSearch(
        array $trainData,
        array $testData,
        array $alphaGrid,
        array $betaGrid,
        array $gammaGrid,
        int   $seasonLength = 12
    ): array {
        $bestMAPE = INF;
        $bestMAE  = INF;
        $bestTriple = ['α'=>0, 'β'=>0, 'γ'=>0];
        $bestForecast = [];

        foreach ($alphaGrid as $alpha) {
            foreach ($betaGrid as $beta) {
                foreach ($gammaGrid as $gamma) {
                    // Fit on train
                    $hwResult = self::multiplicative($trainData, $seasonLength, $alpha, $beta, $gamma);
                    $forecast12 = $hwResult['forecast']; // array of length 12

                    // Compute MAE, MAPE on testData
                    list($mae, $mape) = self::computeErrors($testData, $forecast12);
                    if ($mape < $bestMAPE) {
                        $bestMAPE = $mape;
                        $bestMAE = $mae;
                        $bestTriple = ['α'=>$alpha, 'β'=>$beta, 'γ'=>$gamma];
                        $bestForecast = $forecast12;
                    }
                }
            }
        }

        return [
            'alpha'    => $bestTriple['α'],
            'beta'     => $bestTriple['β'],
            'gamma'    => $bestTriple['γ'],
            'mape'     => $bestMAPE,
            'mae'      => $bestMAE,
            'forecast' => $bestForecast,
        ];
    }

    /**
     * Compute MAE and MAPE between actual (length N) and forecast (length N).
     *
     * @param array $actual
     * @param array $forecast
     * @return array [MAE, MAPE]
     */
    public static function computeErrors(array $actual, array $forecast): array
    {
        $n = count($actual);
        $absErrors = 0.0;
        $pctErrors = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $ae = abs($actual[$i] - $forecast[$i]);
            $absErrors += $ae;

            // To prevent division by zero, if actual is zero, skip from MAPE (or handle however you want).
            if ($actual[$i] != 0) {
                $pctErrors += abs(($actual[$i] - $forecast[$i]) / $actual[$i]);
            }
        }
        $mae  = $absErrors / $n;
        $mape = ($pctErrors / $n) * 100.0;
        return [$mae, $mape];
    }
}
