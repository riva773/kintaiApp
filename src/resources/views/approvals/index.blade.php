@extends('layouts.app')
@section('title','申請一覧')
@section('css')
<link rel="stylesheet" href="{{ asset('css/approvals_index.css') }}">
@endsection
@section('content')
<div class="container">
    <div class="index-container">


        <h1 class="approvals-title">申請一覧</h1>
        <nav class="approvals-tabs">
            <a href="{{ route('approvals.index',['status' => 'pending']) }}" class="{{ $status === 'pending' ? 'active' : '' }} nav-pending">承認待ち</a>
            <a href="{{ route('approvals.index',['status' => 'approved']) }}" class="{{ $status === 'approved' ? 'active' : ''}} nav-approved">承認済み</a>
        </nav>

        <div class="approvals-table-container">
            <table class="approvals-table">
                <thead>
                    <tr class="approvals-table-row">
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($approvals as $ap)
                    <tr class="approvals-table-row">
                        <td>
                            @if($ap->status === 'pending')
                            承認待ち
                            @elseif($ap->status === 'approved')
                            承認済み
                            @endif
                        </td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $ap->attendance->work_date->format('Y/m/d') }}</td>
                        <td>{{ $ap->proposed_remarks }}</td>
                        <td>{{ $ap->created_at->format('Y/m/d') }}</td>
                        <td><a href="{{ route('attendance.show',['id' => $ap->attendance->id]) }}">詳細</a></td>
                    </tr>
                    @empty
                    <tr class="approvals-table-row">
                        <td colspan="6" class="empty-cell">申請はまだありません </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection