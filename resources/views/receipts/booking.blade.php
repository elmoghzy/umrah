@php
    $stayScope = $booking->stay_scope?->value;
    $details = $booking->details->filter(fn ($detail) => $stayScope === 0 || ($stayScope === 1 && $detail->city_id === 2) || ($stayScope === 2 && $detail->city_id === 1));
    $perColumn = (int) config('umrah.receipt_travelers_per_column', 10);
    $columnsPerBand = (int) config('umrah.receipt_traveler_columns', 2);
    $names = $booking->residents->map(fn ($resident) => $resident->customer?->name_ar)->values();
    $transfers = $booking->transfers;
    $watermarkOpacity = max(0.20, (float) config('umrah.watermark_opacity', 0.10));
    $formatCurrency = fn ($amount): string => \Illuminate\Support\Number::currency(
        (float) $amount,
        config('umrah.currency_code'),
        config('umrah.currency_locale'),
        (int) config('umrah.currency_decimal_places'),
    );
@endphp

@extends('layouts.print')

@php($watermark = true)

@section('page-class', 'receipt-page')

@section('title', 'إيصال '.$booking->booking_number)

@section('content')
    <div class="print-section">
        <h1>إيصال إستلام مستندات سفر</h1>

        <section class="block">
            <table>
                <tr><td>رقم الإيصال</td><td>{{ $booking->booking_number }}</td><td>التاريخ</td><td>{{ $booking->created_at?->format('Y-m-d') }}</td></tr>
                <tr><td>استلمت من الأخ</td><td>{{ $booking->customer?->name_ar }}</td><td>الهاتف</td><td>{{ $booking->customer?->phone }}</td></tr>
                <tr><td>عدد الجوازات</td><td colspan="3">{{ $booking->residents->count() }}</td></tr>
            </table>
        </section>

        <section class="block">
            <h2>المعتمرون ({{ $names->count() }})</h2>
            @foreach ($names->chunk($perColumn * $columnsPerBand) as $bandIndex => $band)
                <div class="travelers-band">
                    @foreach ($band->chunk($perColumn) as $chunkIndex => $column)
                        <table class="travelers">
                            <thead><tr><th class="num">ت</th><th>الاسم بالكامل</th><th class="num">ت</th><th>الاسم بالكامل</th></tr></thead>
                            <tbody>
                                @foreach ($column->chunk(2) as $pairIndex => $pair)
                                    @php($firstNumber = $bandIndex * $perColumn * $columnsPerBand + $chunkIndex * $perColumn + $pairIndex * 2 + 1)
                                    <tr>
                                        <td class="num">{{ $firstNumber }}</td>
                                        <td>{{ $pair->values()->get(0) }}</td>
                                        @if ($pair->count() > 1)
                                            <td class="num">{{ $firstNumber + 1 }}</td>
                                            <td>{{ $pair->values()->get(1) }}</td>
                                        @else
                                            <td colspan="2"></td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endforeach
                </div>
            @endforeach
        </section>

        @if ($transfers->isNotEmpty())
            <section class="block">
                <h2>المواصلات</h2>
                <table>
                    <thead><tr><th>من → إلى</th><th>الوسيلة</th><th>التاريخ والوقت</th><th>السعر</th></tr></thead>
                    <tbody>
                        @foreach ($transfers as $transfer)
                            <tr>
                                <td>{{ $transfer->fromCity?->name }} → {{ $transfer->toCity?->name }}</td>
                                <td>{{ $transfer->transport?->name }}</td>
                                <td>
                                    {{ $transfer->travel_date?->format('Y-m-d') }}
                                    {{ $transfer->travel_time ? \Illuminate\Support\Carbon::parse($transfer->travel_time)->format('H:i') : '' }}
                                </td>
                                <td>{{ $formatCurrency($transfer->price) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        @foreach ($details as $detail)
            <section class="block">
                <h2>سكن {{ $detail->city_id === 1 ? 'مكة' : 'المدينة' }}</h2>
                <table>
                    <thead><tr><th>الفندق</th><th>الدخول</th><th>الخروج</th><th>عدد الليالي</th><th>عدد الغرف</th><th>الوجبة</th></tr></thead>
                    <tbody><tr>
                        <td>{{ $detail->hotel?->name_ar }}</td>
                        <td>{{ $detail->check_in?->format('Y-m-d') }}</td>
                        <td>{{ $detail->check_out?->format('Y-m-d') }}</td>
                        <td>{{ (int) $detail->check_in->diffInDays($detail->check_out, true) }}</td>
                        <td>{{ $detail->rooms->count() }}</td>
                        <td>{{ $detail->meal?->name }}</td>
                    </tr></tbody>
                </table>
            </section>
        @endforeach

        <section class="block">
            <h2>تفاصيل السعر</h2>
            <table>
                <thead><tr><th>البند</th><th>البيان</th><th>السعر</th></tr></thead>
                <tbody>
                    <tr><td>الخدمات الإضافية</td><td>{{ $booking->services_note }}</td><td>{{ $formatCurrency($booking->service_amount) }}</td></tr>
                    <tr><td>المواصلات</td><td></td><td>{{ $formatCurrency($booking->transport_amount) }}</td></tr>
                    <tr><td>السعر</td><td colspan="2">{{ $formatCurrency($booking->total_amount) }}</td></tr>
                    <tr><th>الصافي</th><th colspan="2">{{ $formatCurrency($booking->net_amount) }}</th></tr>
                </tbody>
            </table>
        </section>

        <section class="block receipt-footer">
            <div>
                <p><strong>تاريخ السفر:</strong> {{ $details->sortBy('check_in')->first()?->check_in?->format('Y-m-d') }}</p>
                <p><strong>ملاحظات:</strong> {{ $booking->notes }}</p>
                <p><strong>المستلم:</strong> {{ auth()->user()?->name }}</p>
            </div>
            <div class="receipt-qr">{!! $qr !!}</div>
        </section>
    </div>
@endsection
