<?php

namespace App\Telegram\Anon;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Attributes\ParseMode;
use Illuminate\Support\Str;

/* https://github.com/Lukasss93/telegram-stickeroptimizer/blob/d9d3076ff07cc7422d3fc85c72fbad927d21d163/app/Helpers/Utils.php */

trait ViewMessageParser {
/**
 * Return a formatted string (in C# like)
 * @param string $string
 * @param array $args
 * @return string
 */
public function f(string $string, array $args = []): string
{
    preg_match_all('/(?={){(\d+)}(?!})/', $string, $matches, PREG_OFFSET_CAPTURE);
    $offset = 0;
    foreach ($matches[1] as $data) {
        $i = $data[0];
        $string = substr_replace($string, @$args[$i], $offset + $data[1] - 1, 2 + strlen($i));
        $offset += strlen(@$args[$i]) - 2 - strlen($i);
    }

    return $string;
}

/**
 * Render an HTML message
 * @param string $view
 * @param array $values
 * @return string
 */

public function parseViewMessage(string $view, array $values = []): string
{
    return rescue(function () use ($view, $values) {
        return (string)Str::of(view("messages.$view", $values)->render())
            ->replaceMatches('/\r\n|\r|\n/', '')
            ->replace(['<br>', '<BR>'], "\n");
    }, 'messages.'.$view, false);
}


}