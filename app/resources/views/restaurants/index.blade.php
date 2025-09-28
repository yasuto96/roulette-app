@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Restaurants</h1>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>ID</th><th>Name</th><th>Address</th><th>Source</th>
      </tr>
    </thead>
    <tbody>
    @foreach ($restaurants as $r)
      <tr>
        <td>{{ $r->id }}</td>
        <td>{{ $r->name }}</td>
        <td>{{ $r->address }}</td>
        <td>{{ $r->source }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>

  {{ $restaurants->links() }}
</div>
@endsection
