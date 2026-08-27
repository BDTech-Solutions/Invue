<?php

namespace Invue\Notifications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DatabaseNotification extends Model
{
    protected $table = 'invue_notifications';

    protected $fillable = ['notifiable_type', 'notifiable_id', 'title', 'body', 'icon', 'color'];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    /**
     * @return array{id: int, title: string, body: ?string, icon: ?string, color: string, read: bool, createdAt: string}
     */
    public function toNotificationArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $this->icon,
            'color' => $this->color,
            'read' => $this->read_at !== null,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
