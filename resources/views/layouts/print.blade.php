<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'تقرير')</title>
    <style>
        @font-face { font-family: Cairo; src: url('{{ asset('fonts/cairo-variable.woff2') }}') format('woff2'); }
        @page { size: A4 portrait; margin: 0; }
        * { box-sizing: border-box; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { margin: 0; color: #111; font-family: Cairo, sans-serif; }
        .print-page { min-height: 290mm; padding: 8mm; position: relative; }
        table, th, td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        header { padding-bottom: 4px; border-bottom: 2px solid #b68b00; text-align: center; }
        header img { max-width: 180px; max-height: 28mm; object-fit: contain; }
        header .company-name { font-size: 16px; font-weight: 700; }
        h1 { margin: 4px 0; font-size: 18px; }
        h2 { margin: 8px 0 4px; padding: 3px 5px; background: #f4e8bf; font-size: 15px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 11px; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { padding: 3px 5px; border: 1px solid #777; }
        th { background: #f6f1df; }
        .block { break-inside: avoid; }
        .travelers-band { display: grid; grid-template-columns: repeat(var(--cols, 1), 1fr); gap: 6mm; align-items: start; break-inside: avoid; margin-bottom: 4mm; }
        table.travelers { table-layout: fixed; width: 100%; }
        table.travelers .num { width: 11mm; text-align: center; }
        table.travelers td, table.travelers th { padding: 3px 5px; font-size: 11px; word-break: break-word; }
        .no-print { display: block; margin: 12px auto; padding: 8px 28px; cursor: pointer; }
        .watermark { position: fixed; top: 50%; left: 50%; z-index: -1; width: 120mm; transform: translate(-50%, -50%); opacity: {{ config('umrah.watermark_opacity', 0.10) }}; pointer-events: none; }
        .watermark.receipt-watermark { z-index: 0; opacity: {{ $watermarkOpacity ?? config('umrah.watermark_opacity', 0.10) }}; }
        .print-page.receipt-page { min-height: 290mm; padding: 5mm 7mm 7mm; position: relative; z-index: 1; }
        .receipt-page header { padding-bottom: 4px; }
        .receipt-page header img { max-width: 170px; max-height: 24mm; }
        .receipt-page header .company-name { font-size: 15px; line-height: 1.25; }
        .receipt-page header > div:last-child { font-size: 11px; }
        .receipt-page h1 { margin: 6px 0; font-size: 18px; line-height: 1.35; }
        .receipt-page h2 { margin: 8px 0 4px; padding: 4px 6px; font-size: 15px; }
        .receipt-page table { font-size: 11px; line-height: 1.3; }
        .receipt-page th, .receipt-page td { padding: 4px 6px; }
        .receipt-page .block { margin-bottom: 2mm; }
        .receipt-page .travelers-band { display: block; margin-bottom: 0; }
        .receipt-page table.travelers { margin-bottom: 2mm; }
        .receipt-page table.travelers .num { width: 11mm; text-align: center; }
        .receipt-page table.travelers th:not(.num), .receipt-page table.travelers td:not(.num) { width: calc((100% - 22mm) / 2); }
        .receipt-page table.travelers td, .receipt-page table.travelers th { padding: 3px 5px; font-size: 11px; word-break: break-word; }
        .receipt-page .receipt-footer { display: grid; grid-template-columns: 1fr 32mm; gap: 5mm; align-items: center; }
        .receipt-page .receipt-footer p { margin: 5px 0; }
        .receipt-page .receipt-qr { text-align: center; line-height: 1.25; }
        .receipt-page .receipt-qr svg, .receipt-page .receipt-qr img { display: block; width: 32mm !important; height: 32mm !important; max-width: 32mm; margin: auto; }
        .receipt-page .receipt-qr small { display: block; margin-top: 2mm; }
        @media print {
            .no-print { display: none !important; }
            .receipt-page .print-section { display: flex; flex-direction: column; gap: 2mm; }
        }
    </style>
</head>
<body>
    @if (($watermark ?? false) && is_file(public_path(config('umrah.logo_path'))))
        <img class="watermark {{ isset($watermarkOpacity) ? 'receipt-watermark' : '' }}" src="{{ asset(config('umrah.logo_path')) }}" alt="">
    @endif
    <main class="print-page @yield('page-class')">
    <header>
        @if (is_file(public_path(config('umrah.logo_path'))))
            <img src="{{ asset(config('umrah.logo_path')) }}" alt="مواكب">
        @else
            <div class="company-name">مواكب</div>
        @endif
        <div class="company-name">{{ config('umrah.company_name') }}</div>
        <div>{{ auth()->user()?->isMainAdmin() ? 'الفرع الرئيسي' : (auth()->user()?->broker?->name ?? 'الفرع الرئيسي') }}</div>
    </header>

    <button class="no-print" onclick="window.print()">طباعة</button>

    @yield('content')
    </main>
    @if ($autoPrint ?? false)
        <script>
            window.addEventListener('load', () => {
                window.setTimeout(() => window.print(), 300);
            });
        </script>
    @endif
</body>
</html>
