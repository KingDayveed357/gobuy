@props(['url', 'name', 'image' => null, 'price' => null, 'campaign' => null])

@php($slug = \Illuminate\Support\Str::slug($campaign ?: $name))

<button type="button" {{ $attributes->merge(['class' => 'btn btn-sm btn-phoenix-info js-promote']) }}
    data-bs-toggle="modal" data-bs-target="#promoteModal"
    data-promote-url="{{ $url }}"
    data-promote-name="{{ $name }}"
    data-promote-image="{{ $image }}"
    data-promote-price="{{ $price }}"
    data-promote-campaign="{{ $slug }}"
    title="Promote on social">
    <span class="fas fa-bullhorn"></span>
</button>
