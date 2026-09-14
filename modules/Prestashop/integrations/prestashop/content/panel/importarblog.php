<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function getfieldvalue($dbh, $sql)
{
    $rows = $dbh->query($sql);
    foreach ($rows as $row) {
        return $row[0];
    }
}

function getdatarows($dbh, $sql)
{
    return $dbh->query($sql);
}

function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}

function getCategoriasHijas($padre)
{

    $json = peticionget('http://blog.a-alvarez.com/wp-json/wp/v2/categories?parent='.$padre.'&orderby=id');
    $array = json_decode($json, true);

    return $array;

}

function creartag($idpost, $tagtext)
{
    Db::getInstance()->Execute('INSERT INTO aalv_ybc_blog_tag(id_post, id_lang, tag, click_number) VALUES ('.$idpost.",1,'".$tagtext."',0)");
}

function crearpostitem($dbh, $categoria, $postitem, $idpadre)
{

    if ($postitem['status'] == 'publish') {

        // SELECT modelos_relacionados FROM blog_modelos_relacionados where wp_id=11835;
        $modelos = ''.getfieldvalue($dbh, 'SELECT modelos_relacionados FROM blog_modelos_relacionados where wp_id='.$postitem['id']);

        if ($modelos == '') {
            $products = '';
        } else {
            $products = str_replace(',', '-', ''.Db::getInstance()->getValue('SELECT group_concat(id_product) FROM aalv_product_import WHERE id_modelo in ('.$modelos.')'));
        }

        $idpost = ''.Db::getInstance()->getValue('select id_post from aalv_blog_post_import where id_post_source='.$postitem['id']);

        if ($idpost == '') {

            if (Db::getInstance()->Execute('INSERT INTO aalv_ybc_blog_post(id_category_default, is_featured, inventaries, added_by, is_customer, modified_by, enabled, datetime_added, datetime_modified, datetime_active, sort_order, click_number, likes) VALUES ('.$categoria.",0,'".$products."',1,0,1,1,'".str_replace('T', ' ', $postitem['date'])."','".str_replace('T', ' ', $postitem['modified'])."',null,0,0,0)")) {

                $idpost = Db::getInstance()->getValue('select max(id_post) from aalv_ybc_blog_post');

                Db::getInstance()->Execute('INSERT INTO aalv_blog_post_import(id_post, id_post_source) VALUES ('.$idpost.','.$postitem['id'].')');

                Db::getInstance()->Execute('INSERT INTO aalv_ybc_blog_post_category(id_post, id_category, position) VALUES ('.$idpost.','.$categoria.',0)');
                Db::getInstance()->Execute('INSERT INTO aalv_ybc_blog_post_category(id_post, id_category, position) VALUES ('.$idpost.','.$idpadre.',0)');

                Db::getInstance()->Execute('INSERT INTO aalv_ybc_blog_post_shop(id_post, id_shop) VALUES ('.$idpost.',1)');

                $media = $postitem['featured_media'];
                $json = peticionget('http://blog.a-alvarez.com/wp-json/wp/v2/media/'.$media);
                $array = json_decode($json, true);

                $imageurl = $array['source_url'];

                $file = file_get_contents($imageurl);
                file_put_contents(_PS_ADMIN_DIR_.'/../img/ybc_blog/post/'.basename($imageurl), $file);
                file_put_contents(_PS_ADMIN_DIR_.'/../img/ybc_blog/post/thumb/'.basename($imageurl), $file);

                $imagesave = basename($imageurl);

                $metatitle = '';

                /* La importancia  de un buen grip · Blog de golf · Álvarez
                https://www.a-alvarez.com/blog/golf                   Blog de Golf - Alvarez Deportes
                https://www.a-alvarez.com/blog/caza                 Blog de Caza - Armería Alvarez
                https://www.a-alvarez.com/blog/pesca                 Blog de Pesca - Alvarez Deportes
                https://www.a-alvarez.com/blog/hipica               Blog de Hípica - Alvarez Deportes
                https://www.a-alvarez.com/blog/buceo              Blog de Buceo - Alvarez Deportes
                https://www.a-alvarez.com/blog/nautica            Blog de Náutica - Alvarez Deportes
                https://www.a-alvarez.com/blog/esqui                 Blog de Esquí - Alvarez Deportes
                https://www.a-alvarez.com/blog/padel                 Blog de Pádel - Alvarez Deportes
                https://www.a-alvarez.com/blog/aventura        Blog de Aventura - Alvarez Deportes

                */

                if ($idpadre == 1) {
                    $metatitle = $postitem['title']['rendered'].' · Blog de golf · Álvarez';
                }
                if ($idpadre == 3) {
                    $metatitle = $postitem['title']['rendered'].' · Blog de caza · Álvarez';
                }
                if ($idpadre == 4) {
                    $metatitle = $postitem['title']['rendered'].' · Blog de pesca · Álvarez';
                }
                if ($idpadre == 5) {
                    $metatitle = $postitem['title']['rendered'].' · Blog de hipica · Álvarez';
                }
                if ($idpadre == 6) {
                    $metatitle = $postitem['title']['rendered'].' · Blog de buceo · Álvarez';
                }
                if ($idpadre == 7) {
                    $metatitle = $postitem['title']['rendered'].' · Blog de nautica · Álvarez';
                }
                if ($idpadre == 8) {
                    $metatitle = $postitem['title']['rendered'].' · Blog de padel · Álvarez';
                }
                if ($idpadre == 9) {
                    $metatitle = $postitem['title']['rendered'].' · Blog de esqui · Álvarez';
                }
                if ($idpadre == 10) {
                    $metatitle = $postitem['title']['rendered'].' · Blog de aventura · Álvarez';
                }

                Db::getInstance()->Execute('INSERT INTO aalv_ybc_blog_post_lang(id_post, id_lang, title, url_alias, meta_title, description, short_description, meta_keywords, meta_description, thumb, image) VALUES ('.$idpost.",1,'".$postitem['title']['rendered']."','".$postitem['slug']."','".$metatitle."','".str_replace("'", '"', $postitem['content']['rendered'])."','".str_replace("'", '"', $postitem['excerpt']['rendered'])."','','','".$imagesave."','".$imagesave."')");

                $tags = $postitem['tags'];

                foreach ($tags as $tag) {
                    $json = peticionget('http://blog.a-alvarez.com/wp-json/wp/v2/tags/'.$tag);
                    $array = json_decode($json, true);
                    $tagtext = $array['name'];
                    creartag($idpost, $tagtext);
                }

            }

        } else {

            $metatitle = '';

            if ($idpadre == 1) {
                $metatitle = $postitem['title']['rendered'].' · Blog de golf · Álvarez';
            }
            if ($idpadre == 3) {
                $metatitle = $postitem['title']['rendered'].' · Blog de caza · Álvarez';
            }
            if ($idpadre == 4) {
                $metatitle = $postitem['title']['rendered'].' · Blog de pesca · Álvarez';
            }
            if ($idpadre == 5) {
                $metatitle = $postitem['title']['rendered'].' · Blog de hipica · Álvarez';
            }
            if ($idpadre == 6) {
                $metatitle = $postitem['title']['rendered'].' · Blog de buceo · Álvarez';
            }
            if ($idpadre == 7) {
                $metatitle = $postitem['title']['rendered'].' · Blog de nautica · Álvarez';
            }
            if ($idpadre == 8) {
                $metatitle = $postitem['title']['rendered'].' · Blog de padel · Álvarez';
            }
            if ($idpadre == 9) {
                $metatitle = $postitem['title']['rendered'].' · Blog de esqui · Álvarez';
            }
            if ($idpadre == 10) {
                $metatitle = $postitem['title']['rendered'].' · Blog de aventura · Álvarez';
            }

            Db::getInstance()->Execute('UPDATE aalv_ybc_blog_post SET id_category_default='.$categoria.",inventaries='".$products."',datetime_modified='".str_replace('T', ' ', $postitem['modified'])."' WHERE id_post=".$idpost);

            Db::getInstance()->Execute("UPDATE aalv_ybc_blog_post_lang SET title='".$postitem['title']['rendered']."',url_alias='".$postitem['slug']."',description='".str_replace("'", '"', $postitem['content']['rendered'])."',short_description='".str_replace("'", '"', $postitem['excerpt']['rendered'])."',meta_title='".$metatitle."' WHERE id_post=".$idpost);

        }

    }

}

