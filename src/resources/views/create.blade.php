@extends('layouts.app')
@section('title','勤怠登録')
@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="container">
    @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="attendance-container">
        @if($uiStatus === 'not_working')
        <p class="status">勤務外</p>
        @elseif($uiStatus === 'working')
        <p class="status">出勤中</p>
        @elseif($uiStatus === 'on_break')
        <p class="status">休憩中</p>
        @elseif($uiStatus === 'finished')
        <p class="status">退勤済</p>
        @endif

        <p class="now-date">{{ $now->isoFormat('Y年M月D日(ddd)') }}</p>
        <h1 class='current-time'>{{ $time }}</h1>

        <div class="form-container">
            <form action="{{ route('attendance.store') }}" method="post">
                @csrf

                @if($uiStatus === 'not_working')
                <button type="submit" name="action" value="clock_in" class="attendance-button">出勤</button>

                @elseif($uiStatus === 'working')
                <button type="submit" name="action" value="clock_out" class="leaving-work-button">退勤</button>
                <button type="submit" name="action" value="break_start" class="starting-brake-button">休憩入</button>

                @elseif($uiStatus === 'on_break')
                <button type="submit" name="action" value="break_end" class="ending-brake-button">休憩戻</button>

                @elseif($uiStatus === 'finished')
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
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const el = document.querySelector('.current-time');
        if (el) el.textContent = `${h}:${m}`;
    }
    updateClock();
    setInterval(updateClock, 1000);
</script>
@endpush