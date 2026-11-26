@extends('layouts.auth')

@section('title', 'Sin permiso')

@section('card')
    <div class="login-card-head">
        <h2 class="font-poppins">No tienes permiso</h2>
        <p class="font-inter">
            El usuario {{ $username }} no puede conectar el asistente. Pide a un administrador de Kanri que te dé el
            permiso.
        </p>
    </div>
@endsection
