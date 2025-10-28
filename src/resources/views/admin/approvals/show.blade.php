@extends('layouts.admin')
@section('title','管理：修正申請承認画面')
@section('css')
<link rel="stylesheet" href="{{ asset('css/show.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="page-title">勤怠詳細</h1>

    @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @php
    $fmt = function ($v) {
    if (!$v) return null;
    if ($v instanceof \Carbon\CarbonInterface) return $v->format('H:i');
    try { return \Carbon\Carbon::parse($v)->format('H:i'); } catch (\Throwable $e) { return null; }
    };
    $year_part = $attendance->work_date ? $attendance->work_date->format('Y年') : '';
    $md_part = $attendance->work_date ? $attendance->work_date->format('n月j日') : '';

    $rb_is_collection = $resolved_breaks instanceof \Illuminate\Support\Collection;
    $rb_count = $rb_is_collection ? $resolved_breaks->count() : (is_array($resolved_breaks) ? count($resolved_breaks) : 0);
    $show_break_rows = max(2, $rb_count);
    @endphp

    <div class="form-container">
        <div class="row row--single">
            <p class="label">名前</p>
            <p class="data value-full">{{ $user->name }}</p>
        </div>

        <div class="row">
            <p class="label">日付</p>
            <p class="data">{{ $year_part }}</p>
            <p class="data">{{ $md_part }}</p>
        </div>

        <div class="row">
            <p class="label">出勤・退勤</p>
            <p class="data">{{ $fmt($resolved_clock_in) ?? '-' }}</p>
            <p class="divider">〜</p>
            <p class="data">{{ $fmt($resolved_clock_out) ?? '-' }}</p>
        </div>

        @for ($i = 0; $i < $show_break_rows; $i++)
            @php
            $row=$rb_is_collection ? $resolved_breaks->get($i) : ($resolved_breaks[$i] ?? null);
            $b_st = $fmt($row['start'] ?? null);
            $b_en = $fmt($row['end'] ?? null);
            @endphp
            <div class="row">
                <p class="label">{{ $i === 0 ? '休憩' : '休憩'.($i+1) }}</p>
                <p class="data">{{ $b_st ?? '-' }}</p>
                <p class="divider">〜</p>
                <p class="data">{{ $b_en ?? '-' }}</p>
            </div>
            @endfor

            <div class="row row-remarks row-single">
                <p class="label">備考</p>
                <p class="data value-full">
                    {{ ($resolved_remarks !== null && $resolved_remarks !== '') ? $resolved_remarks : '（なし）' }}
                </p>
            </div>
    </div>

    @if ($is_pending)
    <form action="{{ route('approvals.approve', ['attendance_correct_request' => $approval]) }}" method="post">
        @csrf
        <button type="submit" class="btn-edit">承認</button>
    </form>
    @else
    <div class="approved">
        <p>承認済み</p>
    </div>
    @endif
</div>
@endsection