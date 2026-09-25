{{-- Format Rupiah: <x-price :amount="285000000" /> → Rp 285.000.000 --}}
@props(['amount'])

<span {{ $attributes->class(['text-nowrap']) }}>Rp {{ number_format((int) $amount, 0, ',', '.') }}</span>
