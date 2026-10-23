import grapesjs from 'grapesjs';
import { registerCulturalBlocks } from './cultural-blocks';
import pt from 'grapesjs/locale/pt.mjs';
import 'grapesjs/dist/css/grapes.min.css';
import '../css/grapes-editor.css';

const configElement = document.getElementById('visualEditorConfig');
const appElement = document.getElementById('visualEditorApp');

if (configElement && appElement) {
    const config = JSON.parse(configElement.textContent || '{}');
    const pageOptions = (config.pages || [])
        .filter((page) => page.active)
        .map((page) => ({ id: String(page.id), label: page.title }));
    const sectionOptions = (config.sections || []).map((section) => ({ id: String(section.id), label: section.label }));
    const deviceProfiles = {
        totem: { id: 'totem', name: 'Tablet horizontal', width: 1366, height: 768, widthMedia: 1366, fontScale: 1 },
        tablet: { id: 'tablet', name: 'Tablet vertical', width: 768, height: 1024, widthMedia: 992, fontScale: 0.85 },
        mobile: { id: 'mobile', name: 'Celular', width: 390, height: 844, widthMedia: 480, fontScale: 0.72 },
    };

    const navigationPlugin = (editorInstance) => {
        editorInstance.DomComponents.addType('cultura-navigation', {
            isComponent: (element) => element?.tagName === 'BUTTON' && element?.getAttribute?.('data-cultura-navigation') === '1',
            model: {
                defaults: {
                    tagName: 'button',
                    draggable: true,
                    droppable: false,
                    attributes: {
                        type: 'button',
                        'data-cultura-navigation': '1',
                        'data-page-id': pageOptions[0]?.id || '',
                        'aria-label': 'Abrir página',
                    },
                    components: 'Abrir página',
                    resizable: true,
                    traits: [
                        {
                            type: 'select',
                            name: 'data-page-id',
                            label: 'Página de destino',
                            options: pageOptions,
                        },
                        {
                            type: 'text',
                            name: 'aria-label',
                            label: 'Descrição acessível',
                            placeholder: 'Ex.: Conheça nossa história',
                        },
                    ],
                    style: {
                        position: 'absolute',
                        left: '100px',
                        top: '180px',
                        padding: '16px 24px',
                        border: '0',
                        'border-radius': '10px',
                        background: '#16754a',
                        color: '#ffffff',
                        'font-size': '20px',
                        cursor: 'pointer',
                    },
                },
            },
        });
    };

    const starterComponents = `
        <main class="cultura-page">
            <section class="cultura-hero">
                <p class="cultura-eyebrow">TOTEMSYNC</p>
                <h1>Conte uma história que merece ser vista</h1>
                <p class="cultura-lead">Clique duas vezes nos textos para editar. Arraste novos blocos pelo painel à esquerda.</p>
            </section>
            <section class="cultura-content">
                <h2>Comece por aqui</h2>
                <p>Adicione imagens, colunas, listas e botões de navegação. Você pode ajustar cores, tamanhos e espaçamentos no painel à direita.</p>
            </section>
        </main>
    `;

    const starterStyle = `
        * { box-sizing: border-box; }
        body { margin: 0; color: #213129; font-family: Arial, Helvetica, sans-serif; }
        .cultura-page { min-height: 768px; background: #f7f3e8; }
        .cultura-hero { min-height: 440px; padding: 90px 10%; display: flex; flex-direction: column; justify-content: center; background: #174b35; color: #fff; }
        .cultura-eyebrow { margin: 0 0 16px; color: #e3be68; font-size: 16px; font-weight: 700; letter-spacing: 4px; }
        .cultura-hero h1 { max-width: 850px; margin: 0 0 22px; font-size: 58px; line-height: 1.05; }
        .cultura-lead { max-width: 720px; margin: 0; font-size: 23px; line-height: 1.55; }
        .cultura-content { padding: 58px 10%; }
        .cultura-content h2 { margin: 0 0 16px; font-size: 36px; }
        .cultura-content p { max-width: 850px; margin: 0; font-size: 20px; line-height: 1.65; }
    `;

    const editorOptions = {
        container: '#gjs',
        height: '100%',
        width: 'auto',
        fromElement: false,
        dragMode: 'absolute',
        storageManager: false,
        allowScripts: false,
        i18n: {
            locale: 'pt',
            localeFallback: 'pt',
            messages: { pt },
        },
        panels: { defaults: [] },
        canvas: {
            styles: ['/vendor/fontawesome-free/css/all.min.css'],
        },
        plugins: [navigationPlugin, (instance) => registerCulturalBlocks(instance, config.videoAssets || [])],
        blockManager: { appendTo: '#gjsBlocks' },
        traitManager: { appendTo: '#gjsTraits' },
        layerManager: { appendTo: '#gjsLayers' },
        selectorManager: { componentFirst: true },
        deviceManager: {
            devices: Object.values(deviceProfiles).map((device) => ({
                id: device.id,
                name: device.name,
                width: `${device.width}px`,
                height: `${device.height}px`,
                widthMedia: device.widthMedia ? `${device.widthMedia}px` : '',
            })),
        },
        assetManager: {
            assets: config.mediaAssets || [],
            upload: config.uploadUrl,
            uploadName: 'files',
            multiUpload: true,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
        styleManager: {
            appendTo: '#gjsStyles',
            sectors: [
                {
                    name: 'Tamanho e posição',
                    open: true,
                    properties: [
                        'display', 'position', 'width', 'height', 'min-width', 'min-height',
                        'max-width', 'max-height', 'top', 'right', 'bottom', 'left', 'z-index',
                    ],
                },
                {
                    name: 'Organização',
                    open: false,
                    properties: [
                        'flex-direction', 'flex-wrap', 'justify-content', 'align-items',
                        'align-content', 'gap', 'grid-template-columns', 'overflow',
                    ],
                },
                {
                    name: 'Espaçamento',
                    open: false,
                    properties: ['margin', 'padding'],
                },
                {
                    name: 'Texto',
                    open: false,
                    properties: [
                        'font-family', 'font-size', 'font-weight', 'letter-spacing',
                        'color', 'line-height', 'text-align', 'text-decoration', 'text-transform',
                    ],
                },
                {
                    name: 'Cores e bordas',
                    open: false,
                    properties: [
                        'background-color', 'border', 'border-radius', 'box-shadow', 'opacity', 'object-fit',
                    ],
                },
            ],
        },
    };

    if (config.projectData) {
        editorOptions.projectData = config.projectData;
    } else {
        editorOptions.components = starterComponents;
        editorOptions.style = starterStyle;
    }

    const editor = grapesjs.init(editorOptions);
    const blockManager = editor.BlockManager;

    const blocks = [
        {
            id: 'section',
            label: 'Seção',
            category: 'Estrutura',
            media: '<i class="fas fa-square"></i>',
            content: {
                tagName: 'section',
                components: '<h2>Nova seção</h2><p>Escreva o conteúdo desta seção.</p>',
                style: { padding: '48px', 'min-height': '180px' },
            },
        },
        {
            id: 'two-columns',
            label: '2 colunas',
            category: 'Estrutura',
            media: '<i class="fas fa-columns"></i>',
            content: `
                <section style="display:grid;grid-template-columns:1fr 1fr;gap:28px;padding:40px">
                    <div style="padding:20px"><h3>Primeira coluna</h3><p>Edite este texto.</p></div>
                    <div style="padding:20px"><h3>Segunda coluna</h3><p>Edite este texto.</p></div>
                </section>
            `,
        },
        {
            id: 'three-columns',
            label: '3 colunas',
            category: 'Estrutura',
            media: '<i class="fas fa-th-large"></i>',
            content: `
                <section style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;padding:40px">
                    <div style="padding:18px"><h3>Coluna 1</h3><p>Conteúdo.</p></div>
                    <div style="padding:18px"><h3>Coluna 2</h3><p>Conteúdo.</p></div>
                    <div style="padding:18px"><h3>Coluna 3</h3><p>Conteúdo.</p></div>
                </section>
            `,
        },
        {
            id: 'heading',
            label: 'Título',
            category: 'Conteúdo',
            media: '<i class="fas fa-heading"></i>',
            content: '<h2 style="position:absolute;left:80px;top:60px;width:600px;font-size:36px;line-height:1.2">Novo título</h2>',
        },
        {
            id: 'text',
            label: 'Texto',
            category: 'Conteúdo',
            media: '<i class="fas fa-align-left"></i>',
            content: '<p style="position:absolute;left:80px;top:100px;width:520px;font-size:20px;line-height:1.6">Clique duas vezes para editar este texto.</p>',
        },
        {
            id: 'image',
            label: 'Imagem',
            category: 'Conteúdo',
            media: '<i class="fas fa-image"></i>',
            activate: true,
            content: {
                type: 'image',
                attributes: { alt: 'Descreva esta imagem' },
                resizable: true,
                style: { position: 'absolute', left: '100px', top: '160px', width: '420px', height: '280px', 'object-fit': 'cover' },
            },
        },
        {
            id: 'quote',
            label: 'Citação',
            category: 'Conteúdo',
            media: '<i class="fas fa-quote-left"></i>',
            content: '<blockquote style="position:absolute;left:100px;top:180px;width:600px;margin:24px;padding:24px 30px;border-left:6px solid #c99f48;font-size:25px;line-height:1.5">Uma frase de destaque pode ficar aqui.</blockquote>',
        },
        {
            id: 'list',
            label: 'Lista',
            category: 'Conteúdo',
            media: '<i class="fas fa-list-ul"></i>',
            content: '<ul style="position:absolute;left:100px;top:180px;width:420px;font-size:20px;line-height:1.8"><li>Primeiro item</li><li>Segundo item</li><li>Terceiro item</li></ul>',
        },
        {
            id: 'navigation',
            label: 'Botão para página',
            category: 'Interação',
            media: '<i class="fas fa-hand-pointer"></i>',
            content: { type: 'cultura-navigation' },
        },
        {
            id: 'section-entry',
            label: 'Card para seção',
            category: 'Interação',
            media: '<i class="fas fa-layer-group"></i>',
            content: '<article data-cultura-navigation="1" data-section-id="" role="link" style="position:absolute;left:100px;top:180px;width:360px;padding:28px;border-radius:16px;background:#174b35;color:#fff"><h2>Conheça nossa história</h2><p>Selecione a seção de destino nas propriedades.</p></article>',
        },
        {
            id: 'divider',
            label: 'Divisor',
            category: 'Organização',
            media: '<i class="fas fa-minus"></i>',
            content: '<hr style="margin:28px 0;border:0;border-top:1px solid #ccd5cf">',
        },
        {
            id: 'spacer',
            label: 'Espaço',
            category: 'Organização',
            media: '<i class="fas fa-arrows-alt-v"></i>',
            content: '<div aria-hidden="true" style="height:60px"></div>',
        },
    ];

    blocks.forEach((block) => blockManager.add(block.id, block));

    const statusElement = document.getElementById('editorStatus');
    const saveButton = document.getElementById('saveDraftButton');
    const publishButton = document.getElementById('publishButton');
    const undoButton = document.getElementById('undoButton');
    const redoButton = document.getElementById('redoButton');
    const deviceSelector = document.getElementById('deviceSelector');
    const responsiveFitButton = document.getElementById('responsiveFitButton');
    const focusModeButton = document.getElementById('focusModeButton');
    const toggleBlocksButton = document.getElementById('toggleBlocksButton');
    const togglePropertiesButton = document.getElementById('togglePropertiesButton');
    const freePositionButton = document.getElementById('freePositionButton');
    const editElementButton = document.getElementById('editElementButton');
    const duplicateElementButton = document.getElementById('duplicateElementButton');
    const sendBackwardButton = document.getElementById('sendBackwardButton');
    const bringForwardButton = document.getElementById('bringForwardButton');
    const deleteElementButton = document.getElementById('deleteElementButton');
    const deleteCurrentPageForm = document.getElementById('deleteCurrentPageForm');
    const deleteCurrentPageButton = document.getElementById('deleteCurrentPageButton');
    const inspectorEmptyState = document.getElementById('inspectorEmptyState');
    const inspectorFields = document.getElementById('inspectorFields');
    const inspectorElementName = document.getElementById('inspectorElementName');
    const inspectorElementTag = document.getElementById('inspectorElementTag');
    const elementPositionX = document.getElementById('elementPositionX');
    const elementPositionY = document.getElementById('elementPositionY');
    const elementWidthField = document.getElementById('elementWidthField');
    const elementHeightField = document.getElementById('elementHeightField');
    const inspectorTextGroup = document.getElementById('inspectorTextGroup');
    const inspectorButtonGroup = document.getElementById('inspectorButtonGroup');
    const inspectorNavigationGroup = document.getElementById('inspectorNavigationGroup');
    const inspectorImageGroup = document.getElementById('inspectorImageGroup');
    const inspectorLinkGroup = document.getElementById('inspectorLinkGroup');
    const elementTextField = document.getElementById('elementTextField');
    const buttonIconField = document.getElementById('buttonIconField');
    const buttonIconPosition = document.getElementById('buttonIconPosition');
    const buttonIconSize = document.getElementById('buttonIconSize');
    const buttonCustomImageButton = document.getElementById('buttonCustomImageButton');
    const removeButtonImageButton = document.getElementById('removeButtonImageButton');
    const buttonDestinationField = document.getElementById('buttonDestinationField');
    const navigationActionField = document.getElementById('navigationActionField');
    const sectionDestinationField = document.getElementById('sectionDestinationField');
    const changeImageButton = document.getElementById('changeImageButton');
    const imageAltField = document.getElementById('imageAltField');
    const imagePreview = document.getElementById('imagePreview');
    const linkUrlField = document.getElementById('linkUrlField');
    const linkNewWindowField = document.getElementById('linkNewWindowField');

    buttonDestinationField.append(new Option('Selecione uma página', ''));
    sectionDestinationField.append(new Option('Selecione uma seção', ''));
    sectionOptions.forEach((section) => sectionDestinationField.append(new Option(section.label, section.id)));
    pageOptions.forEach((page) => {
        const option = document.createElement('option');
        option.value = page.id;
        option.textContent = page.label;
        buttonDestinationField.append(option);
    });
    let dirty = false;
    let ready = false;
    let changeVersion = 0;
    let freePositionEnabled = true;
    let autosaveTimer = null;
    let savingPromise = null;
    let publishing = false;
    let conflicted = false;

    const setStatus = (message, state = '') => {
        statusElement.textContent = message;
        statusElement.className = `visual-editor-status${state ? ` is-${state}` : ''}`;
    };

    const refreshCanvas = () => {
        window.setTimeout(() => editor.refresh(), 50);
    };

    const setFocusMode = (enabled) => {
        appElement.classList.toggle('is-focus-mode', enabled);
        document.body.classList.toggle('visual-editor-focus', enabled);
        focusModeButton.setAttribute('aria-pressed', String(enabled));
        focusModeButton.querySelector('i').className = enabled
            ? 'fas fa-compress-alt mr-1'
            : 'fas fa-expand-alt mr-1';
        focusModeButton.querySelector('span').textContent = enabled
            ? 'Sair da tela inteira'
            : 'Tela inteira';
        refreshCanvas();
    };

    const togglePanel = (side, button) => {
        const className = side === 'left' ? 'is-left-collapsed' : 'is-right-collapsed';
        const collapsed = appElement.classList.toggle(className);
        button.classList.toggle('is-active', !collapsed);
        button.setAttribute('aria-pressed', String(!collapsed));
        if (!collapsed && window.matchMedia('(max-width: 900px)').matches) {
            const otherSide = side === 'left' ? 'right' : 'left';
            const otherButton = side === 'left' ? togglePropertiesButton : toggleBlocksButton;
            appElement.classList.add(`is-${otherSide}-collapsed`);
            otherButton.classList.remove('is-active');
            otherButton.setAttribute('aria-pressed', 'false');
        }
        refreshCanvas();
    };

    const isWrapper = (component) => !component || component.is?.('wrapper');

    const isTextComponent = (component) => {
        const tagName = String(component?.get?.('tagName') || '').toLowerCase();
        const type = String(component?.get?.('type') || '').toLowerCase();

        return type === 'text' || ['a', 'button', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'blockquote', 'li', 'span'].includes(tagName);
    };

    const isImageComponent = (component) => {
        const tagName = String(component?.get?.('tagName') || '').toLowerCase();
        return component?.is?.('image') || component?.get?.('type') === 'image' || tagName === 'img';
    };

    const prepareFreeComponent = (component) => {
        if (isWrapper(component) || component.is?.('textnode')) {
            return;
        }

        component.set({
            draggable: true,
            resizable: true,
            copyable: true,
            removable: true,
        });
        component.setDragMode?.('absolute');

        if (isTextComponent(component)) {
            component.set('editable', true);
        }
    };

    const prepareComponentTree = (component) => {
        prepareFreeComponent(component);
        component?.components?.().forEach((child) => prepareComponentTree(child));
    };

    const selectedComponent = () => {
        const component = editor.getSelected();
        return isWrapper(component) ? null : component;
    };

    const activeDeviceId = () => editor.Devices.getSelected()?.getId?.() || 'totem';

    const mediaOptions = (deviceId) => {
        const profile = deviceProfiles[deviceId];

        if (!profile?.widthMedia) {
            return null;
        }

        return {
            atRuleType: 'media',
            atRuleParams: `(max-width: ${profile.widthMedia}px)`,
        };
    };

    const componentSelector = (component) => {
        const id = component.getId();
        component.setId(id);
        return `#${id}`;
    };

    const responsiveRule = (component, deviceId) => {
        const options = mediaOptions(deviceId);

        if (!options) {
            return null;
        }

        return editor.Css.getRule(componentSelector(component), options);
    };

    const baseComponentStyle = (component) => {
        const storedStyle = component.get?.('style');
        const modelStyle = storedStyle && typeof storedStyle === 'object'
            ? storedStyle
            : {};
        const plainRule = editor.Css.getRule(componentSelector(component))?.getStyle?.() || {};
        const totemRule = responsiveRule(component, 'totem')?.getStyle?.() || {};

        return { ...modelStyle, ...plainRule, ...totemRule };
    };

    const effectiveComponentStyle = (component, deviceId = activeDeviceId()) => {
        const baseStyle = baseComponentStyle(component);

        if (deviceId === 'totem') {
            return baseStyle;
        }

        const ruleStyle = responsiveRule(component, deviceId)?.getStyle?.() || {};
        return { ...baseStyle, ...ruleStyle };
    };

    const addDeviceStyle = (component, style, deviceId = activeDeviceId()) => {
        const options = mediaOptions(deviceId);

        editor.Css.setRule(componentSelector(component), style, {
            ...options,
            addStyles: true,
        });
    };

    const numericCssValue = (value) => {
        const number = Number.parseFloat(value);
        return Number.isFinite(number) ? number : null;
    };

    const responsiveGeometry = (style, profile, parentWidth, parentHeight) => {
        const result = {};
        const horizontal = ['left', 'right', 'width', 'min-width', 'max-width'];
        const vertical = ['top', 'bottom', 'height', 'min-height', 'max-height'];

        horizontal.forEach((property) => {
            const value = style[property];
            const numeric = numericCssValue(value);

            if (numeric !== null && String(value).trim().endsWith('px')) {
                const percentage = (numeric / parentWidth) * 100;
                result[property] = `${property.includes('width')
                    ? Math.max(0, percentage).toFixed(3)
                    : percentage.toFixed(3)}%`;
            }
        });

        vertical.forEach((property) => {
            const value = style[property];
            const numeric = numericCssValue(value);

            if (numeric !== null && String(value).trim().endsWith('px')) {
                const percentage = (numeric / parentHeight) * 100;
                result[property] = `${property.includes('height')
                    ? Math.max(0, percentage).toFixed(3)
                    : percentage.toFixed(3)}%`;
            }
        });

        const fontSize = numericCssValue(style['font-size']);
        if (fontSize !== null) {
            result['font-size'] = `${Math.max(14, Math.round(fontSize * profile.fontScale))}px`;
        }

        if (style.position === 'absolute' || style.left || style.top) {
            result.position = 'absolute';
        }

        return result;
    };

    const replaceResponsiveGeometry = (component, style, deviceId) => {
        const geometryProperties = new Set([
            'left', 'right', 'width', 'min-width', 'max-width',
            'top', 'bottom', 'height', 'min-height', 'max-height',
            'position', 'font-size',
        ]);
        const currentStyle = responsiveRule(component, deviceId)?.getStyle?.() || {};
        const preservedStyle = currentStyle;

        editor.Css.setRule(componentSelector(component), {
            ...style,
            ...preservedStyle,
        }, mediaOptions(deviceId));
    };

    const ensureResponsiveLayout = (deviceId, { force = false } = {}) => {
        const profile = deviceProfiles[deviceId];

        if (!profile || deviceId === 'totem') {
            return;
        }

        const root = editor.getWrapper().components().at(0);
        const marker = `data-cultura-responsive-${deviceId}`;
        const versionMarker = `${marker}-version`;

        const rootAttributes = root?.getAttributes?.() || {};

        const applyTree = (
            component,
            parentWidth = deviceProfiles.totem.width,
            parentHeight = deviceProfiles.totem.height,
        ) => {
            let childParentWidth = parentWidth;
            let childParentHeight = parentHeight;

            if (!isWrapper(component)) {
                const style = baseComponentStyle(component);
                const responsive = responsiveGeometry(style, profile, parentWidth, parentHeight);

                if (Object.keys(responsive).length) {
                    replaceResponsiveGeometry(component, responsive, deviceId);
                }

                const ownWidth = numericCssValue(style.width);
                const ownHeight = numericCssValue(style.height);
                childParentWidth = ownWidth && String(style.width).trim().endsWith('px')
                    ? ownWidth
                    : parentWidth;
                childParentHeight = ownHeight && String(style.height).trim().endsWith('px')
                    ? ownHeight
                    : parentHeight;
            }

            component.components?.().forEach((child) => applyTree(
                child,
                childParentWidth,
                childParentHeight,
            ));
        };

        applyTree(editor.getWrapper());

        if (root) {
            root.addAttributes({
                [marker]: '1',
                [versionMarker]: '2',
            });

            const updatedAttributes = root.getAttributes();

            if (
                updatedAttributes['data-cultura-responsive-tablet-version'] === '2'
                && updatedAttributes['data-cultura-responsive-mobile-version'] === '2'
            ) {
                root.addAttributes({ 'data-cultura-responsive-version': '2' });
            }
        }

        editor.refresh();
    };

    const isButtonComponent = (component) => String(component?.get?.('tagName') || '').toLowerCase() === 'button';

    const isLinkComponent = (component) => String(component?.get?.('tagName') || '').toLowerCase() === 'a';

    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const elementText = (component) => {
        const savedLabel = component?.get?.('cultura-label');

        if (typeof savedLabel === 'string') {
            return savedLabel;
        }

        return component?.getView?.()?.el?.textContent?.trim()
            || String(component?.getInnerHTML?.() || '').replace(/<[^>]*>/g, '').trim();
    };

    const rebuildButton = (component) => {
        if (!isButtonComponent(component)) {
            return;
        }

        const label = String(component.get('cultura-label') || elementText(component) || 'Botão');
        const icon = String(component.get('cultura-icon') || '');
        const image = String(component.get('cultura-image') || '');
        const position = component.get('cultura-icon-position') === 'end' ? 'end' : 'start';
        const size = Math.max(8, Math.min(200, Number(component.get('cultura-icon-size')) || 22));
        const allowedIcons = [...buttonIconField.options].map((option) => option.value);
        const safeIcon = allowedIcons.includes(icon) ? icon : '';
        let visual = '';

        if (image.startsWith('/')) {
            visual = `<img src="${escapeHtml(image)}" alt="" style="width:${size}px;height:${size}px;object-fit:contain">`;
        } else if (safeIcon) {
            visual = `<i class="${safeIcon}" aria-hidden="true" style="font-size:${size}px"></i>`;
        }

        const text = `<span>${escapeHtml(label)}</span>`;
        component.components(position === 'end' ? `${text}${visual}` : `${visual}${text}`);
        component.addStyle({
            display: 'inline-flex',
            'align-items': 'center',
            'justify-content': 'center',
            gap: '10px',
        });
    };

    const imageSource = (component) => String(
        component?.getAttributes?.().src || component?.get?.('src') || '',
    );

    const refreshInspector = () => {
        const component = selectedComponent();

        inspectorEmptyState.classList.toggle('d-none', Boolean(component));
        inspectorFields.classList.toggle('d-none', !component);

        if (!component) {
            return;
        }

        const tagName = String(component.get('tagName') || component.get('type') || 'elemento').toLowerCase();
        const button = isButtonComponent(component);
        const image = isImageComponent(component);
        const link = isLinkComponent(component);
        const text = isTextComponent(component);
        const navigation = component.getAttributes()['data-cultura-navigation'] === '1';
        const supportsNavigation = ['a', 'button', 'article', 'section', 'div', 'img', 'span', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(tagName)
            && component.get('type') !== 'wrapper';

        inspectorElementName.textContent = component.getName?.() || 'Elemento';
        inspectorElementTag.textContent = tagName;
        inspectorTextGroup.classList.toggle('d-none', !text);
        inspectorButtonGroup.classList.toggle('d-none', !button);
        inspectorNavigationGroup.classList.toggle('d-none', !supportsNavigation);
        inspectorImageGroup.classList.toggle('d-none', !image);
        inspectorLinkGroup.classList.toggle('d-none', !link);

        const componentStyle = effectiveComponentStyle(component);
        const componentElement = component.getView?.()?.el;
        const activeProfile = deviceProfiles[activeDeviceId()] || deviceProfiles.totem;
        const fieldPixels = (value, total) => {
            const numeric = Number.parseFloat(value);
            if (!Number.isFinite(numeric)) return 0;
            return String(value).trim().endsWith('%') ? Math.round((numeric / 100) * total) : Math.round(numeric);
        };

        if (document.activeElement !== elementPositionX) {
            elementPositionX.value = fieldPixels(componentStyle.left, activeProfile.width);
        }
        if (document.activeElement !== elementPositionY) {
            elementPositionY.value = fieldPixels(componentStyle.top, activeProfile.height);
        }
        if (document.activeElement !== elementWidthField) {
            elementWidthField.value = fieldPixels(componentStyle.width, activeProfile.width)
                || Math.round(componentElement?.getBoundingClientRect?.().width || 0);
        }
        if (document.activeElement !== elementHeightField) {
            elementHeightField.value = fieldPixels(componentStyle.height, activeProfile.height)
                || Math.round(componentElement?.getBoundingClientRect?.().height || 0);
        }

        if (text && document.activeElement !== elementTextField) {
            elementTextField.value = elementText(component);
        }

        if (button) {
            buttonIconField.value = component.get('cultura-icon') || '';
            buttonIconPosition.value = component.get('cultura-icon-position') || 'start';
            buttonIconSize.value = component.get('cultura-icon-size') || 22;
            removeButtonImageButton.classList.toggle('d-none', !component.get('cultura-image'));
        }

        const navigationAttributes = component.getAttributes();
        const action = navigation ? (navigationAttributes['data-section-id'] !== undefined ? 'section' : 'page') : '';
        navigationActionField.value = action;
        buttonDestinationField.value = navigationAttributes['data-page-id'] || '';
        sectionDestinationField.value = navigationAttributes['data-section-id'] || '';
        for (const [field, kind] of [[buttonDestinationField, 'page'], [sectionDestinationField, 'section']]) {
            field.hidden = action !== kind;
            field.disabled = action !== kind;
            document.querySelector(`label[for="${field.id}"]`).hidden = action !== kind;
        }

        if (image) {
            const source = imageSource(component);
            imageAltField.value = component.getAttributes().alt || '';
            imagePreview.innerHTML = source
                ? `<img src="${escapeHtml(source)}" alt="Pré-visualização">`
                : '<span>Nenhuma imagem escolhida</span>';
        }

        if (link) {
            const attributes = component.getAttributes();
            linkUrlField.value = attributes.href || '';
            linkNewWindowField.checked = attributes.target === '_blank';
        }
    };

    const updateElementButtons = () => {
        const component = selectedComponent();
        const hasSelection = Boolean(component);

        editElementButton.disabled = !hasSelection || (!isTextComponent(component) && !isImageComponent(component));
        duplicateElementButton.disabled = !hasSelection;
        sendBackwardButton.disabled = !hasSelection;
        bringForwardButton.disabled = !hasSelection;
        deleteElementButton.disabled = !hasSelection;
    };

    const setFreePosition = (enabled) => {
        freePositionEnabled = enabled;
        editor.setDragMode(enabled ? 'absolute' : undefined);

        if (enabled) {
            prepareComponentTree(editor.getWrapper());
        }

        freePositionButton.classList.toggle('is-active', enabled);
        freePositionButton.setAttribute('aria-pressed', String(enabled));
        freePositionButton.title = enabled
            ? 'Movimentação livre ativada'
            : 'Movimentação livre desativada';
        setStatus(enabled ? 'Movimentação livre ativada' : 'Organização automática ativada', 'success');
        refreshCanvas();
    };

    const editSelectedElement = () => {
        const component = selectedComponent();

        if (!component) {
            return;
        }

        if (isImageComponent(component)) {
            editor.runCommand('open-assets', { target: component });
            return;
        }

        if (isTextComponent(component)) {
            component.set('editable', true);
            component.trigger('active');
            setStatus('Editando texto diretamente na página', 'success');
        }
    };

    const duplicateSelectedElement = () => {
        const component = selectedComponent();
        const parent = component?.parent?.();

        if (!component || !parent) {
            return;
        }

        const clone = component.clone();
        const index = parent.components().indexOf(component);
        parent.append(clone, { at: index + 1 });

        const style = clone.getStyle();
        const left = Number.parseFloat(style.left) || 0;
        const top = Number.parseFloat(style.top) || 0;
        clone.addStyle({
            position: 'absolute',
            left: `${left + 20}px`,
            top: `${top + 20}px`,
        });
        editor.select(clone);
    };

    const changeLayer = (increment) => {
        const component = selectedComponent();

        if (!component) {
            return;
        }

        const current = Number.parseInt(effectiveComponentStyle(component)['z-index'], 10) || 0;
        addDeviceStyle(component, {
            'z-index': String(Math.max(-100, Math.min(1000, current + increment))),
        });
        editor.refresh();
    };

    const deleteSelectedElement = () => {
        const component = selectedComponent();

        if (component) {
            component.remove();
            updateElementButtons();
        }
    };

    const handleCanvasKeyboard = (event) => {
        const activeElement = event.target;

        if (activeElement?.matches?.('input, textarea, select, [contenteditable="true"]')) {
            return;
        }

        const component = selectedComponent();

        if (!component) {
            return;
        }

        if (event.key === 'Delete') {
            event.preventDefault();
            deleteSelectedElement();
            return;
        }

        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'd') {
            event.preventDefault();
            duplicateSelectedElement();
            return;
        }

        const directions = {
            ArrowLeft: [-1, 0],
            ArrowRight: [1, 0],
            ArrowUp: [0, -1],
            ArrowDown: [0, 1],
        };
        const direction = directions[event.key];

        if (!direction) {
            return;
        }

        event.preventDefault();
        const step = event.shiftKey ? 10 : 1;
        const style = effectiveComponentStyle(component);
        const left = Number.parseFloat(style.left) || 0;
        const top = Number.parseFloat(style.top) || 0;

        addDeviceStyle(component, {
            position: 'absolute',
            left: `${left + (direction[0] * step)}px`,
            top: `${top + (direction[1] * step)}px`,
        });
        editor.refresh();
    };

    const extractError = async (response) => {
        const payload = await response.json().catch(() => ({}));
        const errors = payload.errors ? Object.values(payload.errors).flat() : [];
        return errors[0] || payload.message || 'Não foi possível concluir a operação.';
    };

    const request = async (url, method, payload) => {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            if (response.status === 409) {
                conflicted = true;
                window.clearTimeout(autosaveTimer);
                document.getElementById('downloadConflictCopy').hidden = false;
            }
            throw new Error(await extractError(response));
        }

        return response.json();
    };

    const saveDraft = async ({ quiet = false } = {}) => {
        if (conflicted || publishing) return;
        if (savingPromise) {
            return savingPromise;
        }

        if (!quiet) {
            setStatus('Salvando rascunho…', 'saving');
        }

        saveButton.disabled = true;
        const submittedVersion = changeVersion;
        savingPromise = request(config.saveUrl, 'PUT', {
            project_data: editor.getProjectData(),
            base_version: config.draftVersion,
            manual: !quiet,
            html: quiet ? null : editor.getHtml(),
            css: quiet ? null : editor.getCss(),
        });

        try {
            const saved = await savingPromise;
            config.draftVersion = saved.draft_version;
            dirty = changeVersion !== submittedVersion;

            if (dirty) {
                setStatus('Há alterações novas aguardando salvamento');
                window.clearTimeout(autosaveTimer);
                autosaveTimer = window.setTimeout(() => {
                    saveDraft({ quiet: true }).catch(() => {});
                }, 4000);
            } else {
                setStatus(quiet ? 'Rascunho salvo automaticamente' : 'Rascunho salvo', 'success');

                if (!quiet) {
                    window.AdminNotice?.success('As alterações foram salvas no rascunho.', 'Rascunho salvo');
                }
            }
        } catch (error) {
            setStatus(error.message, 'error');

            if (!quiet) {
                window.AdminNotice?.error(error.message, 'Erro ao salvar');
            }

            throw error;
        } finally {
            savingPromise = null;
            saveButton.disabled = conflicted;
        }
    };

    const publish = async () => {
        const canPublish = Boolean(config.canPublish);
        const confirmed = await window.AdminNotice?.confirm({
            type: 'success',
            icon: 'fas fa-cloud-upload-alt',
            title: canPublish ? 'Publicar página?' : 'Enviar para revisão?',
            message: canPublish
                ? 'Publicar esta versão para os totens agora?'
                : 'Enviar esta versão imutável para aprovação administrativa?',
            confirmText: canPublish ? 'Publicar agora' : 'Enviar para revisão',
        });

        if (!confirmed) return;

        window.clearTimeout(autosaveTimer);
        if (savingPromise) {
            try {
                const saved = await savingPromise;
                config.draftVersion = saved.draft_version;
            } catch { return; }
        }
        if (conflicted) return;
        publishing = true;
        publishButton.disabled = true;
        saveButton.disabled = true;
        setStatus(canPublish ? 'Validando e publicando…' : 'Preparando revisão…', 'saving');

        try {
            ensureResponsiveLayout('tablet');
            ensureResponsiveLayout('mobile');
            const submittedVersion = changeVersion;
            const result = await request(canPublish ? config.publishUrl : config.submitUrl, 'POST', {
                project_data: editor.getProjectData(),
                html: editor.getHtml(),
                css: editor.getCss(),
                base_version: config.draftVersion,
            });
            config.draftVersion = result.draft_version;
            dirty = changeVersion !== submittedVersion;
            document.getElementById('firstDraftNotice')?.classList.add('d-none');
            setStatus(dirty
                ? 'Concluído, mas há alterações novas no editor'
                : (canPublish ? 'Página publicada com segurança' : 'Conteúdo enviado para revisão'), dirty ? '' : 'success');
            window.AdminNotice?.success(
                dirty
                    ? 'A versão foi enviada, mas já existem alterações novas aguardando salvamento.'
                    : (canPublish ? 'A página está disponível nos totens.' : 'Um administrador foi notificado para revisar o conteúdo.'),
                canPublish ? 'Página publicada' : 'Revisão solicitada',
            );
        } catch (error) {
            setStatus(error.message, 'error');
            window.AdminNotice?.error(error.message, canPublish ? 'Erro ao publicar' : 'Erro ao enviar revisão');
        } finally {
            publishing = false;
            publishButton.disabled = conflicted;
            saveButton.disabled = conflicted;
        }
    };

    const refreshHistoryButtons = () => {
        undoButton.disabled = !editor.UndoManager.hasUndo();
        redoButton.disabled = !editor.UndoManager.hasRedo();
    };

    editor.on('load', () => {
        ready = true;
        appElement.dataset.editorReady = 'true';
        setFreePosition(true);
        editor.Devices.select('totem');
        editor.Canvas.getDocument()?.addEventListener('keydown', handleCanvasKeyboard);
        refreshHistoryButtons();
        updateElementButtons();
        if (window.matchMedia('(max-width: 900px)').matches) {
            togglePanel('left', toggleBlocksButton);
            togglePanel('right', togglePropertiesButton);
        }
    });

    editor.on('component:create', prepareFreeComponent);
    editor.on('component:selected component:deselected', () => {
        updateElementButtons();
        refreshInspector();
    });
    editor.on('update', () => {
        if (!ready) {
            return;
        }

        dirty = true;
        changeVersion += 1;
        setStatus('Alterações ainda não salvas');
        refreshHistoryButtons();
        window.clearTimeout(autosaveTimer);
        if (!publishing && !conflicted) {
            autosaveTimer = window.setTimeout(() => {
                saveDraft({ quiet: true }).catch(() => {});
            }, 4000);
        }
    });

    editor.on('asset:upload:error', () => {
        const message = 'A imagem não pôde ser enviada. Use JPG, PNG, WebP ou GIF de até 10 MB.';
        setStatus(message, 'error');
        window.AdminNotice?.error(message, 'Erro no envio da imagem');
    });

    saveButton.addEventListener('click', () => saveDraft().catch(() => {}));
    document.getElementById('downloadConflictCopy')?.addEventListener('click', () => {
        const blob = new Blob([JSON.stringify(editor.getProjectData(), null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `pagina-${config.pageId}-copia-local.json`;
        link.click();
        URL.revokeObjectURL(url);
    });
    publishButton.addEventListener('click', publish);
    focusModeButton.addEventListener('click', () => {
        setFocusMode(!appElement.classList.contains('is-focus-mode'));
    });
    toggleBlocksButton.addEventListener('click', () => togglePanel('left', toggleBlocksButton));
    togglePropertiesButton.addEventListener('click', () => togglePanel('right', togglePropertiesButton));
    freePositionButton.addEventListener('click', () => {
        setFreePosition(editor.getDragMode() !== 'absolute');
    });
    editElementButton.addEventListener('click', editSelectedElement);
    duplicateElementButton.addEventListener('click', duplicateSelectedElement);
    sendBackwardButton.addEventListener('click', () => changeLayer(-1));
    bringForwardButton.addEventListener('click', () => changeLayer(1));
    deleteElementButton.addEventListener('click', deleteSelectedElement);
    deleteCurrentPageForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const pageTitle = deleteCurrentPageForm.dataset.pageTitle || 'esta página';
        const confirmed = await window.AdminNotice?.confirm({
            type: 'danger',
            icon: 'fas fa-trash-alt',
            title: 'Excluir página?',
            message: `Excluir “${pageTitle}” permanentemente?\n\nTodo o conteúdo salvo será removido. Botões de outras páginas que apontem para ela deixarão de funcionar.`,
            confirmText: 'Excluir página',
        });

        if (!confirmed) return;

        dirty = false;

        if (deleteCurrentPageButton) {
            deleteCurrentPageButton.disabled = true;
        }

        deleteCurrentPageForm.submit();
    });

    const updateSelectedGeometry = () => {
        const component = selectedComponent();

        if (!component) {
            return;
        }

        component.setDragMode?.('absolute');
        addDeviceStyle(component, {
            position: 'absolute',
            left: `${Number(elementPositionX.value) || 0}px`,
            top: `${Number(elementPositionY.value) || 0}px`,
            width: `${Math.max(1, Number(elementWidthField.value) || 1)}px`,
            height: `${Math.max(1, Number(elementHeightField.value) || 1)}px`,
        });
        editor.refresh();
    };

    [elementPositionX, elementPositionY, elementWidthField, elementHeightField]
        .forEach((field) => field.addEventListener('input', updateSelectedGeometry));

    elementTextField.addEventListener('input', () => {
        const component = selectedComponent();

        if (!component || !isTextComponent(component)) {
            return;
        }

        if (isButtonComponent(component)) {
            component.set('cultura-label', elementTextField.value);
            rebuildButton(component);
        } else {
            component.components(escapeHtml(elementTextField.value));
        }
    });

    document.querySelectorAll('[data-quick-style]').forEach((button) => {
        button.addEventListener('click', () => {
            const component = selectedComponent();

            if (!component) {
                return;
            }

            const property = button.dataset.quickStyle;
            const value = button.dataset.styleValue;
            const current = effectiveComponentStyle(component)[property];
            addDeviceStyle(component, { [property]: current === value ? '' : value });
            editor.refresh();
        });
    });

    const updateButtonVisual = () => {
        const component = selectedComponent();

        if (!component || !isButtonComponent(component)) {
            return;
        }

        component.set({
            'cultura-icon': buttonIconField.value,
            'cultura-icon-position': buttonIconPosition.value,
            'cultura-icon-size': Math.max(8, Math.min(200, Number(buttonIconSize.value) || 22)),
        });

        if (buttonIconField.value) {
            component.set('cultura-image', '');
        }

        rebuildButton(component);
        refreshInspector();
    };

    buttonIconField.addEventListener('change', updateButtonVisual);
    buttonIconPosition.addEventListener('change', updateButtonVisual);
    buttonIconSize.addEventListener('input', updateButtonVisual);

    const openAssetPicker = (onSelect) => {
        editor.runCommand('open-assets', {
            target: null,
            types: ['image'],
            accept: 'image/jpeg,image/png,image/webp,image/gif',
            select: true,
            onSelect: (asset) => {
                onSelect(String(asset.getSrc() || ''));
                editor.AssetManager.setTarget(null);
                editor.Modal.close();
            },
        });
    };

    buttonCustomImageButton.addEventListener('click', () => {
        const component = selectedComponent();

        if (!component || !isButtonComponent(component)) {
            return;
        }

        openAssetPicker((source) => {
            component.set({
                'cultura-image': source,
                'cultura-icon': '',
            });
            rebuildButton(component);
            refreshInspector();
        });
    });

    removeButtonImageButton.addEventListener('click', () => {
        const component = selectedComponent();

        if (component && isButtonComponent(component)) {
            component.set('cultura-image', '');
            rebuildButton(component);
            refreshInspector();
        }
    });

    buttonDestinationField.addEventListener('change', () => {
        const component = selectedComponent();

        if (component) {
            component.addAttributes({ 'data-page-id': buttonDestinationField.value });
        }
    });
    navigationActionField.addEventListener('change', () => {
        const component = selectedComponent();
        if (!component) return;
        const action = navigationActionField.value;
        component.removeAttributes(['data-cultura-navigation', 'data-page-id', 'data-section-id']);
        if (action) component.addAttributes({
            'data-cultura-navigation': '1',
            [action === 'section' ? 'data-section-id' : 'data-page-id']: '',
        });
        refreshInspector();
    });
    sectionDestinationField.addEventListener('change', () => {
        selectedComponent()?.addAttributes({ 'data-section-id': sectionDestinationField.value });
    });

    changeImageButton.addEventListener('click', () => {
        const component = selectedComponent();

        if (!component || !isImageComponent(component)) {
            return;
        }

        openAssetPicker((source) => {
            component.set('src', source);
            component.addAttributes({ src: source });
            refreshInspector();
        });
    });

    imageAltField.addEventListener('input', () => {
        const component = selectedComponent();

        if (component && isImageComponent(component)) {
            component.addAttributes({ alt: imageAltField.value });
        }
    });

    linkUrlField.addEventListener('input', () => {
        const component = selectedComponent();

        if (component && isLinkComponent(component)) {
            component.addAttributes({ href: linkUrlField.value });
        }
    });

    linkNewWindowField.addEventListener('change', () => {
        const component = selectedComponent();

        if (component && isLinkComponent(component)) {
            component.addAttributes({ target: linkNewWindowField.checked ? '_blank' : '_self' });
        }
    });
    undoButton.addEventListener('click', () => {
        editor.UndoManager.undo();
        refreshHistoryButtons();
    });
    redoButton.addEventListener('click', () => {
        editor.UndoManager.redo();
        refreshHistoryButtons();
    });
    deviceSelector.addEventListener('change', (event) => {
        const deviceId = event.target.value;
        ensureResponsiveLayout(deviceId);
        editor.Devices.select(deviceId);
        setStatus(
            deviceId === 'totem'
                ? 'Editando o layout-base do totem'
                : `Editando ajustes exclusivos para ${deviceProfiles[deviceId].name}`,
            'success',
        );
        window.setTimeout(() => {
            editor.refresh();
            refreshInspector();
        }, 80);
    });

    responsiveFitButton.addEventListener('click', () => {
        const deviceId = activeDeviceId();

        if (deviceId === 'totem') {
            setStatus('O tablet horizontal é o layout-base. Escolha Tablet vertical ou Celular para recalcular.', 'success');
            return;
        }

        ensureResponsiveLayout(deviceId, { force: true });
        setStatus(`Adaptação para ${deviceProfiles[deviceId].name} recalculada`, 'success');
        window.setTimeout(refreshInspector, 80);
    });

    document.querySelectorAll('[data-editor-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-editor-tab]').forEach((item) => item.classList.remove('is-active'));
            document.querySelectorAll('[data-editor-panel]').forEach((item) => item.classList.remove('is-active'));
            button.classList.add('is-active');
            document.querySelector(`[data-editor-panel="${button.dataset.editorTab}"]`)?.classList.add('is-active');
        });
    });

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
            event.preventDefault();
            saveDraft().catch(() => {});
        }

        if (event.key === 'Escape' && appElement.classList.contains('is-focus-mode')) {
            setFocusMode(false);
        }
    });

    window.addEventListener('beforeunload', (event) => {
        if (!dirty) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    });

    setFocusMode(true);
}
