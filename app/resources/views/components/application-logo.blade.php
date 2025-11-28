@props(['class' => '', 'size' => 80, 'path' => 'images/title.png'])

@php
    // 公開パスの絶対パスを取得して更新時刻をバージョンに
    $abs = public_path($path);
    $ver = is_file($abs) ? filemtime($abs) : time();
    $src = asset($path) . '?v=' . $ver;
@endphp

<img
  src="{{ $src }}"
  alt="アプリロゴ"
  style="width: {{ (int)$size }}px; height: auto; max-height: {{ (int)$size }}px; display:inline-block !important;"
  {{ $attributes->merge(['class' => $class]) }}
/>
