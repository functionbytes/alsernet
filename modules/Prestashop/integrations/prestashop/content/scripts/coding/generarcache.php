<?php

include(dirname(__FILE__) . '/../config/config.inc.php');

//Cantidad máxima de URLs a procesar en cada iteración
$file = "generarcache.log";
$baseURL = 'https://www.a-alvarez.com/';
$dbOkitup = connectOkitup();
$version = "html"; //$version = "ajax"; El menu se carga mediante ajax

$tiempo_inicial_total = microtime(true);
$maxurlsPerIteration = 100;
$timeLapsePerIteration = 0.5;
$paginas = 1;
$inicio = 0;
$maxurls = 999999999999999;
$categorias = false;
$productos = false;
$todo = true;
$idiomas = ['es','en','fr','pt', 'de'];
$debug = false;
$url = false;
$products = [];

if (is_array($argv)) {
  foreach ($argv as $parametro) {
    if (strpos($parametro, "url=")!==false) {
      $url = str_replace('url=','',$parametro);
    }
    if (strpos($parametro, "producto=")!==false) {
      $id_product = str_replace('producto=','',$parametro);
    }
    if (strpos($parametro, "inicio")!==false) {
      $inicio = str_replace('inicio=','',$parametro);
    }
    if (strpos($parametro, "maxurls")!==false) {
      $maxurls = str_replace('maxurls=','',$parametro);
    }
    if (strpos($parametro, "paginas")!==false) {
      $paginas = str_replace('paginas=','',$parametro);
    }
    if (strpos($parametro, "idiomas")!==false) {
      $idiomas = explode(",",str_replace('idiomas=','',$parametro));
    }
    if ($parametro == "categorias") {
      $categorias = true;
      $todo = false;
    }
    if ($parametro == "productos") {
      $productos = true;
      $todo = false;
    }
    if ($parametro == "debug") {
      $debug = true;
    }
  }
}

if ($url) {

  $tiempo_inicial = microtime(true);

  $httpCode = httpCodeUrl($url);

  $tiempo_final = round(microtime(true) - $tiempo_inicial, 3);
  echo $tiempo_final."seg\n";
  die;
}

$urlsok = 0;
$urlsko = 0;
$count = $inicio;
$idiomasSQL = "";
foreach ($idiomas as $idioma) {

  $sql = "SELECT id_lang FROM aalv_lang WHERE iso_code='".$idioma."'";
  $result = mysqli_query($dbOkitup, $sql);
  $id_lang = mysqli_fetch_assoc($result)['id_lang'];
  $ids_idiomas[$id_lang] = $idioma;
}

$idiomasSQL = implode(",",array_keys($ids_idiomas));

if ($categorias) {

  if ($debug) {
    echo "Categorías\n";
  }else{
    file_put_contents($file, "Categorías\n", FILE_APPEND | LOCK_EX);
  }

  foreach ($idiomas as $idioma) {

    $urls = obtenerURLsAlvarez($baseURL, $idioma, $version);
    foreach ($urls as $url) {
      for ($pagina=0;$pagina<=$paginas;$pagina++) {
        if ($pagina == 1) continue;
        $count++;
        if ($debug) echo $count."\n";
        if ($idioma == "es") {
          $productURL = $baseURL . $url;
        }else{
          $productURL = $baseURL . $idioma."/".$url;
        }
        if ($pagina > 1) {
          $productURL = $productURL."?page=".$pagina;
        }
        if ($debug) echo $productURL."\n";

        $tiempo_inicial = microtime(true);

        $httpCode = httpCodeUrl($productURL);

        $tiempo_final = round(microtime(true) - $tiempo_inicial, 3);
        if ($debug) echo $tiempo_final."seg\n";

        if ($tiempo_final > 2) {
          $tiempo_inicial = microtime(true);
          $httpCode = httpCodeUrl($productURL);
          $tiempo_final = round(microtime(true) - $tiempo_inicial, 3);
          if ($debug) echo $tiempo_final."seg\n";
        }

        if (!$httpCode) {
          $urlsko++;
          if ($debug) {
            echo "NO RESPONDE\n";
          }else{
            file_put_contents($file, "NO RESPONDE\n\n", FILE_APPEND | LOCK_EX);
          }
        } elseif ($httpCode == 200) {
          $urlsok++;
        } else {
          $urlsko++;
        }
        if ($timeLapsePerIteration) sleep($timeLapsePerIteration);
      }
    }
  }
}

