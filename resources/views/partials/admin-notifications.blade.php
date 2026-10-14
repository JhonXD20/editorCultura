@php
    $adminNotifications = collect([
        'success' => session('success'),
        'danger' => session('error'),
        'warning' => session('warning'),
        'info' => session('info') ?? session('status'),
    ])->filter(fn ($message) => filled($message))
        ->map(fn ($message, $type) => [
            'type' => $type,
            'message' => (string) $message,
        ])->values();

@endphp

<div
    class="modal fade admin-notice-modal"
    id="adminNoticeModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="adminNoticeTitle"
    aria-describedby="adminNoticeMessage"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" id="adminNoticeHeader">
                <h5 class="modal-title" id="adminNoticeTitle">
                    <i id="adminNoticeIcon" class="fas fa-info-circle mr-2" aria-hidden="true"></i>
                    <span id="adminNoticeTitleText">Aviso</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="admin-notice-message mb-0" id="adminNoticeMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" id="adminNoticeCancel" data-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="adminNoticeConfirm">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="adminNotificationsConfig">
    {!! json_encode($adminNotifications, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
