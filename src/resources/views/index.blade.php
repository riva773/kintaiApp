@extends('layouts.app')
@section('title','勤怠一覧')
@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="index-container">
        <h1 class="attendance-index-title">勤怠一覧</h1>
        <div class="select-month">
            <a class="prev-year-month" href="{{ route('attendance.index',['ym' => $prevYm ]) }}"><i class="fa-solid fa-arrow-left"></i>前月</a>
            <p class="current-year-month"><i class="fa-solid fa-calendar-days"></i>{{ $current_year_month }}</p>
            <a class="next-year-month" href="{{ route('attendance.index',['ym' => $nextYm ]) }}">翌月<i class=" fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="attendance-table-container">
            <table class="attendance-table">
                <thead>
                    <tr class="attendance-table-row">
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dailyRows as $dailyRow)
                    <tr class="attendance-table-row">
                        <td>{{ $dailyRow['date']}}</td>
                        <td>{{ $dailyRow['clock_in']}}</td>
                        <td>{{ $dailyRow['clock_out']}}</td>
                        <td>{{ $dailyRow['break_total']}}</td>
                        <td>{{ $dailyRow['work_total']}}</td>
                        <td><a href=" {{ route('attendance.show',['id' => $dailyRow['id']]) }}" class="detail-link">詳細</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection