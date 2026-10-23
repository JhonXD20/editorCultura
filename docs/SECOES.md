# Seções e navegação por toque

Uma seção reúne páginas internas em uma sequência. A página inicial fica fora das seções. Páginas avulsas e os atalhos existentes continuam disponíveis.

## Uso no painel

1. Em **Conteúdos → Seções e sequência de leitura**, um administrador cria a seção.
2. Abra a seção e use **Adicionar página nesta seção**. A página entra no final como rascunho.
3. Edite e publique pelo fluxo habitual. Editores continuam enviando conteúdo para revisão.
4. Arraste as páginas ou use as setas e clique em **Salvar ordem**. A ordem inclui rascunhos e páginas inativas para facilitar a organização, mas o player só percorre as ativas e publicadas.
5. Para mover uma página existente, abra **Propriedades** na lista de páginas, escolha sua seção e salve. Escolher **Página avulsa** remove sua associação. Páginas movidas ou duplicadas entram no final da seção de destino.

Criação, renomeação, movimentação e ordenação da estrutura publicada são administrativas. Editores podem criar rascunhos em seções existentes. Ordem e nome salvos são aplicados à navegação publicada; isso não publica o conteúdo dos rascunhos. Alterações simultâneas na seção retornam 409 para uma ordem desatualizada; atualize o painel antes de organizar novamente.

## Destinos no editor

Selecione um card, imagem, texto ou botão e, na aba **Conteúdo**, escolha **Ao tocar neste elemento → Abrir seção**, seguido da seção de destino. Também existe o bloco **Card para seção**. A opção **Abrir página** preserva os atalhos para uma página específica.

O destino de seção é o ID da seção, não o da sua página de entrada. Assim, reordenar, desativar ou publicar páginas atualiza a entrada automaticamente. Se a seção estiver vazia ou não tiver páginas ativas e publicadas, o atalho fica indisponível, sem abrir um rascunho.

## Exibição no totem

- Nas páginas de uma seção, um toque parado na metade direita avança e na esquerda retorna. Uma pequena faixa central não navega.
- Na primeira página, retornar não faz nada. Depois da última, avançar retorna à primeira. Uma seção de uma página não navega para ela mesma.
- Links, vídeos, botões, acordeões e carrosséis recebem seus próprios toques; arrastes, rolagens, seleção de texto e toques prolongados não trocam a página.
- As setas do teclado continuam funcionando. Cards e outros destinos também recebem foco e respondem ao teclado.
- O player preserva seu contêiner e a sessão de tela cheia ao trocar conteúdo, com transição e histórico do navegador. O temporizador de inatividade e a medição de permanência passam a acompanhar cada página carregada.
- A barra mantém **Início** e tela cheia. Nas páginas de seção, os botões de sequência são substituídos pelas áreas de toque. Páginas avulsas conservam a barra anterior.
- A sincronização inclui seção e ordem na versão do manifesto. O cache é substituído somente depois de baixar a versão completa, seguindo o mecanismo offline existente.

## Atualização e compatibilidade

Faça backup antes de migrar uma instalação existente, depois aplique as migrações e compile os assets:

```sh
php artisan migrate --force
npm run build
```

A migração `2026_10_19_150000_create_secoes_table` cria `secoes` (`nome`, `versao`) e a chave nullable `paginas.secao_id`. Converte os grupos legados das páginas internas em seções, mantendo IDs, ordem, conteúdo, revisões e datas. Os campos `grupo_slide` e `ordem_slide` continuam no banco; grupos não convertidos ainda possuem a navegação legada.

A reversão remove apenas a estrutura adicionada por essa migração. As associações a seções criadas após a atualização deixam de existir na versão antiga. Não use `migrate:fresh` em um banco com dados a preservar.

Os testes cobrem migração com dados preenchidos e reversão, permissões, inclusão/duplicação/movimentação, conflitos de ordenação, preservação do conteúdo, sequência publicada, manifesto e sanitização. Os cenários de navegador exercitam o painel, destino no editor, controles e navegação offline com preservação do player.
