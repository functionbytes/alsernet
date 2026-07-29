<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../../config/config.inc.php';
ini_set('memory_limit','1024M');
include ('/home/alvarez/LOG_INTEGRACION/dbantigua.php');

$dbcon = connectBD();

$attribute = Db::getInstance()->executeS("select apa.reference from aalv_product_attribute apa where apa.id_product_attribute != '' order by id_product_attribute asc");
$nn = 0;
$con = 0;
foreach ($attribute as $value) {
    # code...
    $sql_antigua    = "select * from portes_producto where referencia = '".$value['reference']."'";
    $result_antigua = mysqli_query($dbcon, $sql_antigua);

    if(mysqli_num_rows($result_antigua) > 0){
        while ($re_antigua = mysqli_fetch_array($result_antigua, MYSQLI_ASSOC)) {
            $attribute = Db::getInstance()->executeS("select * from aalv_portes_producto app where referencia = '".$re_antigua['referencia']."' and id_origen = '".$re_antigua['id']."' AND codigo = '".$re_antigua['codigo']."' AND id_pais_origen = '".$re_antigua['id_pais']."'");
            if(count($attribute) == 0){
                // if (!esportestandard($re_antigua['codigo'])) {
                //     $idpaisps = getpaisps($re_antigua["id_pais"]);
                    // Db::getInstance()->Execute("INSERT INTO aalv_portes_producto
                    // (id_origen,referencia,codigo,id_pais_origen,id_pais,id_product,id_product_attribute)
                    // VALUES
                    // (" . $re_antigua["id"] . ",'" . $value['reference'] . "','" . $re_antigua["codigo"] . "'," . $re_antigua["id_pais"] . "," . $idpaisps . "," . $value['id_product'] . "," . $value['id_product_attribute'] . ")");
                    dump($attribute);
                    dump($re_antigua);
                    dump($value);
                    die();
                // }
            }
        }
    }
    echo ".";
    $nn++;
    if($nn == 200){
        $con = $con + 200;
        echo " => ".$con;
        echo "\n";
        $nn = 0;
    }
}










