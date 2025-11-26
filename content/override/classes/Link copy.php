<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

/**
 * Class ImageManagerCore.
 *
 * This class includes functions for image manipulation
 *
 * @since 1.5.0
 */
class ImageManager extends ImageManagerCore
{
    /**
     * @param string $filename
     *
     * @return string|bool
     */
    public static function getMimeType(string $filename)
    {
        if (!file_exists($filename)) {
            return false;
        }
        $mimeType = false;
        // Try with GD
        if (function_exists('getimagesize')) {
            $imageInfo = @getimagesize($filename);
            if ($imageInfo) {
                $mimeType = $imageInfo['mime'];
            }
        }
        // Try with FileInfo
        if (!$mimeType && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filename);
            finfo_close($finfo);
        }
        // Try with Mime
        if (!$mimeType && function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filename);
        }
        // Try with exec command and file binary
        if (!$mimeType && function_exists('exec')) {
            $mimeType = trim(exec('file -b --mime-type ' . escapeshellarg($filename)));
            if (!$mimeType) {
                $mimeType = trim(exec('file --mime ' . escapeshellarg($filename)));
            }
            if (!$mimeType) {
                $mimeType = trim(exec('file -bi ' . escapeshellarg($filename)));
            }
        }

        return $mimeType;
    }

}