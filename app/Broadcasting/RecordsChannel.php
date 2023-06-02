<?php 
namespace App\Broadcasting;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class RecordsChannel
{
    public function join(User $user): bool
    {
        // Implement your authorization logic here
        return true;
    }

    public function broadcastOn()
    {
        return new Channel('records-channel');
    }
}
    

?>