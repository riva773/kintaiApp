@extends('layouts.admin')
@section('title','管理：勤怠詳細')
@section('css')
<link rel="stylesheet" href="{{ asset('css/show.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="page-title">勤怠詳細</h1>

    @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($has_pending)
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
            <p class="divider"></p>
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

    <div class="alert alert-warning">
        <p class="alert-message">※承認待ちのため修正はできません。</p>
    </div>

    @else
    @error('work-start')
    <p class="text-red-500">{{ $message }}</p>
    @enderror
    @error('work-end')
    <p class="text-red-500">{{ $message }}</p>
    @enderror

    <form action="{{ route('attendance.corrections.submit',['attendance' => $attendance ])}}" method="post" class="form">
        @csrf
        <div class="form-container">
            <div class="row row--single">
                <p class="label">名前</p>
                <p class="data value-full">{{ $user->name }}</p>
            </div>

            <div class="row">
                <p class="label">日付</p>
                <p class="data">{{ $attendance->work_date->format('Y年')}}</p>
                <p class="divider"></p>
                <p class="data">{{ $attendance->work_date->format('m月d日') }}</p>
            </div>

            <div class="row">
                <p class="label">出勤・退勤</p>
                <input type="text" name="work-start" id="work-start"
                    class="data input"
                    value="{{ $attendance->clock_in_at?->format('H:i') }}" placeholder="-">
                <p class="divider">〜</p>
                <input type="text" name="work-end" class="data work-end input" id="work-end"
                    value="{{ $attendance->clock_out_at?->format('H:i') }}" placeholder="-">
            </div>

            @foreach ($breaks as $br)
            <div class="row">
                <p class="label">
                    @if($loop->first)
                    休憩
                    @else
                    休憩{{ $loop->iteration }}
                    @endif
                </p>
                <input type="text" name="breaks[{{ $loop->index }}][start]"
                    class="data break-start-data input"
                    value="{{ $br->break_started_at?->format('H:i') }}">
                <p class="divider">〜</p>
                <input type="text" name="breaks[{{ $loop->index }}][end]"
                    class="data input"
                    value="{{ $br->break_ended_at?->format('H:i') }}">

                @error('breaks.' . $loop->index . '.start')
                <p class="text-red-500">{{ $message }}</p>
                @enderror
                @error('breaks.' . $loop->index . '.end')
                <p class="text-red-500">{{ $message }}</p>
                @enderror
            </div>
            @endforeach

            @php $next = $breaks->count(); @endphp
            <div class="row">
                <p class="label">休憩{{ $next + 1 }}</p>
                <input type="text" name="breaks[{{ $next }}][start]"
                    id="breaks_{{ $next }}_start"
                    class="data break-start-data input"
                    value="" placeholder="-">
                <p class="divider">〜</p>
                <input type="text" name="breaks[{{ $next }}][end]"
                    id="breaks_{{ $next }}_end"
                    class="data input"
                    value="" placeholder="-">
            </div>

            <div class="row row-remarks row-single">
                <p class="label">備考</p>
                <textarea name="proposed_remarks" id="remarks-data"></textarea>
                @error('proposed_remarks')
                <p class="text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="btn">
            <button type="submit" class="btn-edit">修正</button>
        </div>
    </form>
    @endif
</div>
@endsection