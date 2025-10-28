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

    @error('pending')
    <div class="alert alert-warning">{{ $message }}</div>
    @enderror

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
    $row_count = max(2, $rb_count);
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

        @for ($i = 0; $i < $row_count; $i++)
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
    @else

    @error('work-start') <p class="text-red-500">{{ $message }}</p> @enderror
    @error('work-end') <p class="text-red-500">{{ $message }}</p> @enderror
    @foreach ($errors->getMessages() as $field => $msgs)
    @if (str_starts_with($field, 'breaks.'))
    @foreach ($msgs as $m)
    <p class="text-red-500">{{ $m }}</p>
    @endforeach
    @endif
    @endforeach

    <form action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}" method="post" class="form">
        @csrf
        @method('patch')

        <div class="form-container">
            <div class="row row--single">
                <p class="label">名前</p>
                <p class="data value-full">{{ $user->name }}</p>
            </div>

            <div class="row">
                <p class="label">日付</p>
                <p class="data">{{ $attendance->work_date->format('Y年') }}</p>
                <p class="divider"></p>
                <p class="data">{{ $attendance->work_date->format('m月d日') }}</p>
            </div>

            <div class="row">
                <p class="label">出勤・退勤</p>
                <input type="text" name="work-start" id="work-start"
                    class="data input"
                    value="{{ old('work-start', $attendance->clock_in_at?->format('H:i')) }}"
                    placeholder="-">
                <p class="divider">〜</p>
                <input type="text" name="work-end" id="work-end"
                    class="data input"
                    value="{{ old('work-end', $attendance->clock_out_at?->format('H:i')) }}"
                    placeholder="-">
            </div>

            @foreach ($breaks as $br)
            @php $i = $loop->index; @endphp
            <div class="row">
                <p class="label">{{ $loop->first ? '休憩' : '休憩'.$loop->iteration }}</p>
                <input type="text" name="breaks[{{ $i }}][start]"
                    class="data input"
                    value="{{ old("breaks.$i.start", $br->break_started_at?->format('H:i')) }}"
                    placeholder="-">
                <p class="divider">〜</p>
                <input type="text" name="breaks[{{ $i }}][end]"
                    class="data input"
                    value="{{ old("breaks.$i.end", $br->break_ended_at?->format('H:i')) }}"
                    placeholder="-">
            </div>
            @endforeach

            @php $next = $breaks->count(); @endphp
            <div class="row">
                <p class="label">休憩{{ $next + 1 }}</p>
                <input type="text" name="breaks[{{ $next }}][start]"
                    class="data input"
                    value="{{ old("breaks.$next.start") }}" placeholder="-">
                <p class="divider">〜</p>
                <input type="text" name="breaks[{{ $next }}][end]"
                    class="data input"
                    value="{{ old("breaks.$next.end") }}" placeholder="-">
            </div>
        </div>
        <button type="submit" class="btn-edit">修正</button>
    </form>
    @endif
</div>
@endsection