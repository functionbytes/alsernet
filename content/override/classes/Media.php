<?php
/**
 * Page Cache Ultimate, Page Cache standard and Speed pack are powered by Jpresta (jpresta . com)
 *
 *    @author    Jpresta
 *    @copyright Jpresta
 *    @license   See the license of this module in file LICENSE.txt, thank you.
 */
if (!defined('_PS_VERSION_')) {exit;}
class Media extends MediaCore
{
    /*
    * module: pagecache
    * date: 2024-05-21 08:49:56
    * version: 9.3.2
    */
    public static function clearCache()
    {
        if (Module::isEnabled('pagecache') && file_exists(_PS_MODULE_DIR_ . 'pagecache/pagecache.php')) {
            foreach (array(_PS_THEME_DIR_ . 'cache', _PS_THEME_DIR_ . 'assets/cache') as $dir) {
                if (file_exists($dir) && count(array_diff(scandir($dir), array('..', '.', 'index.php'))) > 0) {
                    PageCache::clearCache('Media::clearCache');
                    break;
                }
            }
        }
        if (is_callable('parent::clearCache')) {
            parent::clearCache();
        }
    }
}
