# UX assíncrona e credenciais de usuários

Atualizado em 20/10/2026. As migrações de contas adicionam campos; não redefinem senhas existentes.

## Uso

- **Usuários → Novo usuário:** e-mail como login; nascimento não é necessário. A conta recebe convite de uso único por e-mail, válido por 30 minutos, para o usuário escolher a própria senha. A conta fica pendente até a conclusão e nenhuma senha é enviada. O link não aparece na resposta da API, sessão, painel, auditoria ou notificações persistentes.
- O servidor bloqueia login e sessões de contas pendentes. Credenciais antigas que coincidam com a data cadastrada são recusadas no login, mesmo sem o indicador de troca: o administrador pode enviar um link ou o usuário pode utilizar a recuperação por e-mail quando configurada. Contas antigas com indicador de senha temporária conservam a restrição de troca até alterar a senha ou concluir a recuperação; novas contas usam somente convites.
- Nova senha: entre 8 e 128 caracteres, sem exigir combinações de letras, números ou símbolos, confirmação obrigatória e diferente do nascimento. Na troca autenticada, exige também diferença da senha atual e preserva somente a sessão atual, com novo identificador. Ao concluir um convite ou uma recuperação, todas as sessões anteriores são revogadas e é necessário fazer login.
- **Menu do usuário → Meu perfil:** permite alterar nome/e-mail mediante senha atual e modal AdminLTE. Não permite alterar nascimento, papel ou estado da conta.
- **Usuários → Editar conta:** nascimento pode ser cadastrado para contas antigas. Alterar a data não altera silenciosamente a senha. Mudanças administrativas encerram as sessões da conta modificada.
- **Enviar link / Reenviar convite / Revogar todas as sessões:** somente administrador, confirmação por e-mail exato do destinatário e senha atual do administrador, validados no backend, com limitação de tentativas. Enviar link mantém a senha e as sessões atuais, inclusive se solicitado para a própria conta. A nova senha é definida pelo destinatário ao concluir o link; nesse momento são revogadas todas as sessões e o token de lembrar login. A ação separada de revogar sessões tem efeito imediato e encerra a sessão administrativa quando aplicada à própria conta.
- Recuperação pública em **Esqueceu a senha?** e convite usam o mesmo token com hash no banco, uso único e validade de 30 minutos. Reemitir invalida o link anterior; concluir exige novo login. Há espera mínima de um minuto entre envios por conta e resposta pública genérica para evitar expor estados da conta no corpo da resposta.
- O envio é síncrono e não armazena tokens em fila. Falha de transporte desfaz a criação ou restaura o token anterior; não altera senhas nem sessões. Transportes `log`, `array` fora de testes e fallback para log não são aceitos como envio real. Confira [a configuração SMTP independente de provedor](EMAIL.md).

## Navegação

`admin-navigation.js` atualiza somente `#adminMainContent` e o título do conteúdo. Os mesmos elementos de menu/header são preservados; identidade, contagem de notificações e item ativo são atualizados sem recriar o shell. Há suporte ao histórico do navegador, paginação e formulários GET/POST com `_method` e CSRF.

Formulários de mídia, perfil e usuários usam JSON. Os demais formulários administrativos reaproveitam as views renderizadas pelo servidor, sem executar scripts arbitrários da resposta. Validações de submissões parciais retornam JSON 422, mantêm o preenchimento e exibem erros/toasts AdminLTE. Envios simultâneos são bloqueados; mutações não são repetidas automaticamente após falha de rede.

Cards de mídia só são removidos após resposta bem-sucedida com o ID esperado. Cancelamento, erro de rede ou mídia em uso preservam o card. Upload direciona à primeira página da biblioteca para mostrar o novo arquivo.

Gráficos e temporizadores usam `admin-lifecycle.js` para inicialização/limpeza a cada troca parcial. O dashboard atualiza o conteúdo sem `location.reload()`.

**Limite deliberado:** entrada/saída do GrapesJS, autenticação/logout, pareamento e player continuam com navegação de documento. O editor mantém seu ciclo de vida e proteção de rascunhos; o player tem atualização/offline próprios. Não é uma conversão completa para framework SPA. O template inicial ainda é entregue normalmente pelo Laravel; com JavaScript desativado, ações com confirmação ficam indisponíveis.

## Segurança e validação

A data de nascimento deixou de ser usada para criar ou resetar credenciais. Contas antigas que ainda tenham a data como senha não podem iniciar novas sessões com ela; precisam de reset administrativo ou recuperação por e-mail. A data é omitida da serialização JSON do modelo; fica visível apenas no formulário administrativo autorizado. A troca de senha e a recuperação por e-mail removem a validade temporária e revogam as sessões anteriores. Hashes de senhas existentes não foram alterados automaticamente.

Testes: `composer test`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse --no-progress`, `npm test`, `npm run build` e `npx playwright test`. A suíte de navegador usa banco isolado, duas execuções simultâneas para o servidor PHP local e contas exclusivas por perfil para não compartilhar limites de tentativas. Não usa o banco local da aplicação.