function getpaisps($idorigen)
{

    $idreturn = 0;

    if ($idorigen == 53) $idreturn = 40;
    if ($idorigen == 125) $idreturn = 215;
    if ($idorigen == 155) $idreturn = 229;
    if ($idorigen == 94) $idreturn = 43;
    if ($idorigen == 93) $idreturn = 42;
    if ($idorigen == 56) $idreturn = 228;
    if ($idorigen == 65) $idreturn = 45;
    if ($idorigen == 133) $idreturn = 41;
    if ($idorigen == 77) $idreturn = 44;
    if ($idorigen == 13) $idreturn = 2;
    if ($idorigen == 135) $idreturn = 24;
    if ($idorigen == 95) $idreturn = 46;
    if ($idorigen == 66) $idreturn = 47;
    if ($idorigen == 57) $idreturn = 231;
    if ($idorigen == 98) $idreturn = 51;
    if ($idorigen == 162) $idreturn = 50;
    if ($idorigen == 14) $idreturn = 3;
    if ($idorigen == 168) $idreturn = 60;
    if ($idorigen == 42) $idreturn = 233;
    if ($idorigen == 163) $idreturn = 49;
    if ($idorigen == 169) $idreturn = 62;
    if ($idorigen == 164) $idreturn = 54;
    if ($idorigen == 96) $idreturn = 55;
    if ($idorigen == 167) $idreturn = 59;
    if ($idorigen == 80) $idreturn = 34;
    if ($idorigen == 76) $idreturn = 58;
    if ($idorigen == 97) $idreturn = 48;
    if ($idorigen == 170) $idreturn = 56;
    if ($idorigen == 166) $idreturn = 57;
    if ($idorigen == 62) $idreturn = 52;
    if ($idorigen == 69) $idreturn = 53;
    if ($idorigen == 91) $idreturn = 4;
    if ($idorigen == 215) $idreturn = 71;
    if ($idorigen == 213) $idreturn = 66;
    if ($idorigen == 214) $idreturn = 72;
    if ($idorigen == 39) $idreturn = 19;
    if ($idorigen == 127) $idreturn = 32;
    if ($idorigen == 82) $idreturn = 68;
    if ($idorigen == 126) $idreturn = 64;
    if ($idorigen == 136) $idreturn = 5;
    if ($idorigen == 79) $idreturn = 69;
    if ($idorigen == 70) $idreturn = 73;
    if ($idorigen == 110) $idreturn = 75;
    if ($idorigen == 156) $idreturn = 65;
    if ($idorigen == 15) $idreturn = 76;
    if ($idorigen == 34) $idreturn = 16;
    if ($idorigen == 12) $idreturn = 1;
    if ($idorigen == 236) $idreturn = 77;
    if ($idorigen == 16) $idreturn = 20;
    if ($idorigen == 99) $idreturn = 78;
    if ($idorigen == 92) $idreturn = 79;
    if ($idorigen == 123) $idreturn = 38;
    if ($idorigen == 84) $idreturn = 81;
    if ($idorigen == 19) $idreturn = 86;
    if ($idorigen == 124) $idreturn = 82;
    if ($idorigen == 177) $idreturn = 85;
    if ($idorigen == 1) $idreturn = 6;
    if ($idorigen == 10) $idreturn = 244;
    if ($idorigen == 9) $idreturn = 243;
    if ($idorigen == 8) $idreturn = 242;
    if ($idorigen == 178) $idreturn = 87;
    if ($idorigen == 20) $idreturn = 7;
    if ($idorigen == 179) $idreturn = 90;
    if ($idorigen == 203) $idreturn = 145;
    if ($idorigen == 21) $idreturn = 8;
    if ($idorigen == 180) $idreturn = 91;
    if ($idorigen == 33) $idreturn = 17;
    if ($idorigen == 183) $idreturn = 95;
    if ($idorigen == 67) $idreturn = 93;
    if ($idorigen == 88) $idreturn = 238;
    if ($idorigen == 37) $idreturn = 101;
    if ($idorigen == 182) $idreturn = 94;
    if ($idorigen == 112) $idreturn = 97;
    if ($idorigen == 181) $idreturn = 92;
    if ($idorigen == 138) $idreturn = 102;
    if ($idorigen == 101) $idreturn = 98;
    if ($idorigen == 184) $idreturn = 84;
    if ($idorigen == 22) $idreturn = 9;
    if ($idorigen == 72) $idreturn = 100;
    if ($idorigen == 185) $idreturn = 103;
    if ($idorigen == 85) $idreturn = 104;
    if ($idorigen == 117) $idreturn = 22;
    if ($idorigen == 73) $idreturn = 107;
    if ($idorigen == 58) $idreturn = 74;
    if ($idorigen == 113) $idreturn = 105;
    if ($idorigen == 186) $idreturn = 142;
    if ($idorigen == 118) $idreturn = 110;
    if ($idorigen == 24) $idreturn = 26;
    if ($idorigen == 140) $idreturn = 29;
    if ($idorigen == 139) $idreturn = 109;
    if ($idorigen == 187) $idreturn = 112;
    if ($idorigen == 188) $idreturn = 111;
    if ($idorigen == 49) $idreturn = 108;
    if ($idorigen == 25) $idreturn = 10;
    if ($idorigen == 38) $idreturn = 115;
    if ($idorigen == 114) $idreturn = 114;
    if ($idorigen == 141) $idreturn = 116;
    if ($idorigen == 119) $idreturn = 11;
    if ($idorigen == 142) $idreturn = 118;
    if ($idorigen == 192) $idreturn = 122;
    if ($idorigen == 171) $idreturn = 63;
    if ($idorigen == 193) $idreturn = 119;
    if ($idorigen == 174) $idreturn = 70;
    if ($idorigen == 106) $idreturn = 178;
    if ($idorigen == 175) $idreturn = 120;
    if ($idorigen == 176) $idreturn = 28;
    if ($idorigen == 128) $idreturn = 121;
    if ($idorigen == 100) $idreturn = 234;
    if ($idorigen == 191) $idreturn = 117;
    if ($idorigen == 194) $idreturn = 123;
    if ($idorigen == 143) $idreturn = 125;
    if ($idorigen == 107) $idreturn = 179;
    if ($idorigen == 45) $idreturn = 129;
    if ($idorigen == 223) $idreturn = 195;
    if ($idorigen == 196) $idreturn = 127;
    if ($idorigen == 195) $idreturn = 126;
    if ($idorigen == 27) $idreturn = 130;
    if ($idorigen == 28) $idreturn = 12;
    if ($idorigen == 26) $idreturn = 124;
    if ($idorigen == 197) $idreturn = 128;
    if ($idorigen == 129) $idreturn = 151;
    if ($idorigen == 48) $idreturn = 147;
    if ($idorigen == 63) $idreturn = 146;
    if ($idorigen == 59) $idreturn = 149;
    if ($idorigen == 144) $idreturn = 133;
    if ($idorigen == 189) $idreturn = 139;
    if ($idorigen == 60) $idreturn = 132;
    if ($idorigen == 201) $idreturn = 137;
    if ($idorigen == 165) $idreturn = 61;
    if ($idorigen == 204) $idreturn = 148;
    if ($idorigen == 105) $idreturn = 140;
    if ($idorigen == 145) $idreturn = 141;
    if ($idorigen == 29) $idreturn = 138;
    if ($idorigen == 202) $idreturn = 35;
    if ($idorigen == 200) $idreturn = 136;
    if ($idorigen == 199) $idreturn = 134;
    if ($idorigen == 89) $idreturn = 144;
    if ($idorigen == 198) $idreturn = 135;
    if ($idorigen == 157) $idreturn = 152;
    if ($idorigen == 205) $idreturn = 153;
    if ($idorigen == 208) $idreturn = 158;
    if ($idorigen == 158) $idreturn = 31;
    if ($idorigen == 74) $idreturn = 157;
    if ($idorigen == 30) $idreturn = 13;
    if ($idorigen == 159) $idreturn = 23;
    if ($idorigen == 207) $idreturn = 155;
    if ($idorigen == 206) $idreturn = 154;
    if ($idorigen == 146) $idreturn = 27;
    if ($idorigen == 209) $idreturn = 162;
    if ($idorigen == 75) $idreturn = 166;
    if ($idorigen == 78) $idreturn = 169;
    if ($idorigen == 212) $idreturn = 167;
    if ($idorigen == 137) $idreturn = 170;
    if ($idorigen == 210) $idreturn = 163;
    if ($idorigen == 31) $idreturn = 14;
    if ($idorigen == 160) $idreturn = 181;
    if ($idorigen == 111) $idreturn = 172;
    if ($idorigen == 11) $idreturn = 15;
    if ($idorigen == 36) $idreturn = 245;
    if ($idorigen == 211) $idreturn = 164;
    if ($idorigen == 83) $idreturn = 168;
    if ($idorigen == 172) $idreturn = 173;
    if ($idorigen == 40) $idreturn = 36;
    if ($idorigen == 61) $idreturn = 188;
    if ($idorigen == 44) $idreturn = 175;
    if ($idorigen == 216) $idreturn = 176;
    if ($idorigen == 122) $idreturn = 186;
    if ($idorigen == 190) $idreturn = 192;
    if ($idorigen == 219) $idreturn = 189;
    if ($idorigen == 225) $idreturn = 196;
    if ($idorigen == 35) $idreturn = 18;
    if ($idorigen == 120) $idreturn = 25;
    if ($idorigen == 18) $idreturn = 191;
    if ($idorigen == 17) $idreturn = 37;
    if ($idorigen == 220) $idreturn = 190;
    if ($idorigen == 46) $idreturn = 184;
    if ($idorigen == 147) $idreturn = 187;
    if ($idorigen == 222) $idreturn = 193;
    if ($idorigen == 87) $idreturn = 197;
    if ($idorigen == 218) $idreturn = 185;
    if ($idorigen == 71) $idreturn = 83;
    if ($idorigen == 221) $idreturn = 200;
    if ($idorigen == 224) $idreturn = 199;
    if ($idorigen == 173) $idreturn = 67;
    if ($idorigen == 230) $idreturn = 33;
    if ($idorigen == 153) $idreturn = 204;
    if ($idorigen == 228) $idreturn = 202;
    if ($idorigen == 229) $idreturn = 80;
    if ($idorigen == 231) $idreturn = 210;
    if ($idorigen == 132) $idreturn = 208;
    if ($idorigen == 154) $idreturn = 206;
    if ($idorigen == 43) $idreturn = 209;
    if ($idorigen == 109) $idreturn = 207;
    if ($idorigen == 232) $idreturn = 212;
    if ($idorigen == 121) $idreturn = 201;
    if ($idorigen == 227) $idreturn = 203;
    if ($idorigen == 64) $idreturn = 214;
    if ($idorigen == 148) $idreturn = 213;
    if ($idorigen == 90) $idreturn = 21;
    if ($idorigen == 86) $idreturn = 216;
    if ($idorigen == 233) $idreturn = 217;
    if ($idorigen == 47) $idreturn = 106;
    if ($idorigen == 108) $idreturn = 182;
    if ($idorigen == 81) $idreturn = 219;
    if ($idorigen == 104) $idreturn = 221;
    if ($idorigen == 102) $idreturn = 222;
    if ($idorigen == 149) $idreturn = 220;
    if ($idorigen == 234) $idreturn = 218;
    if ($idorigen == 217) $idreturn = 183;
    if ($idorigen == 235) $idreturn = 225;
    if ($idorigen == 131) $idreturn = 30;
    if ($idorigen == 150) $idreturn = 226;
    if ($idorigen == 151) $idreturn = 227;



    return $idreturn;
}

