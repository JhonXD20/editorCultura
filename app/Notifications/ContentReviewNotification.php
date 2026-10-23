<?php

namespace App\Notifications;

use App\Models\ReviewRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ContentReviewNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ReviewRequest $review, private readonly string $event) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'review_id' => $this->review->id,
            'pagina_id' => $this->review->pagina_id,
            'pagina_titulo' => $this->review->pagina->pagina_titulo,
            'comment' => $this->review->comentario_revisao,
            'url' => route('reviews.index'),
        ];
    }
}
