@inject('preloaderHelper', 'JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper')

<div class="{{ $preloaderHelper->makePreloaderClasses() }} totemsync-preloader"
     style="{{ $preloaderHelper->makePreloaderStyle() }}"
     role="status" aria-label="Carregando TotemSync">
    @hasSection('preloader')
        @yield('preloader')
    @else
        @include('partials.brand-mark', ['brandMarkClass' => 'totemsync-preloader-mark'])
        <strong class="totemsync-preloader-name">TotemSync</strong>
        <span class="totemsync-preloader-label">Carregando...</span>
    @endif
</div>
