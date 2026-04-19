@extends('layouts.adminlte')

@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-comment-dots mr-2"></i>Send LINE Chat</h3>
            </div>
            <form method="POST" action="{{ route('app.chat.store') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Resident</label>
                        <select name="customer_id" class="form-control @error('customer_id') is-invalid @enderror" required>
                            <option value="">Select linked resident</option>
                            @foreach($linkedCustomers as $customer)
                                <option value="{{ $customer->id }}" @selected((int) old('customer_id', $selectedCustomer?->id) === $customer->id)>
                                    {{ $customer->name }} ({{ $customer->room?->room_number ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group mb-0">
                        <label>Message</label>
                        <textarea name="message" rows="5" class="form-control @error('message') is-invalid @enderror" placeholder="Type message to resident LINE OA" required>{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary">Send Message</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-outline card-secondary">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="fas fa-stream mr-2"></i>Recent Chat Timeline</h3>
                @if($selectedCustomer)
                    <span class="text-muted small">{{ $selectedCustomer->name }} ({{ $selectedCustomer->room?->room_number ?? '-' }})</span>
                @else
                    <span class="text-muted small">Showing latest messages</span>
                @endif
            </div>
            <div class="card-body" style="max-height: 560px; overflow-y: auto;">
                @forelse($messages as $message)
                    @php
                        $payload = (array) $message->payload;
                        $text = data_get($payload, 'message');
                        if (! is_string($text) || $text === '') {
                            $text = data_get($payload, 'message.text');
                        }
                        if (! is_string($text) || $text === '') {
                            $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            $text = $encoded !== false ? $encoded : '[structured payload]';
                        }
                        $isOutbound = $message->direction === 'outbound';
                    @endphp
                    <div class="mb-3 d-flex {{ $isOutbound ? 'justify-content-end' : 'justify-content-start' }}">
                        <div class="p-2 rounded {{ $isOutbound ? 'bg-primary text-white' : 'bg-light border' }}" style="max-width: 85%; white-space: pre-wrap;">
                            <div class="small font-weight-bold mb-1">
                                {{ $message->customer?->name ?? 'Resident' }}
                                <span class="font-weight-normal">• {{ $isOutbound ? 'OUT' : 'IN' }}</span>
                            </div>
                            <div>{{ $text }}</div>
                            <div class="small mt-1 {{ $isOutbound ? 'text-white-50' : 'text-muted' }}">
                                {{ $message->sent_at?->format('d/m/Y H:i:s') ?? '-' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">No chat messages yet</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
