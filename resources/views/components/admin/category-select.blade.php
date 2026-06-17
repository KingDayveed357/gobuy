@props([
    'options' => [],
    'name' => 'category_id',
    'selected' => null,
    'includeNone' => false,
    'noneLabel' => 'None (top level)',
    'placeholder' => 'Select a category',
])

<select name="{{ $name }}" {{ $attributes->merge(['class' => 'form-select']) }}>
    <option value="">{{ $includeNone ? $noneLabel : $placeholder }}</option>
    @foreach ($options as $option)
        <option value="{{ $option['id'] }}" @selected((int) $selected === (int) $option['id'])>
            {!! str_repeat('&nbsp;&nbsp;&nbsp;', $option['depth']) !!}{{ $option['depth'] > 0 ? '↳ ' : '' }}{{ $option['name'] }}
        </option>
    @endforeach
</select>
