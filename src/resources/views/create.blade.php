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
        <p class="now-date">{{ $now->isoFormat('Y年M月D日(ddd)') }}</p>
        <h1 class='current-time'>{{ $time }}</h1>
        <div class="form-container">
            <form action="{{ route('attendance.store') }}" method="post">
                @csrf
                @if($status == 'not_working')
                <button type="submit" name="action" value="clock_in" class="attendance-button">出勤</button>
                @elseif($status == 'working')
                <button type="submit" name="action" value="clock_out" class="leaving-work-button">退勤</button>
                <button type="submit" name="action" value="break_start" class="starting-brake-button">休憩入</button>
                @elseif($status == 'on_break')
                <button type="submit" name="action" value="break_end" class="ending-brake-button">休憩戻</button>
                @elseif($status == 'finished')
                <p class="leaving-work-message">お疲れ様でした。</p>
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

        document.querySelector('.current-time').textContent = formattedTime;
    }
    updateClock();
    setInterval(updateClock, 1000);
</script>
@endpush