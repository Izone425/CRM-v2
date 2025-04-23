<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make("avatar_path")
                    ->label('')         // Removes the label text
                    ->placeholder('')
                    ->disk('public')
                    ->directory('uploads/photos')
                    ->image()
                    ->avatar()
                    ->imageEditor()
                    ->extraAttributes(['class' => 'mx-auto']),
                // $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                FileUpload::make("signature_path")
                    ->label('Signature')
                    ->placeholder('')
                    ->disk('public')
                    ->directory('uploads/photos')
                    ->image()
                    ->extraAttributes([
                        'class' => 'mx-auto',
                        'style' => 'border-radius: 0; width: 100%; height: auto;', // Removes circular shape and makes it rectangular
                    ]),
            ]);
    }

    public function save(): void
    {
        // Get the current state from the form
        $data = $this->form->getState();

        /** @var \App\Models\User $currentUser **/
        $currentUser = Auth::user();
        // Check if the avatar_path is being changed
        if ($currentUser->avatar_path !== $data['avatar_path']) {
            $oldFile = $currentUser->avatar_path;
            // Delete the old file if it exists on the public disk
            if ($oldFile && Storage::disk('public')->exists($oldFile)) {
                Storage::disk('public')->delete($oldFile);
            }
        }

        // Update the record with the new data
        if ($currentUser->update($data)) {
            Notification::make()
                ->title('Saved successfully')
                ->success()
                ->send();

                redirect()->back();
        }
    }

}
