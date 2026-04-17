@extends('layouts.adminlte')

@section('content')
@php
    $formatLineMessage = static function (array $payload): string {
        $primary = data_get($payload, 'message');

        if (is_string($primary) && $primary !== '') {
            return $primary;
        }

        $nestedText = data_get($payload, 'message.text');

        if (is_string($nestedText) && $nestedText !== '') {
            return $nestedText;
        }

        if (is_array($primary)) {
            $encoded = json_encode($primary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $encoded !== false ? $encoded : '[structured payload]';
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded !== false ? $encoded : '[structured payload]';
    };
@endphp

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $linkedResidents }}</h3>
                <p>Linked Residents</p>
            </div>
            <div class="icon"><i class="fab fa-line"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $webhookEventsToday }}</h3>
                <p>Webhook Events Today</p>
            </div>
            <div class="icon"><i class="fas fa-bolt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $outboundMessagesToday }}</h3>
                <p>Outbound Messages Today</p>
            </div>
            <div class="icon"><i class="fas fa-paper-plane"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $failedLineNotifications }}</h3>
                <p>Failed LINE Notifications</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-wave-square mr-2"></i>Latest Webhook Events</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Event</th>
                            <th>LINE User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentWebhookLogs as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('d/m H:i:s') }}</td>
                                <td><span class="badge badge-light">{{ $log->event_type }}</span></td>
                                <td>{{ $log->line_user_id }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No webhook activity yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-comments mr-2"></i>Recent LINE Messages</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Direction</th>
                            <th>Resident</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLineMessages as $message)
                            <tr>
                                <td>{{ $message->sent_at?->format('d/m H:i:s') }}</td>
                                <td>
                                    <span class="badge badge-{{ $message->direction === 'outbound' ? 'success' : 'secondary' }}">
                                        {{ strtoupper($message->direction) }}
                                    </span>
                                </td>
                                <td>{{ $message->customer?->name ?? '-' }}</td>
                                <td style="max-width:360px;white-space:normal;">{{ $formatLineMessage((array) $message->payload) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No LINE messages yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-dark">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>LINE Delivery Log</h3>
        <div class="small text-muted">Use this page to confirm menu taps, replies, and failures without opening external logs.</div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Event</th>
                    <th>Status</th>
                    <th>Target</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentLineNotifications as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $log->event }}</td>
                        <td>
                            <span class="badge badge-{{ $log->status === 'failed' ? 'danger' : ($log->status === 'replied' || $log->status === 'received' ? 'success' : 'secondary') }}">
                                {{ strtoupper($log->status) }}
                            </span>
                        </td>
                        <td>{{ $log->target }}</td>
                        <td style="white-space:normal;">{{ $log->message }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No LINE delivery logs yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection