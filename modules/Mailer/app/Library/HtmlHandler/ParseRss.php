<?php

namespace Modules\Mailer\Library;

use League\Pipeline\StageInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

class ParseRss implements StageInterface
{
    public function __invoke($html)
    {
        $rss = new TwigFunction('rss', function ($url, $count = 10) {
            $dom = simplexml_load_string(self::urlGetContentsSslSafe($url), 'SimpleXMLElement', LIBXML_NOCDATA);
            $x = self::xmlToArray($dom);
            $x = ($x['rss']['channel']);
            $x['item'] = array_slice($x['item'], 0, $count);

            return $x;
        });

        $loader = new ArrayLoader([
            'content' => $html,
        ]);

        $twig = new Environment($loader);
        $twig->addFunction($rss);

        return $twig->render('content');
    }

    /**
     * Fetch URL contents with SSL verification fallback
     */
    private static function urlGetContentsSslSafe(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result = curl_exec($ch);

        if ($result === false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($ch);
        }

        curl_close($ch);

        return $result ?: '';
    }

    /**
     * Convert SimpleXMLElement to array
     */
    private static function xmlToArray(\SimpleXMLElement $xml): array
    {
        $json = json_encode($xml);

        return json_decode($json, true) ?: [];
    }
}
