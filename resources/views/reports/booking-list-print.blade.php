@extends('layouts.print')

@php($watermark = true)

@section('title', 'تقرير قائمة الحجوزات')

@section('content')
    <h1>تقرير قائمة الحجوزات</h1>
    <table>
        <thead><tr><th>م</th><th>رقم الحجز</th><th>الفرع</th><th>ولي الأمر</th><th>المعتمرون</th><th>المدينة</th><th>الفندق</th><th>الوجبة</th><th>الدخول</th><th>الملاحظة</th></tr></thead>
        <tbody>@foreach ($rows as $index => $row)<tr><td>{{ $index + 1 }}</td><td>{{ $row->booking->booking_number }}</td><td>{{ $row->booking->broker?->name ?? 'الفرع الرئيسي' }}</td><td>{{ $row->booking->customer?->name_ar }}</td><td>{{ $row->booking->residents->pluck('customer.name_ar')->join('، ') }}</td><td>{{ $row->city?->name }}</td><td>{{ $row->hotel?->name_ar }}</td><td>{{ $row->meal?->name }}</td><td>{{ $row->check_in?->format('Y-m-d') }}</td><td>{{ $row->booking->notes }}</td></tr>@endforeach</tbody>
        <tfoot><tr><th colspan="10">الإجمالي: {{ $rows->count() }}</th></tr></tfoot>
    </table>
@endsection
