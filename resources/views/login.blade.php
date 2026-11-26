@extends('layouts.auth')

@section('title', 'Iniciar sesión')

@section('card')
    <div class="login-card-head">
        <h2 class="font-poppins">¡Bienvenido!</h2>
        <p class="font-inter">Ingresa con el usuario de Kanri para continuar</p>
    </div>

    @include('partials.login-form', ['action' => url('/login')])
@endsection
