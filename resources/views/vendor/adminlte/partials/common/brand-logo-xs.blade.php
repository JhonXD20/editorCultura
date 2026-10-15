@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

@php
    $dashboardUrl = View::getSection('dashboard_url') ?? config('adminlte.dashboard_url', 'home');
    $dashboardUrl = $dashboardUrl
        ? (config('adminlte.use_route_url', false) ? route($dashboardUrl) : url($dashboardUrl))
        : '';
@endphp

<a href="{{ $dashboardUrl }}"
   class="{{ $layoutHelper->isLayoutTopnavEnabled() ? 'navbar-brand' : 'brand-link' }} {{ config('adminlte.classes_brand') }}"
   aria-label="TotemSync - painel administrativo">
    @include('partials.brand-mark', ['brandMarkClass' => 'brand-image editor-brand-image'])

    <span class="brand-text font-weight-light {{ config('adminlte.classes_brand_text') }}">
        {!! config('adminlte.logo', '<b>Totem</b>Sync') !!}
    </span>
</a>
