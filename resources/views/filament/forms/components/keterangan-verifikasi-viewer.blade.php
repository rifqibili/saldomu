{{-- resources/views/filament/forms/components/keterangan-verifikasi-viewer.blade.php --}}
@php
    $keteranganContent = $keteranganContent ?? '';
@endphp

<div class="filament-forms-placeholder-component">
    <div class="filament-forms-placeholder-component-content prose dark:prose-invert max-w-none">
        {{-- HATI-HATI: Menggunakan {!! ... !!} tanpa sanitasi yang tepat dapat menimbulkan risiko XSS. --}}
        {{-- Pastikan $keteranganContent sudah disanitasi dengan benar di PHP sebelum dilewatkan ke view ini. --}}
        {!! $keteranganContent !!}
    </div>
</div>