function esportestandard($porte)
{

    $portestandard = array();

    $portestandard[] = "A";
    $portestandard[] = "CM1";
    $portestandard[] = "B";
    $portestandard[] = "AA";
    $portestandard[] = "ALEMANIA STANDARD";
    $portestandard[] = "ALEMANIA  EXPRESS";
    $portestandard[] = "AUSTRIA STANDARD";
    $portestandard[] = "AUSTRIA EXPRESS";
    $portestandard[] = "BELGICA STANDARD";
    $portestandard[] = "BELGICA EXPRESS";
    $portestandard[] = "CHIPRE STANDARD";
    $portestandard[] = "CHIPRE  EXPRESS";
    $portestandard[] = "DINAMARCA STANDARD";
    $portestandard[] = "DINAMARCA  EXPRESS";
    $portestandard[] = "ESLOVAQUIA STANDARD";
    $portestandard[] = "ESLOVAQUIA  EXPRESS";
    $portestandard[] = "ESLOVENIA STANDARD";
    $portestandard[] = "ESLOVENIA  EXPRESS";
    $portestandard[] = "ESTONIA STANDARD";
    $portestandard[] = "ESTONIA  EXPRESS";
    $portestandard[] = "FINLANDIA STANDARD";
    $portestandard[] = "FINLANDIA  EXPRESS";
    $portestandard[] = "FRANCIA STANDARD";
    $portestandard[] = "FRANCIA EXPRESS";
    $portestandard[] = "GRECIA STANDARD";
    $portestandard[] = "GRECIA  EXPRESS";
    $portestandard[] = "HUNGRIA STANDARD";
    $portestandard[] = "HUNGRIA  EXPRESS";
    $portestandard[] = "IRLANDA STANDARD";
    $portestandard[] = "IRLANDA  EXPRESS";
    $portestandard[] = "ITALIA STANDARD";
    $portestandard[] = "ITALIA EXPRESS";
    $portestandard[] = "LETONIA STANDARD";
    $portestandard[] = "LETONIA  EXPRESS";
    $portestandard[] = "LITUANIA STANDARD";
    $portestandard[] = "LITUANIA  EXPRESS";
    $portestandard[] = "LUXEMBURGO STANDARD";
    $portestandard[] = "LUXEMBURGO  EXPRESS";
    $portestandard[] = "MALTA STANDARD";
    $portestandard[] = "MALTA  EXPRESS";
    $portestandard[] = "HOLANDA STANDARD";
    $portestandard[] = "HOLANDA  EXPRESS";
    $portestandard[] = "POLONIA STANDARD";
    $portestandard[] = "POLONIA  EXPRESS";
    $portestandard[] = "REINO UNIDO STANDARD";
    $portestandard[] = "REINO UNIDO  EXPRESS";
    $portestandard[] = "REP. CHECA STANDARD";
    $portestandard[] = "REP. CHECA  EXPRESS";
    $portestandard[] = "SUECIA STANDARD";
    $portestandard[] = "SUECIA  EXPRESS";
    $portestandard[] = "GUERNSEY STANDARD";
    $portestandard[] = "JERSEY STANDARD";
    $portestandard[] = "SUIZA STANDARD";
    $portestandard[] = "SUIZA EXPRESS";
    $portestandard[] = "RUMANIA STANDARD";
    $portestandard[] = "RUMANIA EXPRESS";
    $portestandard[] = "NORUEGA STANDARD";
    $portestandard[] = "NORUEGA EXPRESS";
    $portestandard[] = "BULGARIA STANDARD";
    $portestandard[] = "BULGARIA  EXPRESS";
    $portestandard[] = "TURQUIA STANDARD";
    $portestandard[] = "TURQUIA EXPRESS";
    $portestandard[] = "RUSIA EXPRESS";
    $portestandard[] = "LIECHTESTEIN EXPRESS";
    $portestandard[] = "MONACO EXPRESS";
    $portestandard[] = "ISLANDIA EXPRESS";
    $portestandard[] = "CROACIA EXPRESS";
    $portestandard[] = "ALBANIA EXPRESS";
    $portestandard[] = "BOSNIA EXPRESS";
    $portestandard[] = "MONTENEGRO EXPRESS";
    $portestandard[] = "MACEDONIA EXPRESS";
    $portestandard[] = "SERVIA EXPRESS";
    $portestandard[] = "BIELORRUSIA EXPRESS";
    $portestandard[] = "MOLDAVIA EXPRESS";
    $portestandard[] = "UCRANIA EXPRESS";
    $portestandard[] = "ARMENIA EXPRESS";
    $portestandard[] = "GEORGIA EXPRESS";
    $portestandard[] = "COSTA RICA EXPRESS";
    $portestandard[] = "EL SALVADOR EXPRESS";
    $portestandard[] = "GUATEMALA EXPRESS";
    $portestandard[] = "HONDURAS EXPRESS";
    $portestandard[] = "NICARAGUA EXPRESS";
    $portestandard[] = "PANAMA EXPRESS";
    $portestandard[] = "BRASIL EXPRESS";
    $portestandard[] = "ARGENTINA EXPRESS";
    $portestandard[] = "PERU EXPRESS";
    $portestandard[] = "COLOMBIA EXPRESS";
    $portestandard[] = "BOLIVIA EXPRESS";
    $portestandard[] = "VENEZUELA EXPRESS";
    $portestandard[] = "CHILE EXPRESS";
    $portestandard[] = "PARAGUAY EXPRESS";
    $portestandard[] = "ECUADOR EXPRESS";
    $portestandard[] = "URUGUAY EXPRESS";
    $portestandard[] = "MEXICO EXPRESS";
    $portestandard[] = "ESTADOS UNIDOS EXPRESS";
    $portestandard[] = "CANADA EXPRESS";
    $portestandard[] = "REP. DOMINICANA EXPRESS";
    $portestandard[] = "CUBA EXPRESS";
    $portestandard[] = "PUERTO RICO EXPRESS";
    $portestandard[] = "JAMAICA EXPRESS";
    $portestandard[] = "CORCEGA STANDARD";
    $portestandard[] = "IRLANDA DEL NORTE STANDARD";
    $portestandard[] = "HONG KONG";
    $portestandard[] = "INDONESIA EXPRESS";
    $portestandard[] = "JAPON EXPRESS";
    $portestandard[] = "SINGAPUR EXPRESS";
    $portestandard[] = "TAIWAN EXPRESS";
    $portestandard[] = "ARABIA SAUDI EXPRESS";
    $portestandard[] = "ARGELIA EXPRESS";
    $portestandard[] = "EGIPTO EXPRESS";
    $portestandard[] = "EMIRATOS ARABES UNIDOS EXPRESS";
    $portestandard[] = "CAMERUN EXPRESS";
    $portestandard[] = "COSTA DE MARFIL EXPRESS";
    $portestandard[] = "KUWAIT EXPRESS";
    $portestandard[] = "MARRUECOS EXPRESS";
    $portestandard[] = "QATAR EXPRESS";
    $portestandard[] = "SURAFRICA EXPRESS";
    $portestandard[] = "TUNEZ EXPRESS";
    $portestandard[] = "ANGOLA EXPRESS";
    $portestandard[] = "AUSTRALIA EXPRESS";
    $portestandard[] = "CHINA EXPRESS";
    $portestandard[] = "FILIPINAS EXPRESS";
    $portestandard[] = "GUINEA EXPRESS";
    $portestandard[] = "INDIA EXPRESS";
    $portestandard[] = "ISRAEL EXPRESS";
    $portestandard[] = "JORDANIA EXPRESS";
    $portestandard[] = "KENIA EXPRESS";
    $portestandard[] = "LIBANO EXPRESS";
    $portestandard[] = "MADAGASCAR EXPRESS";
    $portestandard[] = "MAURITANIA EXPRESS";
    $portestandard[] = "NUEVA ZELANDA EXPRESS";
    $portestandard[] = "SENEGAL EXPRESS";
    $portestandard[] = "UGANDA EXPRESS";
    $portestandard[] = "VIETNAM EXPRESS";
    $portestandard[] = "ZAMBIA EXPRESS";
    $portestandard[] = "ZIMBAWE EXPRESS";


    return in_array($porte, $portestandard);
}