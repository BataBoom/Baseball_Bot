<?php

namespace App\Telegram\Conversations;

use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardRemove;
use SergiX44\Nutgram\Conversations\InlineMenu;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Telegram\CacheHelpers\MLBTrait;
use Carbon\Carbon;
use App\Telegram\Anon\ViewMessageParser;

class MLBConversation extends InlineMenu
{
    use MLBTrait, ViewMessageParser;
    public $sportsScores;
    public $gameID;
    public $teamID;

    public function start(Nutgram $bot)
    {
        $this->menuText("Browsing MLB..")
            ->addButtonRow(
                InlineKeyboardButton::make(
                    "Live Scores",
                    callback_data: "upcoming"
                )
            )
            ->addButtonRow(
                InlineKeyboardButton::make(
                    "📰 Latest News",
                    callback_data: "news"
                )
            )
            ->orNext("none")
            ->showMenu();

        $this->next("parseMsgz");
    }

    public function parseMsgz(Nutgram $bot)
    {
        $callBackData = $bot->callbackQuery()->data;
        $this->clearButtons();
        //$this->closeMenu();

        if ($callBackData == "kill") {
            $this->closingIt($bot);
        }

    if ($callBackData == "upcoming") {
             //CacheHelper Trait
         $gitGames = $this->mlbSchedule();

        foreach ($gitGames as $game) {
            $fetchGames[] = [
                "GameIDz" => [
                    $game["gamePk"],
                    "https://statsapi.mlb.com/api/v1.1/game/" .
                    $game["gamePk"] .
                    "/feed/live",
                    $game["status"]["abstractGameState"],
                ],
                $game["teams"]["away"]["team"]["name"] => [
                    $game["teams"]["away"]["score"] ?? false,
                ],
                $game["teams"]["home"]["team"]["name"] => [
                    $game["teams"]["home"]["score"] ?? false,
                ],
            ];
        }

        $this->menuText("Browsing MLB..");

        foreach ($fetchGames as $game) {
            $keys = array_keys($game);

            $buttons[] = InlineKeyboardButton::make(
                $keys[1] . " @ " . $keys[2],
                callback_data: $game["GameIDz"][0].'@gameDetail'
            );
        }

        // Add the first row of buttons
        $this->addButtonRow($buttons[0], $buttons[1]);

        // Add the rest of the buttons
        for ($i = 2; $i < count($buttons) - 1; $i++) {
            if ($i % 2 == 0) {
                $this->addButtonRow($buttons[$i], $buttons[$i + 1]);
            }
        }

        // Add the last row of button
        $this->addButtonRow(
            InlineKeyboardButton::make("End/Kill", callback_data: "kill")
        );
        $this->orNext('gameDetail');
        // Show the menu
        $this->showMenu();

        } elseif ($callBackData == "news") {

           $bot->sendMessage('📰 Latest News..');

            $gitNews = Http::timeout(5)->get('https://site.api.espn.com/apis/site/v2/sports/baseball/mlb/news')->json();

            foreach($gitNews['articles'] as $k => $v) {
                
                $lastKey = array_key_last($gitNews['articles']);

                if($k !== $lastKey) {
                $bot->sendMessage($gitNews['articles'][$k]['description']." |LINK: ".$gitNews['articles'][$k]['links']['web']['href']);
                }
            }
            $this->closingIt($bot);
        }
    }

    public function gameDetail(Nutgram $bot)
    {
        $data = $bot->callbackQuery()->data;

        //if($this->gameID)
        $this->gameID = $data;

        //$this->clearButtons();
        //$this->closeMenu();

        if ($data == "kill") {
            $this->closingIt($bot);
        }

        //CacheHelper Trait
        $gitEvent = $this->getMLBGame($data);
       if(isset($gitEvent["liveData"]["linescore"]["currentInningOrdinal"])) {
                 $status =  $gitEvent["liveData"]["linescore"]["inningState"] .
                " " .
                $gitEvent["liveData"]["linescore"]["currentInningOrdinal"];
            } else {

                 $status = "First Pitch: ". Carbon::parse($gitEvent['gameData']['gameInfo']['firstPitch'])->diffForHumans();
            }

        $gameInfo = [
            $gitEvent["gameData"]["teams"]["away"]["teamName"] => [
                "runs" =>
                    $gitEvent["liveData"]["linescore"]["teams"]["away"][
                        "runs"
                    ] ?? false,
                "errors" =>
                    $gitEvent["liveData"]["linescore"]["teams"]["away"][
                        "errors"
                    ] ?? false,
                 "abr" => $gitEvent["gameData"]["teams"]["away"]["abbreviation"],
            ],
            $gitEvent["gameData"]["teams"]["home"]["teamName"] => [
                "runs" =>
                    $gitEvent["liveData"]["linescore"]["teams"]["home"][
                        "runs"
                    ] ?? false,
                "errors" =>
                    $gitEvent["liveData"]["linescore"]["teams"]["home"][
                        "errors"
                    ] ?? false,
                "abr" => $gitEvent["gameData"]["teams"]["home"]["abbreviation"],
            ],
            "Status" =>
                $status,
            "Weather" =>
                $gitEvent["gameData"]["weather"]["condition"] .
                " " .
                $gitEvent["gameData"]["weather"]["temp"] .
                " degrees. " .
                "Wind: " .
                $gitEvent["gameData"]["weather"]["wind"],
            "Pitchers" => [  $gitEvent["gameData"]["teams"]["away"]["teamName"] => $gitEvent["gameData"]["probablePitchers"]["away"]["fullName"], $gitEvent["gameData"]["teams"]["home"]["teamName"] => $gitEvent["gameData"]["probablePitchers"]["home"]["fullName"]],
        ];

        $teamKeys = array_keys($gameInfo);
        $awayteam = $teamKeys[0];
        $hometeam = $teamKeys[1];
        //$gameDetail[$awayteam];
        //$gameDetail[$hometeam];

        $array[] = $this->parseViewMessage('mlb.index', [
                'homeTeam' => $hometeam,
                'awayTeam' => $awayteam,
                "pitchingHome" => $gameInfo["Pitchers"][$hometeam],
                "pitchingAway" => $gameInfo["Pitchers"][$awayteam],
                "scoreAway" => $gameInfo[$awayteam]["runs"],
                "scoreHome" => $gameInfo[$hometeam]["runs"],
                "errorsAway" => $gameInfo[$awayteam]["errors"],
                "errorsHome" => $gameInfo[$hometeam]["errors"],
                'abrHome' => $gameInfo[$hometeam]['abr'],
                'abrAway' => $gameInfo[$awayteam]['abr'],
                'Weather' => $gameInfo['Weather'],
                'Status' => $gameInfo['Status']
            ]);
        
        $string = implode(' ', $array);
       
        
        $bot->sendChunkedMessage($string);

        
    }

     private function teamRecord($teamID) {

        // need mlb teams db for this to work
        $team = json_decode(file_get_contents('https://site.api.espn.com/apis/site/v2/sports/baseball/mlb/seasons/2023/teams/'.$teamID), true);
        return ['record'=> $team['team']['record']['items'][0]['summary'], 'homeRecord' => $team['team']['record']['items'][1]['summary'],'awayRecord' => $team['team']['record']['items'][2]['summary']];

    }

    public function closingIt(Nutgram $bot)
    {
        $this->clearButtons();
        $this->none($bot);
    }

    public function none(Nutgram $bot)
    {
        
        $this->end();
    }
}