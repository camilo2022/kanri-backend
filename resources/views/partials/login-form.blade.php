<form id="login-form" method="POST" action="{{ $action }}">
    @csrf

    <div class="login-field">
        <label class="form-label font-inter" for="username">Usuario</label>
        <div class="login-input {{ old('username') ? 'has-value' : '' }} {{ $errors->has('username') ? 'input-group-error' : '' }}">
            <span class="input-group-text">
                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M406.5 399.6C387.4 352.9 341.5 320 288 320l-64 0c-53.5 0-99.4 32.9-118.5 79.6C69.9 362.2 48 311.7 48 256C48 141.1 141.1 48 256 48s208 93.1 208 208c0 55.7-21.9 106.2-57.5 143.6zm-40.1 32.7C334.4 452.4 296.6 464 256 464s-78.4-11.6-110.5-31.7c7.3-36.7 39.7-64.3 78.5-64.3l64 0c38.8 0 71.2 27.6 78.5 64.3zM256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-272a40 40 0 1 1 0-80 40 40 0 1 1 0 80zm-88-40a88 88 0 1 0 176 0 88 88 0 1 0 -176 0z"></path></svg>
            </span>
            <span class="input-group-text login-at font-inter">@</span>
            <input id="username" name="username" type="text" class="form-control font-inter" placeholder="Ingresa tu usuario" autocomplete="username" maxlength="100" value="{{ old('username') }}">
        </div>
        @if ($errors->has('username'))
            <div class="field-error font-inter">{{ $errors->first('username') }}</div>
        @endif
    </div>

    <div class="login-field last">
        <div class="login-field-top">
            <label class="form-label font-inter" for="password">Contraseña</label>
            <a class="login-link font-inter" href="{{ rtrim(config('app.frontend_url'), '/') }}/forgot">¿Olvidaste tu contraseña?</a>
        </div>
        <div class="login-input {{ $errors->has('password') ? 'input-group-error' : '' }}">
            <span class="input-group-text">
                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M6 9V7.25C6 3.845 8.503 1 12 1s6 2.845 6 6.25V9h.5a2.5 2.5 0 0 1 2.5 2.5v8a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 19.5v-8A2.5 2.5 0 0 1 5.5 9Zm-1.5 2.5v8a1 1 0 0 0 1 1h13a1 1 0 0 0 1-1v-8a1 1 0 0 0-1-1h-13a1 1 0 0 0-1 1Zm3-4.25V9h9V7.25c0-2.67-1.922-4.75-4.5-4.75-2.578 0-4.5 2.08-4.5 4.75Z"></path></svg>
            </span>
            <input id="password" name="password" type="password" class="form-control font-inter" placeholder="Ingresa la contraseña" autocomplete="current-password">
            <span id="toggle-password" class="input-group-text cursor-pointer">
                <svg id="icon-eye" stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M256 105c-101.8 0-188.4 62.4-224 151 35.6 88.6 122.2 151 224 151s188.4-62.4 224-151c-35.6-88.6-122.2-151-224-151zm0 251.7c-56 0-101.8-45.3-101.8-100.7S200 155.3 256 155.3 357.8 200.6 357.8 256 312 356.7 256 356.7zm0-161.1c-33.6 0-61.1 27.2-61.1 60.4s27.5 60.4 61.1 60.4 61.1-27.2 61.1-60.4-27.5-60.4-61.1-60.4z"></path></svg>
                <svg id="icon-eye-off" style="display:none" stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M256.1 144.8c56.2 0 101.9 45.3 101.9 101.1 0 13.1-2.6 25.5-7.3 37l59.5 59c30.8-25.5 55-58.4 69.9-96-35.3-88.7-122.3-151.6-224.2-151.6-28.5 0-55.8 5.1-81.1 14.1l44 43.7c11.6-4.6 24.1-7.3 37.3-7.3zM52.4 89.7l46.5 46.1 9.4 9.3c-33.9 26-60.4 60.8-76.3 100.8 35.2 88.7 122.2 151.6 224.1 151.6 31.6 0 61.7-6.1 89.2-17l8.6 8.5 59.7 59 25.9-25.7L78.2 64 52.4 89.7zM165 201.4l31.6 31.3c-1 4.2-1.6 8.7-1.6 13.1 0 33.5 27.3 60.6 61.1 60.6 4.5 0 9-.6 13.2-1.6l31.6 31.3c-13.6 6.7-28.7 10.7-44.8 10.7-56.2 0-101.9-45.3-101.9-101.1 0-15.8 4.1-30.7 10.8-44.3zm87.8-15.7l64.2 63.7.4-3.2c0-33.5-27.3-60.6-61.1-60.6l-3.5.1z"></path></svg>
            </span>
        </div>
        @if ($errors->has('password'))
            <div class="field-error font-inter">{{ $errors->first('password') }}</div>
        @endif
    </div>

    <button id="submit-login" type="submit" class="btn-login font-poppins">
        <span id="submit-label">
            Iniciar sesión
            <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </span>
        <span id="submit-wait" style="display:none">
            <span class="spinner"></span>
            Iniciando sesión…
        </span>
    </button>
</form>

<script>
    var username = document.getElementById('username');
    var password = document.getElementById('password');
    var usernameBox = username.parentNode;

    username.addEventListener('input', function () {
        usernameBox.classList.toggle('has-value', username.value !== '');
    });

    document.getElementById('toggle-password').addEventListener('click', function () {
        var visible = password.type === 'text';
        password.type = visible ? 'password' : 'text';
        document.getElementById('icon-eye').style.display = visible ? '' : 'none';
        document.getElementById('icon-eye-off').style.display = visible ? 'none' : '';
    });

    document.getElementById('login-form').addEventListener('submit', function () {
        var button = document.getElementById('submit-login');
        document.getElementById('submit-label').style.display = 'none';
        document.getElementById('submit-wait').style.display = 'inline-flex';
        setTimeout(function () { button.disabled = true; }, 0);
    });

    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        document.getElementById('submit-login').disabled = false;
        document.getElementById('submit-label').style.display = '';
        document.getElementById('submit-wait').style.display = 'none';
    });
</script>
