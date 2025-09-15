@extends('layouts.app')
@section('title','勤怠登録')
@section('css')
<link rel="stylesheet" href="{{asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="attendance-container">
        @if($status == 'not_working')
        <p class="status">勤務外</p>
        @elseif($status == 'working')
        <p class="status">出勤中</p>
        @elseif($status == 'on_break')
        <p class="status">休憩中</p>
        @elseif($status == 'finished')
        <p class="status">退勤済</p>
        @endif
        <p class="now_date">{{ $now->isoFormat('Y年M月D日(ddd)') }}</p>
        <h1 id='current-time'>{{ $time }}</h1>
        <!-- 現在の状況に応じて、出勤、退勤、休憩入り、休憩戻り、お疲れ様でしたのいずれかを表示する。 -->
        <div class="form-container">
            <form action="{{ route('attendance.store') }}" method="post">
                @csrf
                @if($status == 'not_working')
                <button type="submit" name="action" value="clock_in">出勤</button>
                @elseif($status == 'working')
                <button type="submit" name="action" value="clock_out">退勤</button>
                <button type="submit" name="action" value="break_start">休憩入</button>
                @elseif($status == 'on_break')
                <button type="submit" name="action" value="break_end">休憩戻</button>
                @elseif($status == 'finished')
                お疲れ様でした。
                @endif
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateClock() {
        const now = new Date();
        const hours = now.getHours();
        const minutes = now.getMinutes();

        const formattedTime = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;

        document.getElementById('current-time').textContent = formattedTime;
    }

    updateClock();
    setInterval(updateClock, 1000);
</script>
@endpush