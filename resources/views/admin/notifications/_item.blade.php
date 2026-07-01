@php
    // Normalises the two notification shapes into one presenter:
    //  - new_paid_order  → order_number / customer / total_kobo
    //  - alert (AdminAlertNotification) → title / message / icon / severity / url
    $d = $note->data ?? [];
    $isOrder = ($d['type'] ?? null) === 'new_paid_order';

    $title = $isOrder
        ? 'New paid order '.($d['order_number'] ?? '')
        : ($d['title'] ?? 'Notification');

    $body = $isOrder
        ? trim(($d['customer'] ?? 'Unknown').' · '.money($d['total_kobo'] ?? 0))
        : ($d['message'] ?? '');

    $icon = $isOrder ? 'fa-receipt' : ($d['icon'] ?? 'fa-bell');

    $tone = match ($d['severity'] ?? ($isOrder ? 'info' : 'important')) {
        'critical' => 'danger',
        'important' => 'warning',
        default => 'primary',
    };

    $url = $isOrder
        ? (isset($d['order_number']) ? route('admin.orders.show', $d['order_number']) : null)
        : ($d['url'] ?? null);
@endphp

<div class="position-relative d-flex gap-2 px-3 py-3 border-bottom border-translucent {{ $note->read_at ? '' : 'bg-primary-subtle bg-opacity-25' }}">
    @if ($url)
        <a href="{{ $url }}" class="stretched-link" aria-label="{{ $title }}"></a>
    @endif
    <span class="fas {{ $icon }} text-{{ $tone }} mt-1"></span>
    <div class="flex-1 min-w-0">
        <p class="fs-9 text-body-emphasis mb-1 fw-semibold">{{ $title }}</p>
        @if ($body)
            <p class="fs-9 text-body-tertiary mb-1">{{ $body }}</p>
        @endif
        <p class="fs-10 text-body-tertiary mb-0">{{ $note->created_at->diffForHumans() }}</p>
    </div>
</div>
