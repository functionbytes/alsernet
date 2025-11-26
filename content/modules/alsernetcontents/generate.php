<?php

ini_set('max_execution_time', 176000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');

$langs = [
    ['id_lang' => 1, 'iso_code' => 'es'],
    ['id_lang' => 2, 'iso_code' => 'en'],
    ['id_lang' => 3, 'iso_code' => 'fr'],
    ['id_lang' => 4, 'iso_code' => 'pt'],
    ['id_lang' => 5, 'iso_code' => 'de'],
    ['id_lang' => 6, 'iso_code' => 'it'],
];
// 4 => [["cms" => "8"],["cms" => "9"],["cms" => "12"],["cms" => "10"],["cms" => "87"],["cms" => "85"]],


$footers = [
    [
        "title" => [
            1 => "SOBRE ÁLVAREZ",
            2 => "ABOUT ÁLVAREZ",
            3 => "À PROPOS D'ÁLVAREZ",
            4 => "SOBRE ÁLVAREZ",
            5 => "ÜBER ALVAREZ",
            6 => "SU ÁLVAREZ",
        ],
        "type" => "items",
        "items" => [
            1 => [["cms" => "8"],["cms" => "9"],["cms" => "12"],["cms" => "10"],["title" => "Opiniones", "url" => "/opiniones"],["title" => "Contacto", "url" => "/contacto"],["cms" => "68"]],
            2 => [["cms" => "8"],["cms" => "9"],["cms" => "10"],["title" => "Contact", "url" => "/en/contact-us"],["cms" => "68"]],
            3 => [["cms" => "8"],["cms" => "9"],["cms" => "10"],["title" => "Contact", "url" => "/fr/contactez-nous"],["cms" => "68"]],
            4 => [["cms" => "8"],["cms" => "9"],["cms" => "10"],["title" => "Contato", "url" => "/pt/contacte-nos"],["cms" => "68"]],
            5 => [["cms" => "8"],["cms" => "9"],["cms" => "10"],["title" => "Kontact", "url" => "/de/kontakt"],["cms" => "68"]],
            6 => [["cms" => "8"],["cms" => "9"],["cms" => "10"],["title" => "Contatto", "url" => "/it/contacto"],["cms" => "68"]],


        ],
        "position" => "1"
    ],

    [
        "title" => [
            1 => "INFORMACIÓN",
            2 => "INFORMATION",
            3 => "INFORMATION",
            4 => "INFORMAÇÃO",
            5 => "INFORMATION",
            6 => "INFORMAZIONI",
        ],
        "items" => [
            1 => [["cms" => "11"],["cms" => "13"],["cms" => "1"],["cms" => "6"],["cms" => "70"],["cms" => "3"],["cms" => "7"],["cms" => "2"]],
            2 => [["cms" => "11"],["cms" => "13"],["cms" => "1"],["cms" => "6"],["cms" => "70"],["cms" => "3"],["cms" => "7"],["cms" => "2"]],
            3 => [["cms" => "11"],["cms" => "13"],["cms" => "1"],["cms" => "6"],["cms" => "70"],["cms" => "3"],["cms" => "7"],["cms" => "2"]],
            4 => [["cms" => "11"],["cms" => "13"],["cms" => "1"],["cms" => "6"],["cms" => "70"],["cms" => "3"],["cms" => "7"],["cms" => "2"]],
            5 => [["cms" => "11"],["cms" => "13"],["cms" => "1"],["cms" => "6"],["cms" => "70"],["cms" => "3"],["cms" => "7"],["cms" => "2"]],
            6 => [["cms" => "11"],["cms" => "13"],["cms" => "1"],["cms" => "6"],["cms" => "70"],["cms" => "3"],["cms" => "7"],["cms" => "2"]],
        ],
        "position" => "3"
    ],[
        "title" => [
            1 => "SISTEMA",
            2 => "SYSTEM",
            3 => "SYSTÈME",
            4 => "SISTEMA",
            5 => "SYSTEM",
            6 => "SISTEMA",
        ],
        "items" => [
            1 => [["title" => "Cambios y devoluciones", "url" => "https://returns.itsrever.com/alvarez?lang=es"],["cms" => "14"],["cms" => "15"],["title" => "Estado de mi pedido", "url" => "/seguimiento-pedido-invitado"]],
            2 => [["title" => "Returns and exchanges", "url" => "https://returns.itsrever.com/alvarez?lang=en"],["cms" => "14"],["cms" => "15"],["title" => "Status of my order", "url" => "/en/guest-tracking"]],
            3 => [["title" => "Retour et d'échange", "url" => "https://returns.itsrever.com/alvarez?lang=fr"],["cms" => "14"],["cms" => "15"],["title" => "Situation de ma commande", "url" => "/fr/suivi-de-commande-invite"]],
            4 => [["title" => "Devoluções e trocas", "url" => "https://returns.itsrever.com/alvarez?lang=pt"],["cms" => "14"],["cms" => "15"],["title" => "Mon compte Information", "url" => "/pt/seguimento-de-visitante"]],
            5 => [["title" => "Rrückgabe & umtausch", "url" => "https://returns.itsrever.com/alvarez?lang=de"],["cms" => "14"],["cms" => "15"],["title" => "Status meiner bestellung", "url" => "/de/auftragsverfolgung-gast"]],
            6 => [["title" => "Cambio e resi", "url" => "https://returns.itsrever.com/alvarez?lang=it"],["cms" => "14"],["cms" => "15"],["title" => "Stato del mio ordine", "url" => "/it/tracciamento-ordini-per-ospiti"]],
        ],
        "position" => "2"
    ]
];

function urlPath($cms_id, $id_lang) {
    $link = new Link();
    $cms = new CMS($cms_id, $id_lang);


    $url = $link->getCMSLink($cms, null, null, $id_lang);
    $title = $cms->meta_title;

    $url_parts = parse_url($url);

    return [
        "title" => $title,
        "url" => $url_parts['path']
    ];

}
function footer($value, $id_lang, $counter) {
    $datos_items = [];

    if (isset($value['items'][$id_lang])) {


        foreach ($value['items'][$id_lang] as $item) {

            if (isset($item['title']) && isset($item['url'])) {
                // Ítem personalizado con title y url
                $datos_items[] = [
                    "title" => $item['title'],
                    "url" => $item['url']
                ];
            } elseif (isset($item['cms'])) {
                // Ítem referenciando un CMS por ID
                // Aquí puedes implementar la lógica para obtener el title y url del CMS según el ID
                $cms_id = $item['cms'];
                $datos_items[] = urlPath($cms_id, $id_lang); // Función urlPath para obtener title y url
            }
        }



    }

    // Obtener el título
    $name = isset($value['title'][$id_lang]) ? ucfirst($value['title'][$id_lang]) : '';

    return [
        "title" => $name,
        "type" => isset($value['type']) ? $value['type'] : 'items', // Asegurarse de tener un valor por defecto
        "id" => $counter,
        "position" => isset($value['position']) ? $value['position'] : '',
        "items" => $datos_items
    ];
}
function generateFooterJson($footers, $langs) {
    foreach ($langs as $lang) {
        $counter = 1;
        $array = [];

        foreach ($footers as $footer) {
            // Llamar a la función footer para obtener los datos del footer
            $footerData = footer($footer, $lang['id_lang'], $counter);


            // Agregar los datos del footer al array
            $array[] = $footerData;

            // Incrementar el contador
            $counter++;
        }

        // Ruta donde se guardará el archivo JSON
        $ruta_padre = 'json/' . $lang['iso_code'];
        $ruta = $ruta_padre . '/footer.json';

        // Crear directorio si no existe
        if (!is_dir($ruta_padre)) {
            if (!mkdir($ruta_padre, 0777, true) && !is_dir($ruta_padre)) {
                echo "Ha ocurrido un error al crear el directorio $ruta_padre.";
                continue;
            }
        }

        // Codificar datos a JSON
        $jsonDatos = json_encode($array, JSON_PRETTY_PRINT);

        // Guardar el archivo JSON
        if (file_put_contents($ruta, $jsonDatos) === false) {
            echo "Ha ocurrido un error al crear/sobrescribir el archivo JSON en $ruta.";
        } else {
            echo "Se ha generado el archivo JSON correctamente en $ruta.<br>";
        }
    }

    echo "LISTO";
}

generateFooterJson($footers, $langs);
