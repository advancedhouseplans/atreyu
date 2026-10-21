<?php

namespace App\Livewire\Notifications;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $view = 'unread';

    public function updatingView(): void
    {
        $this->resetPage();
    }

    public function openNotification(string $notificationId): void
    {
        /** @var DatabaseNotification $notification */
        $notification = auth()->user()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        $url = data_get($notification->data, 'url', route('tasks.index'));
        $this->redirect($url, navigate: true);
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function render(): View
    {
        $notifications = auth()->user()->notifications()
            ->when($this->view !== 'all', fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate(30);

        return view('livewire.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
