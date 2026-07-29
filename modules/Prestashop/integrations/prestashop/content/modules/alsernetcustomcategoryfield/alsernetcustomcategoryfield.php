<?php


use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Alsernetcustomcategoryfield extends Module
{
    public function __construct()
    {
        $this->name = 'alsernetcustomcategoryfield';
        $this->version = '1.0.0';
        $this->author = 'Alsernet';
        $this->tab = 'administration';
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->need_instance = 0;
        parent::__construct();

        $this->displayName = $this->l('Alsernet Custom Category Field');
        $this->description = $this->l('Añade campos extras a las categorías.');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionCategoryFormBuilderModifier') &&
            $this->registerHook('actionAfterUpdateCategoryFormHandler') &&
            $this->registerHook('actionObjectCategoryUpdateAfter') &&
            $this->addFields();
    }


    public function addFields()
    {
        // Definir los nombres de las nuevas columnas
        $columns = [
            'tituloh1' => 'VARCHAR(255) NULL',
            'category_url_path' => 'VARCHAR(500) NULL',
            'add_sitemap' => 'VARCHAR(500) NULL',
            'prioridad' => 'VARCHAR(500) NULL'
        ];

        $tableName = _DB_PREFIX_ . 'category_lang';

        foreach ($columns as $columnName => $columnType) {
            // Verifica si la columna ya existe
            $sql = "SELECT COUNT(*) as count
                FROM information_schema.COLUMNS
                WHERE TABLE_NAME = '{$tableName}'
                AND COLUMN_NAME = '{$columnName}'
                AND TABLE_SCHEMA = DATABASE();";

            $result = Db::getInstance()->getRow($sql);

            if ($result['count'] == 0) {
                // Si la columna no existe, la añade
                $sql = "ALTER TABLE {$tableName} ADD COLUMN {$columnName} {$columnType};";
                if (!Db::getInstance()->execute($sql)) {
                    return false; // En caso de error en la consulta
                }
            }
        }
        $sql_update = "UPDATE {$tableName} SET link_rewrite = REPLACE(link_rewrite,'-','_');";
        $sql_update_add_sitemap = "UPDATE {$tableName} SET add_sitemap = '1';";
        $sql_update_prioridad = "UPDATE {$tableName} SET prioridad = '0.9';";

        Db::getInstance()->execute($sql_update);
        Db::getInstance()->execute($sql_update_add_sitemap);
        Db::getInstance()->execute($sql_update_prioridad);

        return true; // Todos los campos se han añadido o ya existen
    }


    public function hookActionCategoryFormBuilderModifier($params)
    {
        $formBuilder = $params['form_builder'];

        // Añadir el campo translatable para todos los idiomas usando el tipo adecuado
        $formBuilder->add('tituloh1', TranslatableType::class, [
            'label' => $this->l('H1'),
            'required' => false,
            'type' => TextType::class, // Cambia 'text' por TextType::class
        ])
            ->add('category_url_path', TranslatableType::class, [
                'label' => $this->l('Category Url Path'),
                'required' => false,
                'attr' => [
                    'readonly' => 'readonly', // Add readonly attribute
                ],
            ])
            ->add('add_sitemap', TranslatableType::class, [
                'label' => $this->l('Add Sitemap'),
                'required' => false,
                'type' => TextType::class
            ])
            ->add('prioridad', TranslatableType::class, [
                'label' => $this->l('Prioridad'),
                'required' => false,
                'type' => TextType::class,
            ]);

        // Obtener los datos de la categoría actual
        $categoryId = (int)Tools::getValue('id_category');
        if ($categoryId) {
            $category = new Category($categoryId);
            $params['data']['tituloh1'] = $category->tituloh1;
            $params['data']['category_url_path'] = $category->category_url_path;
            $params['data']['add_sitemap'] = $category->add_sitemap;
            $params['data']['prioridad'] = $category->prioridad;
            $formBuilder->setData($params['data']);
        }
    }

    public function hookActionObjectCategoryUpdateAfter($params)
    {
        $category = $params['object'];
        $tableName = _DB_PREFIX_ . 'category_lang';

        if ($category instanceof Category) {
            $categoryUrlPaths = $category->category_url_path;
            foreach ($categoryUrlPaths as $idLang => $categoryUrlPath) {
                $category_url_path = $this->getFullUrlPath($category, $idLang);
                Db::getInstance()->execute("UPDATE {$tableName} SET category_url_path = '{$category_url_path}' WHERE id_category = {$category->id} AND id_lang = {$idLang};");
            }
        }
    }

    public function hookActionAfterUpdateCategoryFormHandler($params)
    {


        $categoryId = (int)$params['id'];
        $category = new Category($categoryId);
        $updated = false;

        if (array_key_exists('form_data', $params)) {

            $addsitemaps = $params['form_data']['add_sitemap'];
            if ($categoryId && !empty($addsitemaps)) {
                foreach ($addsitemaps as $idLang => $addsitemap) {
                    if ($addsitemaps) {
                        $category->add_sitemap[$idLang] = $addsitemap; // Guardar el campo traducido para cada idioma
                        $updated = true;
                    }
                }
            }

            $prioridades = $params['form_data']['prioridad'];
            if ($categoryId && !empty($prioridades)) {
                foreach ($prioridades as $idLang => $prioridad) {
                    if ($prioridades) {
                        $category->prioridad[$idLang] = $prioridad; // Guardar el campo traducido para cada idioma
                        $updated = true;
                    }
                }
            }

            $linkRewriteValues = $params['form_data']['link_rewrite'];
            //URL's con el formato siempre _
            if ($categoryId && !empty($linkRewriteValues)) {
                foreach ($linkRewriteValues as $idLang => $linkRewriteValue) {
                    if ($linkRewriteValues) {
                        $category->link_rewrite[$idLang] = str_replace('-', '_', $linkRewriteValue); // Guardar el campo traducido para cada idioma
                        $updated = true;
                    }
                }
            }

            // Obtener los valores traducidos del campo
            $customFieldValues = $params['form_data']['tituloh1'];
            if ($categoryId && !empty($customFieldValues)) {
                foreach ($customFieldValues as $idLang => $customFieldValue) {
                    if ($customFieldValue) {
                        $category->tituloh1[$idLang] = $customFieldValue; // Guardar el campo traducido para cada idioma
                        $updated = true;
                    }
                }
            }

        }
        if ($updated) {
            $category->update();
        }


    }


    /**
     * @param Category $category
     * @param int $id_lang
     * @return void
     */
    public function getFullUrlPath(Category $category, int $idLang): string
    {
        $formatted_url = '';
        /**
         * @return array Corresponding categories
         */
        $categories = $category->getParentsCategories($idLang);

        foreach ($categories as $i => $category) {
            if ($category['is_root_category']) {
                break;
            } else if ($category['id_parent'] == 2) {
                $formatted_url = $category['link_rewrite'] . '/' . $formatted_url;
            } else if ($category['id_category'] == null) {
                continue;
            } else {
                if ($i == 0)
                    $formatted_url = $category['link_rewrite'];
                else
                    $formatted_url = $category['link_rewrite'] . '-' . $formatted_url;
            }
        }

        return rtrim($formatted_url, '/');

    }

}
