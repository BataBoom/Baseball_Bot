<?php

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Command\BotCommandScopeAllPrivateChats;
use SergiX44\Nutgram\Telegram\Types\Command\BotCommandScopeChatMember;
use SergiX44\Nutgram\Telegram\Exceptions\TelegramException;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Telegram\Properties\MessageEntityType;
use SergiX44\Nutgram\Telegram\Properties\UpdateType;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use App\Telegram\Conversations\MLBConversation;
use App\Telegram\Commands\StartCommand;

/*
|--------------------------------------------------------------------------
| Nutgram Handlers
|--------------------------------------------------------------------------
|
| Here is where you can register telegram handlers for Nutgram. These
| handlers are loaded by the NutgramServiceProvider. Enjoy!
|
*/

$bot->group(function (Nutgram $bot) {

$bot->onCommand('mlb', function (Nutgram $bot) { 
    MLBConversation::begin($bot);
})->description('latest baseball scores');

$bot->onCommand('start', [StartCommand::class, 'handle'])
    ->description('useful commands');

// Called only for unmatched messages
$bot->fallbackOn(UpdateType::MESSAGE, function (Nutgram $bot) {
    $bot->sendMessage('Sorry, I don\'t understand.');
});

$bot->fallbackOn(UpdateType::CALLBACK_QUERY, function (Nutgram $bot) {
    $bot->answerCallbackQuery();
    $bot->end();
});

})->middleware(isPrivateChat::class);


//$bot->onApiError([ExceptionsHandler::class, 'api']);
//$bot->onException([ExceptionsHandler::class, 'global']);