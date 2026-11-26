@extends('layouts.auth')

@section('title', 'Autorizar aplicación')

@section('card')
    <div class="login-card-head">
        <h2 class="font-poppins">Autorizar a {{ $client->name }}</h2>
        <p class="font-inter">Revisa los datos antes de continuar</p>
    </div>

    <div class="data-box font-inter">
        <span>Esta aplicación trabajará a nombre del usuario</span>
        {{ $user->username }}
    </div>

    <div class="data-box font-inter">
        <span>Al terminar volverá a este sitio</span>
        {{ parse_url((string) request()->query('redirect_uri'), PHP_URL_HOST) ?: 'desconocido' }}
    </div>

    @if (count($scopes) > 0)
        <p class="scope-title font-inter">Podrá:</p>
        <ul class="scope-list font-inter">
            @foreach ($scopes as $scope)
                <li>{{ $scope->description }}</li>
            @endforeach
        </ul>
    @endif

    <div class="button-row">
        <form method="POST" action="{{ route('passport.authorizations.deny') }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="state" value="">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn-login btn-login-outline font-poppins">Cancelar</button>
        </form>

        <form method="POST" action="{{ route('passport.authorizations.approve') }}">
            @csrf
            <input type="hidden" name="state" value="">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn-login font-poppins">Autorizar</button>
        </form>
    </div>
@endsection
