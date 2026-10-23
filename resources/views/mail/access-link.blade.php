<x-mail::message>
# {{ $isInvitation ? 'Defina sua senha de acesso' : 'Redefina sua senha' }}

Olá, {{ $recipientName }}.

{{ $isInvitation ? 'Uma conta foi criada para você no TotemSync. Escolha sua própria senha para ativar o acesso.' : 'Recebemos uma solicitação para redefinir a senha da sua conta no TotemSync.' }}

<x-mail::button :url="$accessUrl">Definir senha</x-mail::button>

Este link é de uso único e vence em {{ $expiresAt->format('d/m/Y \à\s H:i') }} ({{ $expiresAt->timezoneName }}).

Se você não reconhece esta solicitação, ignore este e-mail. Sua senha atual permanece válida até a conclusão da redefinição.

Nunca compartilhe este link. O TotemSync não envia senhas por e-mail.
</x-mail::message>
