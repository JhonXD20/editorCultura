@php
    $usuario = Auth::user();
    $nomeUsuario = trim($usuario->name ?: 'Usuário');
    $iniciais = collect(preg_split('/\s+/u', $nomeUsuario))->filter()->take(2)
        ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('');
    $papel = ['admin' => 'Administrador', 'editor' => 'Editor', 'pairing' => 'Pareamento'][$usuario->perfil] ?? 'Usuário';
@endphp
<li class="nav-item dropdown user-menu editor-user-menu">
    <button type="button" class="nav-link dropdown-toggle editor-user-trigger" data-toggle="dropdown"
            aria-haspopup="true" aria-expanded="false" aria-label="Abrir menu de {{ $nomeUsuario }}">
        <span class="editor-user-avatar" aria-hidden="true">{{ $iniciais ?: 'U' }}</span>
        <span class="editor-user-summary d-none d-sm-flex"><strong>{{ $nomeUsuario }}</strong><small>{{ $papel }}</small></span>
        <i class="fas fa-chevron-down editor-user-chevron" aria-hidden="true"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-right editor-user-dropdown">
        <div class="editor-user-card-header">
            <span class="editor-user-avatar editor-user-avatar-lg" aria-hidden="true">{{ $iniciais ?: 'U' }}</span>
            <div class="editor-user-identity"><strong>{{ $nomeUsuario }}</strong><span>{{ $usuario->email }}</span></div>
        </div>
        <div class="editor-user-session">
            <span class="editor-user-status-dot" aria-hidden="true"></span><span>Sessão ativa</span><small>{{ $papel }}</small>
        </div>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="{{ route('account.edit') }}"><i class="fas fa-user mr-2"></i> Meu perfil</a>
        <form action="{{ route('logout') }}" method="POST" class="editor-logout-form">
            @csrf
            <button type="submit" class="dropdown-item editor-logout-action">
                <span class="editor-logout-icon" aria-hidden="true"><i class="fas fa-sign-out-alt"></i></span>
                <span><strong>Sair do sistema</strong><small>Encerrar esta sessão com segurança</small></span>
            </button>
        </form>
    </div>
</li>
