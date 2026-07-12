@props(['cols' => [], 'empty' => false, 'emptyText' => 'Nothing here yet.', 'emptyIcon' => 'fa-inbox', 'loading' => true])

<div {{ $attributes->merge(['class' => 'card admin-card']) }}>
    @if (isset($toolbar))
        <div class="card-header  admin-table-toolbar d-flex flex-wrap gap-3 justify-content-between align-items-center">
            {{ $toolbar }}
        </div>
    @endif
    <div class="table-responsive scrollbar position-relative" @if ($loading) data-admin-table @endif>
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    @foreach ($cols as $col)
                        @php($c = is_array($col) ? $col : ['label' => $col])
                        <th class="text-{{ $c['align'] ?? 'start' }}" @isset($c['width']) style="width: {{ $c['width'] }};" @endisset>{{ $c['label'] ?? '' }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="">
                @if ($empty)
                    <tr>
                        <td colspan="{{ max(count($cols), 1) }}">
                            <x-admin.empty-state :text="$emptyText" :icon="$emptyIcon" />
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>

        {{-- Navigation loading skeleton — revealed by table-loading.js the instant a
             filter/pagination/tab reload starts, so the list never blanks out mid-request. --}}
        @if ($loading)
            <div class="admin-table-loading" aria-hidden="true">
                <x-admin.skeleton type="table" :rows="8" :cols="max(count($cols), 1)" />
            </div>
        @endif
    </div>
</div>

@once
    @push('scripts')
        <script src="{{ asset('theme/js/table-loading.js') }}" defer></script>
    @endpush
@endonce