if ($productos || $id_product) {
  if ($debug) {
    echo "Productos\n";
  }else{
    file_put_contents($file, "Productos\n", FILE_APPEND | LOCK_EX);
  }

  if ($id_product) {
    $where_product = " AND p.id_product = ".$id_product;
  }

  $sql = "SELECT count(1) AS contador
          FROM aalv_product_lang pl
          INNER JOIN aalv_product p ON pl.id_product=p.id_product
          WHERE pl.link_rewrite IS NOT NULL
            AND p.active=1
            AND id_lang IN (".$idiomasSQL.")".$where_product;

  //$results = Db::getInstance()->ExecuteS($sql);
  $result = mysqli_query($dbOkitup, $sql);
  $totalUrls = mysqli_fetch_assoc($result)['contador'];
  if ($id_product) {
    $maxurls = $totalUrls;
  }

  echo "URLs a procesar: ".$totalUrls."\n\n";
  if ($debug) echo "Productos\n";

  for ($i = $inicio;;) {
      $sql = "SELECT p.id_product, pl.link_rewrite, pl.id_lang
              FROM aalv_product_lang pl
              INNER JOIN aalv_product p ON pl.id_product=p.id_product
              WHERE link_rewrite IS NOT NULL
                  AND p.active=1
                  AND id_lang IN (".$idiomasSQL.")
                  ".$where_product."
              GROUP BY id_product, link_rewrite
              LIMIT ". $maxurlsPerIteration." OFFSET ".$i;

      //$results = Db::getInstance()->ExecuteS($sql);
      $result = mysqli_query($dbOkitup, $sql);
      if ($result) {
        while ($a = mysqli_fetch_assoc($result)) {
          $products[] = $a;
        }
      }

      if (count($products) <= 0 || ($maxurls > 0 && $i > $maxurls)) {
          break;
      }

      foreach ($products as $product) {
          $count++;
          if ($debug) echo $count."\n";
          $productId = $product['id_product'];
          $link = $product['link_rewrite'];
          if ($product['id_lang'] > 1) {
            $idioma = $ids_idiomas[$product['id_lang']];
            $productURL = $baseURL . $idioma . "/" . $productId . '-' . $link;
          }else{
            $productURL = $baseURL . $productId . '-' . $link;
          }
          if ($debug) echo $productURL."\n";

          $tiempo_inicial = microtime(true);

          $httpCode = httpCodeUrl($productURL);

          $tiempo_final = round(microtime(true) - $tiempo_inicial, 3);
          if ($debug) echo $tiempo_final."seg\n";

          if (!$httpCode) {
            $urlsko++;
            if ($debug) echo "NO RESPONDE\n\n";
          } elseif ($httpCode == 200) {
            $urlsok++;
          } else {
            $urlsko++;
          }

          if ($timeLapsePerIteration) sleep($timeLapsePerIteration);
      }
      $i+=$maxurlsPerIteration;
      if ($timeLapsePerIteration) sleep($timeLapsePerIteration);
  }
}

$tiempo_final_total = round(microtime(true) - $tiempo_inicial_total, 3);
echo "Total de urls recorridas ok: ".$urlsok."\n";
if ($urlsok > 0) {
    echo "Porcentaje de urls recorridas ok: " . ($urlsok / $totalUrls * 100) . "%\n";
}
echo "Total de páginas no recorridas: ".$urlsko."\n";
echo "Duración del proceso: ".$tiempo_final_total."\n";
die;


function obtenerURLsAlvarez($baseURL, $idioma = 'es', $version = "html") {

  $urls_contenido = [];

  if ($version == "html") {
    if ($idioma == "es") {
      $contenido_pagina = file_get_contents($baseURL);
      $patron = '/href=["\'](?:https?:\/\/)?(?:www\.)?a-alvarez\.com\/([^0-9][a-zA-Z\/\_\-]*)["\']/';
    }else{
      $contenido_pagina = file_get_contents($baseURL.$idioma."/");
      $patron = '/href=["\'](?:https?:\/\/)?(?:www\.)?a-alvarez\.com\/'.$idioma.'\/([^0-9][a-zA-Z\/\_\-]*)["\']/';
    }
    preg_match_all($patron, $contenido_pagina, $urls);
    $urls_contenido = $urls[1];
  }else{
    for ($i=3;$i<=11;$i++) {
      $url_categoria = $baseURL."module/alsernetmenu/menu?language=".$idioma."&method=category&category=".$i;
      $contenido_pagina = file_get_contents($url_categoria);

      if ($idioma == "es") {
        $patron = '/href=["\']\/([^0-9][a-zA-Z\/\_\-]*)["\']/';
      }else{
        $patron = '/href=["\']\/'.$idioma.'\/([^0-9][a-zA-Z\/\_\-]*)["\']/';
      }

      preg_match_all($patron, $contenido_pagina, $urls);

      $urls_contenido = array_merge($urls[1], $urls_contenido);
    }
  }

  return $urls_contenido;
}

function httpCodeUrl($url) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 15);
  if (!curl_exec($ch)) {
    return false;
  } else {
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  }
  curl_close($ch);
  return $httpCode;
}

function connectOkitup() {

  return $dbcon;
}

?>
