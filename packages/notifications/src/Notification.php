<?php

namespace Invue\Notifications;

use Illuminate\Support\Str;

/**
 * Fluent builder for one ephemeral toast, translating Filament's
 * Notification::make()->title()->body()->icon()->color()->send() — the
 * PHP side stays a plain data/trigger builder (title/body/icon/color/
 * duration), never markup: which Vue component renders a toast, and how,
 * is a 100% client-side registry concern (see notifications.Toast).
 *
 * ->send() flashes onto the session for exactly the next request/redirect
 * — the same one-shot model Filament's own ->send() uses. There is
 * deliberately no ->sendToDatabase()/bell-dropdown persisted mode in v1
 * (see the package README's v1-scope note) — settled explicitly with the
 * project owner in favor of shipping the toast first.
 */
class Notification
{
    protected const SESSION_KEY = 'invue_notifications';

    protected string $title = '';

    protected ?string $body = null;

    protected ?string $icon = null;

    protected string $color = 'gray';

    protected ?string $iconColor = null;

    /** Milliseconds before auto-dismiss; null = persistent (manual close only). */
    protected ?int $duration = 5000;

    public static function make(): static
    {
        return new static;
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function body(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    /**
     * An icon *name* resolved client-side through invue/core's <Icon>
     * registry (e.g. 'check' — Lucide's naming, but not tied to Lucide
     * specifically) — the app must have registered it via
     * invue.registerIcons({...}) or it silently won't render.
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * One of the shared Invue color names (gray/red/green/blue/yellow/
     * amber/sky/rose/purple/pink) — the same palette invue/tables'
     * TextColumn/IconColumn already use for `color`/`badge`.
     */
    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function iconColor(string $color): static
    {
        $this->iconColor = $color;

        return $this;
    }

    public function duration(int $milliseconds): static
    {
        $this->duration = $milliseconds;

        return $this;
    }

    public function persistent(): static
    {
        $this->duration = null;

        return $this;
    }

    public function success(): static
    {
        return $this->color('green')->icon($this->icon ?? 'circle-check');
    }

    public function warning(): static
    {
        return $this->color('amber')->icon($this->icon ?? 'triangle-alert');
    }

    public function danger(): static
    {
        return $this->color('red')->icon($this->icon ?? 'circle-x');
    }

    public function info(): static
    {
        return $this->color('sky')->icon($this->icon ?? 'info');
    }

    /**
     * @return array{id: string, title: string, body: ?string, icon: ?string, color: string, iconColor: ?string, duration: ?int}
     */
    public function toArray(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $this->icon,
            'color' => $this->color,
            'iconColor' => $this->iconColor,
            'duration' => $this->duration,
        ];
    }

    /**
     * Queues this notification onto the session, to be picked up by the
     * next request's Inertia response. Re-flashes the whole accumulated
     * list each call, so multiple ->send() calls within one request (or
     * request chain) stack instead of clobbering each other.
     */
    public function send(): void
    {
        $queued = session()->get(static::SESSION_KEY, []);
        $queued[] = $this->toArray();

        session()->flash(static::SESSION_KEY, $queued);
    }

    /**
     * Call this from your own HandleInertiaRequests::share(), the same way
     * the app already hand-shares 'flash' => ['status' => fn () => ...] —
     * this package doesn't auto-register a global middleware, since
     * ->send() can be called from any controller in the app, not just
     * routes the package itself owns.
     *
     * @return list<array{id: string, title: string, body: ?string, icon: ?string, color: string, iconColor: ?string, duration: ?int}>
     */
    public static function flashed(): array
    {
        return session(static::SESSION_KEY, []);
    }
}
