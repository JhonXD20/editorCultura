# Envio de e-mail

Criação de conta, recuperação pública e reset administrativo enviam links de uso único, válidos por 30 minutos. O destinatário define a própria senha; nenhuma senha é enviada por e-mail. O token é armazenado somente como hash em `password_reset_tokens`. O painel, a API e a auditoria não mostram o link.

## SMTP independente de provedor

O fluxo não depende de Brevo nem de assinatura específica. Use o SMTP institucional ou um serviço de e-mail transacional compatível. O mecanismo por link ainda exige transporte de e-mail; ele substitui o envio de senhas, não o serviço de entrega.

1. Obtenha host, porta, credencial SMTP e remetente autorizado no serviço escolhido. Para um domínio próprio, configure SPF, DKIM e DMARC conforme as orientações desse serviço.
2. Copie somente o bloco de `.env.smtp.example` para o `.env` existente. Preserve APP_KEY, banco e demais configurações. Preencha as credenciais diretamente no computador, sem enviar por chat nem versionar.
3. O exemplo usa porta 587 e STARTTLS obrigatório. Ajuste conforme a documentação do serviço; para TLS implícito na porta 465 use `MAIL_SCHEME=smtps`. Não desative a verificação de certificados.
4. Configure `APP_URL` com a origem HTTPS pública da aplicação. Os links usam esta origem configurada, sem confiar no cabeçalho Host da solicitação.
5. Execute `php artisan config:clear`. Em produção, gere novamente o cache de configuração conforme o procedimento do ambiente.
6. Crie uma conta com um endereço que você controla, abra o convite, defina a senha e faça login. Solicite recuperação e valide que o link funciona uma vez. Confira spam e entrega no serviço sem registrar tokens em tickets ou capturas públicas.

O `.env` local permanece com `MAIL_MAILER=log`. Esse transporte não envia mensagens e é recusado na criação de conta e no envio administrativo. Fallback para log também é recusado; não grave links reais em logs. A entrega externa permanece pendente até configurar o SMTP. A aceitação pelo transporte não garante chegada à caixa postal.

## Convite e recuperação

- Contas novas recebem `convite_pendente=true` e uma senha aleatória desconhecida, armazenada como hash e nunca enviada. Login e sessões são bloqueados até a conclusão pelo link. Contas existentes não têm suas senhas alteradas pela migração.
- **Enviar link** e **Reenviar convite** exigem administrador, senha atual do administrador e confirmação do e-mail do destinatário. Há intervalo mínimo de um minuto entre emissões por conta e limites por rota. Contas inativas não recebem links.
- Solicitar um reset mantém a senha e as sessões atuais. Ao concluir pelo link, a nova senha é gravada, o token é eliminado, os tokens de lembrar acesso são invalidados e as sessões anteriores são revogadas. É necessário fazer login com a nova senha.
- Reemitir com sucesso invalida o link anterior. Abrir a página por GET não consome o token, permitindo inspeção de links por serviços de e-mail. Validação e consumo acontecem em uma transação com bloqueio da conta para impedir dois usos concorrentes.
- A recuperação pública responde com a mesma mensagem para conta inexistente, inativa, solicitação recente e falha de entrega, sem revelar esses estados no corpo. Há limite de cinco solicitações por minuto por IP. Isso não é uma garantia de igualdade de tempo de resposta.
- Alterar e-mail ou senha invalida os links anteriores. Desativar a conta impede resgate. A página que recebe o token usa `Referrer-Policy: no-referrer` e `Cache-Control: no-store`; token e senha não são copiados para o preenchimento antigo da sessão em erros de validação. Na infraestrutura futura, restrinja também o registro de URLs de recuperação no proxy e em ferramentas de monitoramento.

## Comportamento em falhas

O envio é síncrono, dentro da transação de criação/emissão, sem token em filas ou jobs de falha. Se o transporte falhar ou cancelar a mensagem, a criação é desfeita ou o token anterior é restaurado. A senha e as sessões existentes ficam preservadas. A interface administrativa retorna erro sem detalhes do transporte, que poderiam conter a mensagem. Não há repetição automática da mutação após erro de rede.

O banco e o serviço SMTP não formam uma transação distribuída: uma falha após a aceitação pelo SMTP pode deixar um link sem efeito. Solicitar outro link permite recuperar o acesso após resolver a falha.

## Testes

Testes PHP usam `Mail::fake` ou o transporte `array` em `testing`, sem destinatários externos. Cobrem convite, token armazenado como hash, validade, reutilização, reemissão, conta inativa, ausência do link na resposta administrativa, falha de transporte, cancelamento, preservação e revogação de sessões, limites de solicitações e invalidação após alterações de conta.

O servidor isolado do Playwright habilita uma caixa postal em `storage/framework/testing/mailbox.jsonl`, fora do Git e sem rota HTTP. A captura exige `testing` e a flag exclusiva `E2E_CAPTURE_MAIL`; não funciona em local/produção. Os testes obtêm os links dessa caixa e concluem a definição de senha pelo navegador.
