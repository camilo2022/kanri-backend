@extends('layouts.auth')

@section('title', 'Iniciar sesión en el chat')

@section('card')
    <div class="login-card-head">
        <h2 class="font-poppins">¡Bienvenido!</h2>
        <p class="font-inter">Ingresa con el usuario de Kanri para trabajar en el chat. Al terminar, vuelve a la conversación.</p>
    </div>

    @include('partials.login-form', ['action' => route('assistant.login.submit', ['code' => $code])])
@endsection
