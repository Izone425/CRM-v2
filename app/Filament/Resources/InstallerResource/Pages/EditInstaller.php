<?php

namespace App\Filament\Resources\InstallerResource\Pages;

use App\Filament\Resources\InstallerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class EditInstaller extends EditRecord
{
    protected static string $resource = InstallerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // protected function afterSave(): void
    // {
    //     // Get the edited installer
    //     $installer = $this->record;

    //     // Send notification to all users
    //     $users = User::all();

    //     foreach ($users as $user) {
    //         Notification::make()
    //             ->title('🇲🇾 Happy Malaysia Day!')
    //             ->body('Wishing you a joyful long holiday 🎉')
    //             ->actions([
    //                 Action::make('thumbsUp')
    //                     ->label('👍')  // Thumbs up emoji
    //                     ->button()
    //                     ->color('success')
    //                     ->close()
    //                     ->action(function (Notification $notification) use ($user) {
    //                         // Mark notification as read
    //                         $databaseNotification = $user->notifications()
    //                             ->where('id', $notification->getDatabaseNotificationId())
    //                             ->first();

    //                         if ($databaseNotification) {
    //                             $databaseNotification->markAsRead();
    //                         }

    //                         // Show feedback
    //                         Notification::make()
    //                             ->title('Thanks for acknowledging!')
    //                             ->success()
    //                             ->send();
    //                     })
    //             ])
    //             ->color('primary')
    //             ->icon('heroicon-o-fire')
    //             ->sendToDatabase($user);
    //     }
    // }
}
