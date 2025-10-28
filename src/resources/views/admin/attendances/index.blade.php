@extends('layouts.admin')
@section('title','管理：勤怠一覧')
@section('css')
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="index-container">
        <h1 class="attendance-index-title">{{ $target->format('Y年m月j日')}}の勤怠一覧</h1>
        <div class="select-day">
            <a class="prev-day" href="{{ route('admin.attendance.index',['date' => $prev_date ]) }}"><i class="fa-solid fa-arrow-left"></i>前日</a>
            <p class="current-day"><i class="fa-solid fa-calendar-days"></i>{{ $current_day }}</p>
            <a class="next-day" href="{{ route('admin.attendance.index',['date' => $next_date ]) }}">翌日<i class=" fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="attendance-table-container">
            <table class="attendance-table">
                <thead>
                    <tr class="attendance-table-row">
                        <th>名前</th>
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
                        <td>{{ $dailyRow['user']}}</td>
                        <td>{{ $dailyRow['clock_in']}}</td>
                        <td>{{ $dailyRow['clock_out']}}</td>
                        <td>{{ $dailyRow['break_total']}}</td>
                        <td>{{ $dailyRow['work_total']}}</td>
                        <td><a href="{{ route('admin.attendance.show',['id' => $dailyRow['id']]) }}" class="detail-link">詳細</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection