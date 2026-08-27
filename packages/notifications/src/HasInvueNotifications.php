<?php

namespace Invue\Notifications;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Invue\Notifications\Models\DatabaseNotification;

/**
 * Mixed into whatever model receives persisted notifications (typically
 * your User model) — mirrors Laravel's own `Notifiable` trait shape, but
 * scoped to Invue\Notifications\Models\DatabaseNotification, not
 * Illuminate\Notifications\DatabaseNotification. Not required to *send* a
 * database notification (`Notification::sendToDatabase()` works against
 * any model), only to conveniently *read* "my notifications" back.
 */
trait HasInvueNotifications
{
    public function invueNotifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->latest();
    }

    public function unreadInvueNotifications(): MorphMany
    {
        return $this->invueNotifications()->unread();
    }
}
