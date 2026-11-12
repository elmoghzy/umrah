@extends('layouts.print')

@php
    $formatCurrency = fn ($amount): string => \Illuminate\Support\Number::currency(
        (float) $amount,
        config('umrah.currency_code'),
        config('umrah.currency_locale'),
        (int) config('umrah.currency_decimal_places'),
    );
@endphp

@section('title', 'طباعة الحجوزات الحالية')

@section('content')
    <h1>الحجوزات الحالية</h1>
    <table>
        <thead>
            <tr>
                <th>م</th>
                <th>رقم الحجز</th>
                <th>الفرع</th>
                <th>ولي الأمر</th>
                <th>النوع</th>
                <th>النطاق</th>
                <th>عدد المعتمرين</th>
                <th>التأشيرات</th>
                <th>الصافي</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bookings as $index => $booking)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $booking->booking_number }}</td>
                    <td>{{ $booking->broker?->name ?? 'الفرع الرئيسي' }}</td>
                    <td>{{ $booking->customer?->name_ar }}</td>
                    <td>{{ $booking->bookingType?->name }}</td>
                    <td>{{ $booking->stay_scope?->getLabel() }}</td>
                    <td>{{ $booking->residents_count }}</td>
                    <td>{{ $booking->visas_issued_count }}/{{ $booking->visas_total_count }}</td>
                    <td>{{ $formatCurrency($booking->net_amount) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="9">الإجمالي: {{ $bookings->count() }}</th>
            </tr>
        </tfoot>
    </table>
@endsection
