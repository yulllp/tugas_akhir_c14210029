<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Receipt #{{ $data['code'] }}</title>
  <style>
    * {
      font-family: monospace;
      font-size: 10px;
    }

    @page {
      margin: 0;
    }

    .receipt {
      width: 180px;
      margin: 0 auto;
      padding-left: 0px;
      padding-right: 11px;
      /* Add left & right internal margin */
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
    }

    .center {
      text-align: center;
    }

    .bold {
      font-weight: bold;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    td {
      vertical-align: top;
    }

    .right {
      text-align: end;
    }

    .separator {
      border-top: 1px dashed #000;
      margin: 4px 0;
    }
  </style>
</head>

<body>
  <div class="receipt">
    <div class="center bold">TOKO YAMDENA PLAZA</div>
    <div class="center">Jl. BHINEKA No. 5-6, SAUMLAKI</div>

    <div class="separator"></div>

    <table>
      <tr>
        <td>Kode</td>
        <td>: {{ $data['code'] }}</td>
      </tr>
      <tr>
        <td>Tanggal</td>
        <td>: {{ $data['date'] }}</td>
      </tr>
      <tr>
        <td>Kasir</td>
        <td>: {{ $data['cashier'] }}</td>
      </tr>
      <tr>
        <td>Status</td>
        <td>: {{ strtoupper($data['status']) }}</td>
      </tr>
    </table>

    <div class="separator"></div>

    <table>
      @foreach ($data['items'] as $item)
      @php
      $price = (float) str_replace(',', '', $item['price']);
      $disc = (float) str_replace(',', '', $item['disc']);
      $qty = (int) $item['qty'];
      @endphp
      <tr>
        <td colspan="2">{{ $item['name'] }}</td>
      </tr>
      <tr>
        <td>{{ $qty }} x Rp{{ number_format($price) }}</td>
        <td class="right">Rp{{ number_format($qty * $price) }}</td>
      </tr>
      @if ($disc > 0)
      <tr>
        <td>Disc {{ $qty }} x Rp{{ number_format($disc) }}</td>
        <td class="right">-Rp{{ number_format($qty * $disc) }}</td>
      </tr>
      @endif
      @endforeach
    </table>

    <div class="separator"></div>

    <table>
      <tr>
        <td>Total</td>
        <td class="right">Rp{{ $data['total'] }}</td>
      </tr>
      <tr>
        <td>Bayar</td>
        <td class="right">Rp{{ $data['paid'] }}</td>
      </tr>
    </table>

    <div class="separator"></div>

    <div class="center">Terima kasih atas kunjungan Anda!</div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      window.print();
    });
    window.onafterprint = function() {
      window.close();
    };
  </script>
</body>

</html>