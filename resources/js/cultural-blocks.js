export function registerCulturalBlocks(editor, videos = []) {
    editor.DomComponents.addType('cultura-video', {
        isComponent: (element) => element?.tagName === 'VIDEO',
        model: { defaults: {
            tagName: 'video', draggable: true, droppable: false, resizable: true,
            attributes: { controls: true, playsinline: true, preload: 'metadata' },
            traits: [{ type: 'select', name: 'src', label: 'Vídeo da biblioteca', options: [{ id: '', label: 'Selecione um vídeo' }, ...videos] }],
            style: { width: '560px', height: '315px', 'object-fit': 'contain' },
        } },
    });
    const blocks = [
        ['cultural-video', 'Vídeo local', 'fas fa-film', { type: 'cultura-video' }],
        ['cultural-card', 'Card cultural', 'fas fa-address-card', '<article style="padding:24px;background:#fff;border:1px solid #ddd;border-radius:12px;width:360px"><h3>Acervo em destaque</h3><p>Conte a história deste item.</p><button type="button" data-cultura-navigation="1" data-page-id="">Saiba mais</button></article>'],
        ['cultural-gallery', 'Galeria', 'fas fa-images', { tagName: 'section', style: { display: 'grid', 'grid-template-columns': 'repeat(3, 1fr)', gap: '20px', width: '90%' }, components: [1, 2, 3].map(() => ({ tagName: 'figure', components: [{ type: 'image', attributes: { alt: 'Descreva a imagem' }, style: { width: '100%', height: '220px', 'object-fit': 'cover' } }, { tagName: 'figcaption', type: 'text', content: 'Legenda da imagem' }] })) }],
        ['cultural-accordion', 'Acordeão', 'fas fa-list', '<section style="padding:20px;width:600px"><details open><summary>Pergunta ou tema</summary><p>Escreva o conteúdo que será expandido.</p></details><details><summary>Outro tema</summary><p>Mais informações.</p></details></section>'],
        ['cultural-carousel', 'Carrossel', 'fas fa-images', '<section data-cultura-carousel="1" style="width:620px;padding:20px"><div data-cultura-slide="1"><h3>Primeiro destaque</h3><p>Edite o conteúdo e adicione imagens.</p></div><div data-cultura-slide="1"><h3>Segundo destaque</h3><p>Outro conteúdo.</p></div><button type="button" data-carousel-direction="-1" aria-label="Slide anterior">Anterior</button><button type="button" data-carousel-direction="1" aria-label="Próximo slide">Próximo</button></section>'],
        ['cultural-template', 'Modelo de exposição', 'fas fa-landmark', '<main style="width:100%;min-height:768px;background:#f7f3e8;padding:60px"><header><p>EXPOSIÇÃO CULTURAL</p><h1 style="font-size:48px">Uma história para compartilhar</h1></header><section style="display:grid;grid-template-columns:1fr 1fr;gap:32px"><article><h2>Sobre a exposição</h2><p>Apresente seu acervo de forma clara e acessível.</p></article><article><h2>Visite e descubra</h2><p>Inclua horários, contexto histórico e informações.</p></article></section></main>'],
    ];
    for (const [id, label, icon, content] of blocks) editor.BlockManager.add(id, { label, category: 'Cultura', media: `<i class="${icon}"></i>`, content });
}
