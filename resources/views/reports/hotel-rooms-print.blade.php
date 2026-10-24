@extends('layouts.print')

@php($watermark = true)

@section('title', 'تقرير الغرف المحجوزة في الفندق')

@section('content')
    <h1>تقرير الغرف المحجوزة في الفندق</h1>
    <table>
        <thead><tr><th>م</th><th>رقم الحجز</th><th>الفرع</th><th>النوع</th><th>ولي الأمر</th><th>الدخول</th><th>الخروج</th><th>عدد الغرف</th></tr></thead>
        <tbody>@foreach ($rows as $index => $row)<tr><td>{{ $index + 1 }}</td><td>{{ $row->booking?->booking_number }}</td><td>{{ $row->booking?->broker?->name ?? 'الفرع الرئيسي' }}</td><td>{{ $row->booking?->bookingType?->name }}</td><td>{{ $row->booking?->customer?->name_ar }}</td><td>{{ $row->check_in?->format('Y-m-d') }}</td><td>{{ $row->check_out?->format('Y-m-d') }}</td><td>{{ $row->rooms->count() }}</td></tr>@endforeach</tbody>
        <tfoot><tr><th colspan="7">إجمالي الغرف</th><th>{{ $rows->sum(fn ($row) => $row->rooms->count()) }}</th></tr></tfoot>
    </table>
@endsection
