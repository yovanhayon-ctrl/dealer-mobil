{{--
    Checkbox boolean (value 1). Setelah validasi gagal, status mengikuti old();
    checkbox yang tidak dicentang memang tidak terkirim, jadi old() kosong = tidak dicentang.
--}}
@props([
    'name',
    'label',
    'checked' => false,
    'help' => null,
])

@php
    $id = $attributes->get('id', $name);
    $isChecked = $errors->any() ? (bool) old($name) : (bool) $checked;
@endphp

<div class="mb-3">
    <div class="form-check form-switch">
        <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" @checked($isChecked)
               {{ $attributes->except('id')->class(['form-check-input', 'is-invalid' => $errors->has($name)]) }}>
        <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
        @error($name)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
