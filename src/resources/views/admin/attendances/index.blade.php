@extends('layouts.admin')
@section('title','管理：勤怠一覧')

@section('content')
<div class="container">
    <h1 class="page-title">
        勤怠一覧（{{ $current_year_month ?? ($target?->format('Y/m')) }}）
    </h1>

    <div class="mb-4 flex gap-2">
        <a class="btn btn-outline-secondary"
            href="{{ route('admin.attendance.index', ['ym' => $prev_ym]) }}">
            ← {{ \Carbon\Carbon::createFromFormat('Y-m', $prev_ym)->format('Y/m') }}
        </a>
        <a class="btn btn-outline-secondary"
            href="{{ route('admin.attendance.index', ['ym' => $next_ym]) }}">
            {{ \Carbon\Carbon::createFromFormat('Y-m', $next_ym)->format('Y/m') }} →
        </a>
    </div>

    @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <table class="table table-striped">
        <thead>
            <tr>
                <th>日付</th>
                <th>ユーザー</th>
                <th>出勤</th>
                <th>退勤</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
            @php
            $workDate = $row->work_date instanceof \Carbon\CarbonInterface
            ? $row->work_date
            : (\Carbon\Carbon::parse($row->work_date));
            @endphp
            <tr>
                <td>{{ $workDate->format('Y-m-d') }}</td>
                <td>{{ $row->user?->name }}</td>
                <td>{{ $row->clock_in_at?->format('H:i') }}</td>
                <td>{{ $row->clock_out_at?->format('H:i') }}</td>
                <td>
                    <a class="btn btn-primary btn-sm"
                        href="{{ route('admin.attendance.show', ['id' => $row->id]) }}">
                        詳細
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">データがありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-3">
        {{ $rows->links() }}
    </div>
</div>
@endsection