@props(['color' => 'slate', 'label' => null])

<span {{ $attributes->merge(['class' => 'badge badge-'.$color]) }}>{{ $label ?? $slot }}</span>
