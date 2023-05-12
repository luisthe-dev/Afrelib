<?php 

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{roomId}', function ($chat_id) {
    // return true if the user is authorized to join the channel
});

?>
