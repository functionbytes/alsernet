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

namespace PrestaShop\PrestaShop\Adapter\CMS\Page\CommandHandler;

use CMS;
use PrestaShop\PrestaShop\Core\Domain\CmsPage\Command\EditCmsPageCommand;
use PrestaShop\PrestaShop\Core\Domain\CmsPage\CommandHandler\EditCmsPageHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\CmsPage\Exception\CannotEditCmsPageException;
use PrestaShop\PrestaShop\Core\Domain\CmsPage\Exception\CmsPageException;
use PrestaShop\PrestaShop\Core\Domain\CmsPage\Exception\CmsPageNotFoundException;
use PrestaShop\PrestaShop\Core\Domain\CmsPageCategory\Exception\CmsPageCategoryException;
use PrestaShopException;

/**
 * Edits cms page
 */
final class EditCmsPageHandler extends AbstractCmsPageHandler implements EditCmsPageHandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws CmsPageException
     * @throws CmsPageCategoryException
     */
    public function handle(EditCmsPageCommand $command)
    {
        $cms = $this->createCmsFromCommand($command);

        try {
            if ($cms->validateFields(false) === false || $cms->validateFieldsLang(false) === false) {
                throw new CmsPageException('Cms page contains invalid field values');
            }
            if ($cms->update() === false) {
                throw new CannotEditCmsPageException(sprintf('Failed to update cms page with id %s', $command->getCmsPageId()->getValue()));
            }
            if ($command->getShopAssociation() !== null) {
                $this->associateWithShops($cms, $command->getShopAssociation());
            }
        } catch (PrestaShopException $e) {
            throw new CmsPageException(sprintf('An unexpected error occurred when editing cms page with id %s', $command->getCmsPageId()->getValue()), 0, $e);
        }
    }

    /**
     * @return CMS
     *
     * @throws CmsPageException
     * @throws CmsPageNotFoundException
     * @throws CmsPageCategoryException
     */
    private function createCmsFromCommand(EditCmsPageCommand $command)
    {
        $cms = $this->getCmsPageIfExistsById($command->getCmsPageId()->getValue());

        if ($command->getCmsPageCategoryId() !== null) {
            $this->assertCmsCategoryExists($command->getCmsPageCategoryId()->getValue());

            $cms->id_cms_category = $command->getCmsPageCategoryId()->getValue();
        }

        if ($command->getLocalizedTitle() !== null) {
            $cms->meta_title = $command->getLocalizedTitle();
        }

        if ($command->getLocalizedMetaTitle() !== null) {
            $cms->head_seo_title = $command->getLocalizedMetaTitle();
        }

        if ($command->getLocalizedMetaDescription() !== null) {
            $cms->meta_description = $command->getLocalizedMetaDescription();
        }

        if ($command->getLocalizedMetaKeyword() !== null) {
            $cms->meta_keywords = $command->getLocalizedMetaKeyword();
        }

        if ($command->getLocalizedFriendlyUrl() !== null) {
            $cms->link_rewrite = $command->getLocalizedFriendlyUrl();
        }

        if ($command->getLocalizedContent() !== null) {
            $cms->content = $command->getLocalizedContent();
        }

        if ($command->isIndexedForSearch() !== null) {
            $cms->indexation = $command->isIndexedForSearch();
        }

        if ($command->isDisplayed() !== null) {
            $cms->active = $command->isDisplayed();
        }

        return $cms;
    }
}
