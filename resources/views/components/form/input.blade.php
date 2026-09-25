@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'help' => null,
])

@php
    $id = $attributes->get('id', $name);
@endphp

<div class="mb-3">
    <label for="{{ $id }}" class="form-label">
        {{ $label }}@if ($required)<span class="text-danger ms-1" aria-hidden="true">*</span>@endif
    </label>
    <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}"
           @if ($type !== 'file') value="{{ old($name, $value) }}" @endif
           @required($required)
           {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @else
        @if ($help)
            <div class="form-text">{{ $help }}</div>
        @endif
    @enderror
</div>
