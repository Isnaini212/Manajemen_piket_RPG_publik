<?php

namespace App\Livewire;

use App\Models\Notification;
use Livewire\Component;

class NotificationBell extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $notifications = [];

    public int $unreadCount = 0;

    public bool $isOpen = false;

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        if (! auth()->check()) {
            $this->notifications = [];
            $this->unreadCount = 0;

            return;
        }

        $this->notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Notification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'message' => $n->message,
                'is_read' => (bool) $n->is_read,
                'time' => $n->created_at?->locale('id')->diffForHumans(),
            ])
            ->all();

        $this->unreadCount = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();
    }

    public function toggle(): void
    {
        $this->isOpen = ! $this->isOpen;
    }

    public function markAsRead(int $id): void
    {
        Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->update(['is_read' => true]);

        $this->loadNotifications();
    }

    public function markAllRead(): void
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->loadNotifications();
    }

    public function getIcon(string $type): string
    {
        return match ($type) {
            'piket_approved' => '✅',
            'piket_rejected' => '❌',
            'piket_submission' => '📸',
            'resubmit_required' => '🔄',
            'swap_request' => '🔀',
            'swap_accepted' => '✅',
            'swap_rejected' => '❌',
            'swap_info' => 'ℹ️',
            'status_changed' => '⚠️',
            'status_recovered' => '🌟',
            'badge_earned' => '🏆',
            'new_semester' => '📅',
            'replacement_offered' => '🔔',
            'redemption_failed' => '💀',
            default => '📩',
        };
    }

    public function getUrl(string $type): ?string
    {
        return match ($type) {
            'piket_submission' => route('admin.submissions.index'),
            'swap_request', 'swap_accepted', 'swap_rejected', 'swap_info' => route('student.swap'),
            default => null,
        };
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
