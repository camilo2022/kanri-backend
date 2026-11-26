@extends('layouts.auth')

@section('title', 'Sesión iniciada')

@section('card')
    <div class="login-card-head">
        <h2 class="font-poppins">¡Listo, {{ $username }}!</h2>
        <p class="font-inter">Ya iniciaste sesión. Vuelve al chat y dile al asistente que ya terminaste.</p>
    </div>
@endsection