function crearposts($dbh, $categoria, $hrefpost, $numpost, $idpadre)
{

    $numllamadas = ceil((int) $numpost / 10);
    for ($i = 1; $i <= $numllamadas; $i++) {

        $json = peticionget($hrefpost.'&order=asc&page='.$i);
        $posts = json_decode($json, true);
        foreach ($posts as $postitem) {
            crearpostitem($dbh, $categoria, $postitem, $idpadre);
        }
    }

}

function crearcategoria($dbh, $idpadre, $categoriablog)
{

    echo $categoriablog['count'].' '.$categoriablog['name'].' '.$categoriablog['slug'].' '.$categoriablog['_links']['wp:post_type'][0]['href'].'<br/>';

    $numpost = $categoriablog['count'];
    $hrefpost = $categoriablog['_links']['wp:post_type'][0]['href'];
    $name = $categoriablog['name'];
    $slug = $categoriablog['slug'];

    $categoria = ''.Db::getInstance()->getValue('select id_category from aalv_blog_category_import where id_category_source='.$categoriablog['id']);

    if ($categoria == '') {

        // INSERT INTO `aalv_ybc_blog_category`(`id_category`, `id_parent`, `added_by`, `modified_by`, `enabled`, `datetime_added`, `datetime_modified`, `sort_order`) VALUES ('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]')
        // INSERT INTO `aalv_ybc_blog_category_shop`(`id_category`, `id_shop`) VALUES ('[value-1]','[value-2]')
        // INSERT INTO `aalv_ybc_blog_category_lang`(`id_category`, `id_lang`, `meta_title`, `title`, `description`, `url_alias`, `meta_keywords`, `meta_description`, `image`, `thumb`) VALUES ('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]','[value-10]')

        if (Db::getInstance()->Execute('INSERT INTO aalv_ybc_blog_category(id_parent, added_by, modified_by, enabled, datetime_added, datetime_modified, sort_order) VALUES ('.$idpadre.',1,1,1,curdate(),curdate(),0)')) {

            $categoria = Db::getInstance()->getValue('select max(id_category) from aalv_ybc_blog_category where id_parent='.$idpadre);
            Db::getInstance()->Execute('INSERT INTO aalv_ybc_blog_category_shop(id_category, id_shop) VALUES ('.$categoria.',1)');
            Db::getInstance()->Execute('INSERT INTO aalv_ybc_blog_category_lang(id_category, id_lang, meta_title, title, description, url_alias, meta_keywords, meta_description, image, thumb) VALUES ('.$categoria.",1,'','".$name."','','".$slug."','','','','')");
            Db::getInstance()->Execute('INSERT INTO aalv_ybc_blog_category_lang(id_category, id_lang, meta_title, title, description, url_alias, meta_keywords, meta_description, image, thumb) VALUES ('.$categoria.",2,'','".$name."','','".$slug."','','','','')");
            Db::getInstance()->Execute('INSERT INTO aalv_ybc_blog_category_lang(id_category, id_lang, meta_title, title, description, url_alias, meta_keywords, meta_description, image, thumb) VALUES ('.$categoria.",3,'','".$name."','','".$slug."','','','','')");
            Db::getInstance()->Execute('INSERT INTO aalv_ybc_blog_category_lang(id_category, id_lang, meta_title, title, description, url_alias, meta_keywords, meta_description, image, thumb) VALUES ('.$categoria.",4,'','".$name."','','".$slug."','','','','')");
            Db::getInstance()->Execute('INSERT INTO aalv_ybc_blog_category_lang(id_category, id_lang, meta_title, title, description, url_alias, meta_keywords, meta_description, image, thumb) VALUES ('.$categoria.",5,'','".$name."','','".$slug."','','','','')");

            Db::getInstance()->Execute('INSERT INTO aalv_blog_category_import(id_category, id_category_source) VALUES ('.$categoria.','.$categoriablog['id'].')');

        }

    } else {

        Db::getInstance()->Execute("UPDATE aalv_ybc_blog_category_lang SET title='".$name."', url_alias='".$slug."' WHERE id_category=".$categoria);

    }
    crearposts($dbh, $categoria, $hrefpost, $numpost, $idpadre);

}

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

