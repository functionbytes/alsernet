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

namespace PrestaShop\PrestaShop\Adapter\Category\CommandHandler;

use Category;
use PrestaShop\PrestaShop\Adapter\Domain\AbstractObjectModelHandler;
use PrestaShop\PrestaShop\Core\Domain\Category\Command\EditCategoryCommand;
use PrestaShop\PrestaShop\Core\Domain\Category\CommandHandler\EditCategoryHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Category\Exception\CannotEditCategoryException;
use PrestaShop\PrestaShop\Core\Domain\Category\Exception\CannotEditRootCategoryException;
use PrestaShop\PrestaShop\Core\Domain\Category\Exception\CategoryNotFoundException;

/**
 * Class EditCategoryHandler.
 *
 * @internal
 */
final class EditCategoryHandler extends AbstractObjectModelHandler implements EditCategoryHandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws CategoryNotFoundException
     * @throws CannotEditRootCategoryException
     */
    public function handle(EditCategoryCommand $command)
    {
        $category = new Category($command->getCategoryId()->getValue());

        if (! $category->id) {
            throw new CategoryNotFoundException($command->getCategoryId(), sprintf('Category with id "%s" cannot be found.', $command->getCategoryId()->getValue()));
        }

        if ($category->isRootCategory()) {
            throw new CannotEditRootCategoryException;
        }

        $this->updateCategoryFromCommandData($category, $command);
    }

    /**
     * Updates legacy object model with data from command
     *
     *
     * @throws CannotEditCategoryException
     */
    private function updateCategoryFromCommandData(Category $category, EditCategoryCommand $command)
    {
        if ($command->isActive() !== null) {
            $category->active = $command->isActive();
        }

        if ($command->getParentCategoryId() !== null) {
            $category->id_parent = $command->getParentCategoryId();
        }

        if ($command->getLocalizedNames() !== null) {
            $category->name = $command->getLocalizedNames();
        }

        if ($command->getLocalizedLinkRewrites() !== null) {
            $category->link_rewrite = $command->getLocalizedLinkRewrites();
        }

        if ($command->getLocalizedDescriptions() !== null) {
            $category->description = $command->getLocalizedDescriptions();
        }

        if ($command->getLocalizedMetaTitles() !== null) {
            $category->meta_title = $command->getLocalizedMetaTitles();
        }

        if ($command->getLocalizedMetaDescriptions() !== null) {
            $category->meta_description = $command->getLocalizedMetaDescriptions();
        }

        if ($command->getLocalizedMetaKeywords() !== null) {
            $category->meta_keywords = $command->getLocalizedMetaKeywords();
        }

        if ($command->getAssociatedGroupIds() !== null) {
            $category->groupBox = $command->getAssociatedGroupIds();
        }

        if ($category->validateFields(false) === false) {
            throw new CannotEditCategoryException('Invalid data when updating category');
        }

        if ($category->validateFieldsLang(false) === false) {
            throw new CannotEditCategoryException('Invalid data when updating category');
        }

        if ($category->update() === false) {
            throw new CannotEditCategoryException(sprintf('Failed to edit Category with id "%s".', $category->id));
        }

        if ($command->getAssociatedShopIds()) {
            $this->associateWithShops($category, $command->getAssociatedShopIds());
        }
    }
}
