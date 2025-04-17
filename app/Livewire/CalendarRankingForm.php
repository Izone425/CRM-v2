<?php

namespace App\Livewire;

use App\Models\DemoRanking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CalendarRankingForm extends Component
{

    public $users = [];

    public function mount()
    {
        $this->loadUsers();
    }

    public function loadUsers()
    {
        // Check if rankings table has any entries
        $hasRankings = DemoRanking::count() > 0;

        if ($hasRankings) {
            // Get users with role_id 2 and join with rankings
            $usersFromDb = User::where("role_id", "2")
                ->leftJoin('demo_rankings', 'users.id', '=', 'demo_rankings.user_id')
                ->select('users.id', 'users.name', 'users.avatar_path', DB::raw("COALESCE(demo_rankings.rank, 999999) as 'rank'"))
                ->orderBy('rank')
                ->get();

            $this->users =  $usersFromDb->map(function (User $user, $index) {
                $userData = $user->toArray();
                $userData['avatarPath'] = $user->getFilamentAvatarUrl();
                return $userData;
            });
        } else {
            // Fall back to your original query if no rankings exist
            $usersFromDb = User::where("role_id", "2")->select("id", "name", "avatar_path")->get();
            // Add a default rank property to each user
            $this->users = $usersFromDb->map(function (User $user, $index) {
                $userData = $user->toArray();
                $userData['rank'] = $index + 1; // Default rank based on retrieval order
                $userData['avatarPath'] = $user->getFilamentAvatarUrl();
                return $userData;
            })->toArray();
        }
    }

    public function updateRankings($orderedIds)
    {
        DB::beginTransaction();

        try {
            foreach ($orderedIds as $index => $userId) {
                DemoRanking::updateOrCreate(
                    ['user_id' => $userId],
                    ['rank' => $index + 1]
                );
            }

            DB::commit();
            $this->loadUsers(); // Refresh the data
            $this->dispatch('rankingUpdated');
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            session()->flash('error', 'Failed to update rankings.');
        }
    }
}
