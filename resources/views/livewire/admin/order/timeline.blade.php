<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title mb-4">Order Timeline</h3>
        <p class="fs-9 text-body-tertiary mb-4">Complete chronological history of the order.</p>

        <div class="timeline-vertical mb-5">
            @foreach ($events as $event)
                <div class="timeline-item">
                    <div class="row g-2 align-items-center {{ $loop->last ? 'mb-0' : 'mb-3' }}">
                        <div class="col-auto d-flex">
                            <div class="timeline-item-bar position-relative me-3 {{ $loop->last ? 'pb-0' : '' }}">
                                <div class="icon-item icon-item-sm bg-{{ $event->color }}" data-bs-theme="light">
                                    <span class="{{ $event->icon }} text-white fs-10"></span>
                                </div>
                                @if (!$loop->last)
                                    <span class="timeline-bar border-end border-{{ $event->color }}"></span>
                                @endif
                            </div>
                        </div>
                        <div class="col">
                            <h6 class="mb-0">{{ $event->title }} <span class="fs-10 text-body-tertiary fw-normal">— {{ $event->date->format('M j, g:i A') }}</span></h6>
                            @if ($event->description)
                                <p class="fs-9 text-body-secondary mb-0">{{ $event->description }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
