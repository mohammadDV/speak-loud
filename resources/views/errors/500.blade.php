@extends('errors.layout')

@section('title', 'Something went wrong')

@section('content')
    <div class="eyebrow">Server error</div>
    <p class="code" aria-hidden="true">500</p>
    <h1>Something went wrong on our side.</h1>
    <p class="lead">
        We’ve logged the problem and will look into it. Please try again in a moment, or reach out if it keeps happening.
    </p>
    <div class="actions">
        <a href="{{ url('/') }}" class="btn btn-primary">Back to home</a>
        <a href="{{ url('/support') }}" class="btn btn-secondary">Get support</a>
        <a href="{{ url('/contact') }}" class="btn btn-secondary">Contact us</a>
    </div>
@endsection
