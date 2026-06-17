@props(['cols' => [], 'empty' => false, 'emptyText' => 'Nothing here yet.', 'emptyIcon' => 'fa-inbox'])

<div {{ $attributes->merge(['class' => 'card admin-card']) }}>
    @if (isset($toolbar))
        <div class="card-header  admin-table-toolbar d-flex flex-wrap gap-3 justify-content-between align-items-center">
            {{ $toolbar }}
        </div>
    @endif
    <div class="table-responsive scrollbar">
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
    </div>
</div>
