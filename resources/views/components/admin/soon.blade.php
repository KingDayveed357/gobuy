{{-- Marks a section ported from the template that is not yet wired to the backend. --}}
<span {{ $attributes->merge(['class' => 'admin-soon-badge']) }}>
    <span class="fas fa-clock"></span>{{ $slot->isEmpty() ? 'Not yet functional' : $slot }}
</span>
