@extends('layouts.print')

@section('title', 'تقرير حركة الرحلات')

@section('content')
    <h1>تقرير حركة الرحلات</h1>
    <table>
        <thead><tr><th>م</th><th>رقم الحجز</th><th>الفرع</th><th>ولي الأمر</th><th>النوع</th><th>الفندق</th><th>الدخول</th><th>الخروج</th><th>تاريخ التسجيل</th><th>الملاحظة</th></tr></thead>
        <tbody>@foreach ($rows as $index => $row)<tr><td>{{ $index + 1 }}</td><td>{{ $row->booking?->booking_number }}</td><td>{{ $row->booking?->broker?->name ?? 'الفرع الرئيسي' }}</td><td>{{ $row->booking?->customer?->name_ar }}</td><td>{{ $row->booking?->bookingType?->name }}</td><td>{{ $row->hotel?->name_ar }}</td><td>{{ $row->check_in?->format('Y-m-d') }}</td><td>{{ $row->check_out?->format('Y-m-d') }}</td><td>{{ $row->created_at?->format('Y-m-d') }}</td><td>{{ $row->booking?->notes }}</td></tr>@endforeach</tbody>
        <tfoot><tr><th colspan="10">الإجمالي: {{ $rows->count() }}</th></tr></tfoot>
    </table>
@endsection
