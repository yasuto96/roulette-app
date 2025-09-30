@extends('layouts.app')
@section('content')
<div class="container py-4">
  <h1>カテゴリ一覧</h1>
  <ul>
    @foreach($cuisines as $c)
      <li>{{ $c->name }}</li>
    @endforeach
  </ul>
</div>
@endsection
