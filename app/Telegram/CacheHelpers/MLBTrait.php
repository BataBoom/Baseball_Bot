<?php


namespace App\Telegram\CacheHelpers;

use Illuminate\Support\Facades\Cache;
use DateTime;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait MLBTrait {
    public function mlbSchedule() {

        $carbon = Carbon::now();
        $carbon->tz('America/Los_Angeles');
        $formattedDate = $carbon->format('Y-m-d');

    	if (Cache::has('mlbschedule_'.$formattedDate)) {
            
        } else {
        	$gitEvent = Http::get('https://statsapi.mlb.com/api/v1/schedule?sportId=1,51&date='.$formattedDate)->json()['dates'][0]['games'];
        	Cache::put('mlbschedule_'.$formattedDate, $gitEvent, now()->addHours(12));	
        }

        $item = Cache::get('mlbschedule_'.$formattedDate);


        return $item ?? null;
    }

    public function getMLBGame($gameiD) {

        if (Cache::has('mlb_'.$gameiD)) {
        $value = Cache::get('mlb_'.$gameiD);
        } else {
        $gitEvent = Http::get('https://statsapi.mlb.com/api/v1.1/game/'.$gameiD.'/feed/live')->json();
        Cache::put('mlb_'.$gameiD, $gitEvent, now()->addMinutes(30));
        $value = Cache::get('mlb_'.$gameiD);
        }
      
        return $value;
    }


}