@extends('layouts.app')
@section('title','勤怠詳細')

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
            <p class="divider">〜</p>
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
                <p class="divider">〜</p>
                <p class="data">{{ $attendance->work_date->format('m月d日') }}</p>
            </div>

            <div class="row">
                <p class="label">出勤・退勤</p>
                <input type="text" name="work-start" id="work-start"
                    class="data input"
                    value="{{ old('work-start', $attendance->clock_in_at?->format('H:i')) }}" placeholder="-">
                <p class="divider">〜</p>
                <input type="text" name="work-end" class="data work-end input" id="work-end"
                    value="{{ old('work-end', $attendance->clock_out_at?->format('H:i')) }}" placeholder="-">
            </div>
            @php
            $workErrors = array_values(array_unique(array_filter(array_merge(
            $errors->get('work') ?? [],
            $errors->get('work-start') ?? [],
            $errors->get('work-end') ?? []
            ))));
            @endphp
            @if($workErrors)
            <div class="row row--error">
                <p class="label"></p>
                <div class="data value-full">
                    @foreach($workErrors as $msg)
                    <p class="error-text">{{ $msg }}</p>
                    @endforeach
                </div>
            </div>
            @endif

            @foreach ($breaks as $br)
            @php $idx = $loop->index; @endphp
            <div class="row">
                <p class="label">
                    @if($loop->first) 休憩 @else 休憩{{ $loop->iteration }} @endif
                </p>
                <input type="text" name="breaks[{{ $idx }}][start]"
                    class="data break-start-data input"
                    value="{{ old('breaks.'.$idx.'.start', $br->break_started_at?->format('H:i')) }}" placeholder="-">
                <p class="divider">〜</p>
                <input type="text" name="breaks[{{ $idx }}][end]"
                    class="data input"
                    value="{{ old('breaks.'.$idx.'.end', $br->break_ended_at?->format('H:i')) }}" placeholder="-">
            </div>
            @php
            $rowErrors = array_values(array_unique(array_filter(array_merge(
            $errors->get("breaks.$idx.messages") ?? [],
            $errors->get("breaks.$idx.start") ?? [],
            $errors->get("breaks.$idx.end") ?? []
            ))));
            @endphp
            @if($rowErrors)
            <div class="row row--error">
                <p class="label"></p>
                <div class="data value-full">
                    @foreach($rowErrors as $msg)
                    <p class="error-text">{{ $msg }}</p>
                    @endforeach
                </div>
            </div>
            @endif
            @endforeach

            @php $next = $breaks->count(); @endphp
            <div class="row">
                <p class="label">休憩{{ $next + 1 }}</p>
                <input type="text" name="breaks[{{ $next }}][start]"
                    id="breaks_{{ $next }}_start"
                    class="data break-start-data input"
                    value="{{ old('breaks.'.$next.'.start') }}" placeholder="-">
                <p class="divider">〜</p>
                <input type="text" name="breaks[{{ $next }}][end]"
                    id="breaks_{{ $next }}_end"
                    class="data input"
                    value="{{ old('breaks.'.$next.'.end') }}" placeholder="-">
            </div>
            @php
            $rowErrors = array_values(array_unique(array_filter(array_merge(
            $errors->get("breaks.$next.messages") ?? [],
            $errors->get("breaks.$next.start") ?? [],
            $errors->get("breaks.$next.end") ?? []
            ))));
            @endphp
            @if($rowErrors)
            <div class="row row--error">
                <p class="label"></p>
                <div class="data value-full">
                    @foreach($rowErrors as $msg)
                    <p class="error-text">{{ $msg }}</p>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="row row-remarks row-single">
                <p class="label">備考</p>
                <textarea name="proposed_remarks" id="remarks-data">{{ old('proposed_remarks') }}</textarea>
            </div>
            @php
            $remarkErrors = array_values(array_unique($errors->get('proposed_remarks') ?? []));
            @endphp
            @if($remarkErrors)
            <div class="row row--error">
                <p class="label"></p>
                <div class="data value-full">
                    @foreach($remarkErrors as $msg)
                    <p class="error-text">{{ $msg }}</p>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        <div class="btn">
            <button type="submit" class="btn-edit">修正</button>
        </div>
    </form>
    @endif
</div>
@endsection