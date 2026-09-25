{{-- <x-form.select name="brand_id" label="Merek" :options="[id => label]" :value="$car->brand_id" placeholder="Pilih merek" required /> --}}
@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
])

@php
    $id = $attributes->get('id', $name);
    $selected = (string) old($name, $value);
@endphp

<div class="mb-3">
    <label for="{{ $id }}" class="form-label">
        {{ $label }}@if ($required)<span class="text-danger ms-1" aria-hidden="true">*</span>@endif
    </label>
    <select id="{{ $id }}" name="{{ $name }}" @required($required)
            {{ $attributes->except('id')->class(['form-select', 'is-invalid' => $errors->has($name)]) }}>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @else
        @if ($help)
            <div class="form-text">{{ $help }}</div>
        @endif
    @enderror
</div>
