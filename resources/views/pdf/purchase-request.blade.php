{{--
    Bukti pengajuan pembelian (dompdf, A4). CSS sederhana berbasis tabel karena dompdf tidak mendukung flex/grid.
    Semua teks di-escape ({{ }}); tidak ada gambar/font eksternal.
--}}
@php
    $rupiah = fn (int|float|null $value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $percent = fn (float $value) => rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',').'%';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Pengajuan {{ $purchase->documentNumber() }} | {{ $dealer['name'] }}</title>
    <style>
        @page { margin: 28px 36px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1c1c1e; line-height: 1.45; }
        h1, h2 { margin: 0; }
        .kop { width: 100%; border-bottom: 3px solid #c3002f; padding-bottom: 8px; margin-bottom: 14px; }
        .kop td { vertical-align: top; }
        .brand { font-size: 20px; font-weight: bold; color: #1c1c1e; }
        .brand span { color: #c3002f; }
        .tagline { color: #6b7280; font-size: 9.5px; }
        .contact { text-align: right; font-size: 9px; color: #4b5563; }
        .title { font-size: 15px; font-weight: bold; margin-bottom: 2px; }
        .meta { width: 100%; margin-bottom: 14px; }
        .meta td { padding: 1px 0; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 9px; background: #1c1c1e; color: #fff; font-size: 9.5px; font-weight: bold; }
        .section { font-size: 11px; font-weight: bold; color: #c3002f; text-transform: uppercase; letter-spacing: .5px; margin: 12px 0 4px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        table.data th { width: 34%; color: #4b5563; font-weight: normal; background: #f4f6f9; }
        table.data td.amount { font-weight: bold; }
        .total td, .total th { border-top: 2px solid #1c1c1e; font-size: 11.5px; }
        .note { border-left: 3px solid #c3002f; background: #fdf2f4; padding: 6px 8px; margin-top: 6px; }
        .footer { margin-top: 18px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 8.5px; color: #6b7280; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            <td>
                <div class="brand">{{ $dealer['name'] }}<span>.</span></div>
                @if ($dealer['tagline'])
                    <div class="tagline">{{ $dealer['tagline'] }}</div>
                @endif
            </td>
            <td class="contact">
                @if ($dealer['address']){{ $dealer['address'] }}<br>@endif
                @if ($dealer['phone'])Telp. {{ $dealer['phone'] }}<br>@endif
                @if ($dealer['email']){{ $dealer['email'] }}<br>@endif
                @if ($dealer['hours']){{ $dealer['hours'] }}@endif
            </td>
        </tr>
    </table>

    <h1 class="title">Bukti Pengajuan Pembelian</h1>
    <table class="meta">
        <tr>
            <td>No. <strong>{{ $purchase->documentNumber() }}</strong> · diajukan {{ $purchase->created_at->translatedFormat('d F Y, H:i') }} WIB</td>
            <td style="text-align: right;">Status: <span class="status">{{ $purchase->statusLabel() }}</span></td>
        </tr>
    </table>

    <div class="section">Data Customer</div>
    <table class="data">
        <tr><th>Nama</th><td>{{ $customer->name }}</td></tr>
        <tr><th>Email</th><td>{{ $customer->email }}</td></tr>
        <tr><th>Nomor HP / WhatsApp</th><td>{{ $purchase->phone }}</td></tr>
        <tr><th>Alamat</th><td>{{ $purchase->address }}</td></tr>
    </table>

    <div class="section">Data Mobil</div>
    <table class="data">
        <tr><th>Mobil</th><td><strong>{{ $car->brand->name }} {{ $car->name }} {{ $car->year }}</strong></td></tr>
        <tr><th>Kondisi</th><td>{{ $car->condition_label }}@unless ($car->isNew()) · {{ number_format($car->mileage, 0, ',', '.') }} km @endunless</td></tr>
        <tr><th>Transmisi / Bahan bakar</th><td>{{ $car->transmission_label }} · {{ $car->fuel_type_label }}</td></tr>
        @if ($car->color)
            <tr><th>Warna</th><td>{{ $car->color }}</td></tr>
        @endif
    </table>

    <div class="section">Rincian Pembayaran</div>
    <table class="data">
        <tr><th>Metode pembayaran</th><td>{{ $purchase->paymentMethodLabel() }}</td></tr>
        <tr><th>Harga mobil</th><td class="amount">{{ $rupiah($purchase->car_price) }}</td></tr>
        @if ($purchase->isCredit())
            <tr><th>Uang muka (DP)</th><td>{{ $rupiah($purchase->down_payment) }}</td></tr>
            <tr><th>Pokok pinjaman</th><td>{{ $rupiah($purchase->principal()) }}</td></tr>
            <tr><th>Tenor</th><td>{{ $purchase->tenor_months }} bulan</td></tr>
            <tr><th>Bunga flat</th><td>{{ $percent((float) $purchase->interest_rate) }} per tahun · total bunga {{ $rupiah($purchase->interestTotal()) }}</td></tr>
            <tr><th>Cicilan per bulan</th><td class="amount">{{ $rupiah($purchase->monthly_installment) }}</td></tr>
            <tr class="total"><th>Total pembayaran</th><td class="amount">{{ $rupiah($purchase->totalPayment()) }}</td></tr>
        @else
            <tr class="total"><th>Total pembayaran</th><td class="amount">{{ $rupiah($purchase->car_price) }}</td></tr>
        @endif
    </table>

    @if ($purchase->notes)
        <div class="section">Catatan Customer</div>
        <div class="note">{{ $purchase->notes }}</div>
    @endif

    @if ($purchase->admin_note)
        <div class="section">Catatan Dealer</div>
        <div class="note">{{ $purchase->admin_note }}</div>
    @endif

    <div class="footer">
        Dokumen ini adalah <strong>bukti pengajuan pembelian</strong>, bukan bukti pembayaran atau kontrak jual beli.
        Nilai kredit merupakan simulasi bunga flat dan dapat berubah sesuai persetujuan lembaga pembiayaan.<br>
        Dicetak {{ now()->translatedFormat('d F Y, H:i') }} WIB dari {{ config('app.url') }} · {{ $dealer['name'] }}
    </div>
</body>
</html>
