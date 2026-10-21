<?php

namespace App\Livewire\Notifications;

use Illuminate\View\View;
use Livewire\Component;

class UnreadIndicator extends Component
{
    public function render(): View
    {
        return view('livewire.notifications.unread-indicator', [
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