if (Tools::getValue('reset') == '1') {

    Db::getInstance()->Execute('delete from aalv_ybc_blog_category_shop where id_category>=11');
    Db::getInstance()->Execute('delete from aalv_ybc_blog_category where id_category>=11');
    Db::getInstance()->Execute('delete from aalv_ybc_blog_category_lang where id_category>=11');

    Db::getInstance()->Execute('truncate table aalv_ybc_blog_post');
    Db::getInstance()->Execute('truncate table aalv_ybc_blog_post_category');
    Db::getInstance()->Execute('truncate table aalv_ybc_blog_post_shop');
    Db::getInstance()->Execute('truncate table aalv_ybc_blog_post_lang');
    Db::getInstance()->Execute('truncate table aalv_ybc_blog_post_shop');
    Db::getInstance()->Execute('truncate table aalv_ybc_blog_tag');

    Db::getInstance()->Execute('truncate table aalv_blog_category_import');
    Db::getInstance()->Execute('truncate table aalv_blog_post_import');

    Db::getInstance()->Execute('INSERT INTO aalv_blog_category_import(id_category, id_category_source) VALUES (1,2)');
    Db::getInstance()->Execute('INSERT INTO aalv_blog_category_import(id_category, id_category_source) VALUES (3,3)');
    Db::getInstance()->Execute('INSERT INTO aalv_blog_category_import(id_category, id_category_source) VALUES (4,4)');
    Db::getInstance()->Execute('INSERT INTO aalv_blog_category_import(id_category, id_category_source) VALUES (5,5)');
    Db::getInstance()->Execute('INSERT INTO aalv_blog_category_import(id_category, id_category_source) VALUES (6,6)');
    Db::getInstance()->Execute('INSERT INTO aalv_blog_category_import(id_category, id_category_source) VALUES (7,7)');
    Db::getInstance()->Execute('INSERT INTO aalv_blog_category_import(id_category, id_category_source) VALUES (8,8)');
    Db::getInstance()->Execute('INSERT INTO aalv_blog_category_import(id_category, id_category_source) VALUES (9,9)');
    Db::getInstance()->Execute('INSERT INTO aalv_blog_category_import(id_category, id_category_source) VALUES (10,10)');

}

$idblogcat = Tools::getValue('id');

if ($idblogcat == 2) {

    $categoriasgolf = getCategoriasHijas(2);
    foreach ($categoriasgolf as $categoriablog) {
        crearcategoria($dbh, 1, $categoriablog);
    }

} else {
    $categoriasgolf = getCategoriasHijas($idblogcat);
    foreach ($categoriasgolf as $categoriablog) {
        crearcategoria($dbh, $idblogcat, $categoriablog);
    }

}

echo 'Proceso acabado';
