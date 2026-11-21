@extends('errors.layout')

@section('title', 'Page not found')

@section('content')
    <div class="eyebrow">Missing page</div>
    <p class="code" aria-hidden="true">404</p>
    <h1>We can’t find that page.</h1>
    <p class="lead">
        The link may be broken, or the page may have moved. Head home, browse partners, or keep practicing German in Learn.
    </p>
    <div class="actions">
        <a href="{{ url('/') }}" class="btn btn-primary">Back to home</a>
        <a href="{{ url('/discover') }}" class="btn btn-secondary">Browse partners</a>
        <a href="{{ url('/learn') }}" class="btn btn-secondary">Open Learn</a>
    </div>
@endsection
