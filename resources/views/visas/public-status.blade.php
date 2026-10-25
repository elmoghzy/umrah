<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>حالة التأشيرات - {{ $booking->booking_number }}</title>
    <style>
        :root { color-scheme: light; font-family: Cairo, Tahoma, sans-serif; }
        body { margin: 0; background: #f5f3ed; color: #252525; }
        main { width: min(760px, calc(100% - 32px)); margin: 32px auto; }
        .card { background: #fff; border: 1px solid #e6dfca; border-radius: 16px; padding: 24px; box-shadow: 0 8px 24px #6b5a2514; }
        h1 { margin: 0 0 8px; color: #7b5c00; font-size: 24px; }
        .intro { margin: 0 0 24px; color: #666; }
        .summary { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 24px; }
        .summary div { background: #faf8f0; border-radius: 10px; padding: 12px; }
        .summary strong { display: block; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 8px; border-bottom: 1px solid #eee; text-align: right; }
        th { color: #6b5a25; font-size: 14px; }
        .status { display: inline-block; border-radius: 999px; padding: 4px 10px; font-size: 13px; font-weight: 700; }
        .pending { background: #fff3cd; color: #8a6500; }
        .issued { background: #dff5e5; color: #18743a; }
        .rejected { background: #fde2e2; color: #a32929; }
        .reason { display: block; margin-top: 4px; color: #a32929; font-size: 12px; }
        @media (max-width: 520px) { main { width: min(100% - 20px, 760px); margin: 20px auto; } .card { padding: 16px; } .summary { grid-template-columns: 1fr; } th, td { padding: 10px 4px; } }
    </style>
</head>
<body>
<main>
    <section class="card">
        <h1>حالة تأشيرات الحجز</h1>
        <p class="intro">هذه الصفحة تعرض آخر حالة لتأشيرات أفراد الحجز.</p>

        <div class="summary">
            <div>رقم الحجز<strong>{{ $booking->booking_number }}</strong></div>
            <div>ولي الأمر<strong>{{ $booking->customer?->name_ar }}</strong></div>
        </div>

        <table>
            <thead>
                <tr><th>م</th><th>اسم المعتمر</th><th>حالة التأشيرة</th></tr>
            </thead>
            <tbody>
                @foreach ($booking->residents as $index => $resident)
                    @php($visa = $visasByCustomer->get($resident->customer_id))
                    @php($status = $visa?->status)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $resident->customer?->name_ar }}</td>
                        <td>
                            @if ($status)
                                <span class="status {{ $status->value }}">{{ $status->getLabel() }}</span>
                                @if ($status->value === 'rejected' && filled($visa->rejected_reason))
                                    <span class="reason">السبب: {{ $visa->rejected_reason }}</span>
                                @endif
                            @else
                                <span class="status pending">لم تُسجل بعد</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
