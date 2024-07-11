<?php

namespace App\Telegram\Middleware;

use SergiX44\Nutgram\Nutgram;
use Illuminate\Support\Str;
use Carbon\Carbon;

class isPrivateChat
{
    public function __invoke(Nutgram $bot, $next): void
    {
        if($bot->getChat($bot->chatId())->type->value !== 'group') {
            $next($bot);

        } else {
            $bot->sendMessage("This feature is only available in THiS chat", $bot->userId());
        }

    }
}