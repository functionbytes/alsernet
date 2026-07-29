<?php

function translategoogle($text, $idiomasrc, $idiomadest)
{

    $apiKey = 'disabled_api_key';
    $url = 'https://www.googleapis.com/language/translate/v2?key='.$apiKey.'&q='.rawurlencode($text).'&source='.$idiomasrc.'&target='.$idiomadest;

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($handle);

    echo $response;

    $responseDecoded = json_decode($response, true);
    curl_close($handle);

    return ''.$responseDecoded['data']['translations'][0]['translatedText'];

}

echo translategoogle('Mi casa es hermosa', 'es', 'kpoolll');

echo 'acaba';
