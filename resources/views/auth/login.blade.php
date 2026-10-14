@extends('adminlte::master')

@section('title', 'Acesso administrativo | Editor Cultura')

@section('classes_body', 'editor-login-page')

@section('adminlte_css')
    @include('partials.theme-loader')
    @vite('resources/css/login.css')
@stop

@section('body')
    <main class="editor-login-shell">
        <button type="button" class="editor-login-theme-toggle" data-theme-toggle aria-label="Alternar tema">
            <i class="fas fa-moon" data-theme-icon aria-hidden="true"></i>
            <span data-theme-label>Tema escuro</span>
        </button>

        <div class="editor-login-ambient" aria-hidden="true">
            <span></span><span></span><span></span>
        </div>

        <section class="editor-login-showcase" aria-label="Apresentação do Editor Cultura">
            <div class="editor-login-brand">
                <span class="editor-login-brand-mark" aria-hidden="true">
                    <i></i><i></i><i></i>
                </span>
                <span>
                    <strong>Editor Cultura</strong>
                    <small>Plataforma de experiências digitais</small>
                </span>
            </div>

            <div class="editor-login-showcase-copy">
                <span class="editor-login-eyebrow">ACERVO &middot; MEMÓRIA &middot; TECNOLOGIA</span>
                <h1>Histórias ganham<br><em>novas formas.</em></h1>
                <p>
                    Crie, publique e acompanhe experiências culturais em uma
                    plataforma pensada para conectar memória e futuro.
                </p>
            </div>

            <div class="editor-login-showcase-footer">
                <span><i class="fas fa-layer-group" aria-hidden="true"></i> Gestão integrada</span>
                <span><i class="fas fa-shield-alt" aria-hidden="true"></i> Ambiente seguro</span>
            </div>
        </section>

        <section class="editor-login-panel">
            <div class="editor-login-card">
                <div class="editor-login-card-heading">
                    <span class="editor-login-access-icon" aria-hidden="true">
                        <i class="fas fa-fingerprint"></i>
                    </span>
                    <span class="editor-login-kicker">ACESSO RESTRITO</span>
                    <h2>Bem-vindo de volta</h2>
                    <p>Entre com suas credenciais para acessar o painel.</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="editor-login-form">
                    @csrf

                    <div class="editor-login-field">
                        <label for="email">E-mail</label>
                        <div class="editor-login-input @error('email') has-error @enderror">
                            <i class="far fa-envelope" aria-hidden="true"></i>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                placeholder="nome@instituicao.edu.br" autocomplete="email" required autofocus>
                        </div>
                        @error('email')
                            <span class="editor-login-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="editor-login-field">
                        <div class="editor-login-label-row">
                            <label for="password">Senha</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}">Esqueceu a senha?</a>
                            @endif
                        </div>
                        <div class="editor-login-input @error('password') has-error @enderror">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                            <input id="password" type="password" name="password" placeholder="Digite sua senha"
                                autocomplete="current-password" required>
                            <button type="button" class="editor-login-password-toggle" data-password-toggle
                                aria-label="Mostrar senha" aria-pressed="false">
                                <i class="far fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error('password')
                            <span class="editor-login-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <label class="editor-login-remember" for="remember">
                        <input id="remember" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span aria-hidden="true"><i class="fas fa-check"></i></span>
                        Manter meu acesso neste dispositivo
                    </label>

                    <button type="submit" class="editor-login-submit">
                        <span>Entrar no painel</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>

                <div class="editor-login-card-footer">
                    <i class="fas fa-lock" aria-hidden="true"></i>
                    Acesso exclusivo para usuários autorizados
                </div>
            </div>
        </section>
    </main>
@stop

@section('adminlte_js')
    @vite(['resources/js/theme.js', 'resources/js/login.js'])
@stop
