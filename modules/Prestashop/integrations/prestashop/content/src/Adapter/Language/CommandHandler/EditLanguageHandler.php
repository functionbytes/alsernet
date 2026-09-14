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

namespace PrestaShop\PrestaShop\Adapter\Language\CommandHandler;

use Configuration;
use Db;
use Language;
use PrestaShop\PrestaShop\Adapter\Image\ImageValidator;
use PrestaShop\PrestaShop\Core\Domain\Language\Command\EditLanguageCommand;
use PrestaShop\PrestaShop\Core\Domain\Language\CommandHandler\EditLanguageHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Language\Exception\CannotDisableDefaultLanguageException;
use PrestaShop\PrestaShop\Core\Domain\Language\Exception\LanguageConstraintException;
use PrestaShop\PrestaShop\Core\Domain\Language\Exception\LanguageException;
use PrestaShop\PrestaShop\Core\Domain\Language\ValueObject\IsoCode;

/**
 * Handles command which edits language using legacy object model
 *
 * @internal
 */
final class EditLanguageHandler extends AbstractLanguageHandler implements EditLanguageHandlerInterface
{
    /**
     * @var ImageValidator
     */
    private $imageValidator;

    public function __construct(ImageValidator $imageValidator)
    {
        $this->imageValidator = $imageValidator;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(EditLanguageCommand $command)
    {
        if ($command->getNoPictureImagePath()) {
            $this->imageValidator->assertFileUploadLimits($command->getNoPictureImagePath());
            $this->imageValidator->assertIsValidImageType($command->getNoPictureImagePath());
        }

        if ($command->getFlagImagePath()) {
            $this->imageValidator->assertFileUploadLimits($command->getFlagImagePath());
            $this->imageValidator->assertIsValidImageType($command->getFlagImagePath());
        }

        $language = $this->getLegacyLanguageObject($command->getLanguageId());

        $this->assertLanguageWithIsoCodeDoesNotExist($language, $command);
        $this->assertDefaultLanguageIsNotDisabled($command);

        $this->updateEmployeeLanguage($command);
        $this->moveTranslationsIfIsoChanged($language, $command);

        $this->updateLanguageWithCommandData($language, $command);
        $this->updateShopAssociationIfChanged($language, $command);
        $this->copyNoPictureIfChanged($language, $command);
        $this->uploadFlagImageIfChanged($language, $command);
    }

    /**
     * Update legacy language only with data that is set
     */
    private function updateLanguageWithCommandData(Language $language, EditLanguageCommand $command)
    {
        if ($command->getName() !== null) {
            $language->name = $command->getName();
        }

        if ($command->getIsoCode() !== null) {
            $language->iso_code = $command->getIsoCode()->getValue();
            if (false !== ($languageDetails = Language::getLangDetails($command->getIsoCode()->getValue()))) {
                $language->locale = $languageDetails['locale'];
            }
        }

        if ($command->getTagIETF() !== null) {
            $language->language_code = $command->getTagIETF()->getValue();
        }

        if ($command->getShortDateFormat() !== null) {
            $language->date_format_lite = $command->getShortDateFormat();
        }

        if ($command->getFullDateFormat() !== null) {
            $language->date_format_full = $command->getFullDateFormat();
        }

        if ($command->isRtl() !== null) {
            $language->is_rtl = $command->isRtl();
        }

        if ($command->isActive() !== null) {
            $language->active = $command->isActive();
        }

        if ($language->validateFields(false) === false) {
            throw new LanguageException('Cannot add language with invalid data');
        }

        if ($language->update() === false) {
            throw new LanguageException(sprintf('Cannot update language with id "%s"', $language->id));
        }
    }

    /**
     * Only copy new "No picture" if it's being updated
     */
    private function copyNoPictureIfChanged(Language $language, EditLanguageCommand $command)
    {
        if ($command->getNoPictureImagePath() === null) {
            return;
        }

        $isoCode = $command->getIsoCode();

        if (! $isoCode instanceof IsoCode) {
            $isoCode = new IsoCode($language->iso_code);
        }

        $this->copyNoPictureImage(
            $isoCode,
            $command->getNoPictureImagePath()
        );
    }

    /**
     * Default language cannot be disabled
     */
    private function assertDefaultLanguageIsNotDisabled(EditLanguageCommand $command)
    {
        if ($command->isActive() === false
            && $command->getLanguageId()->getValue() === (int) Configuration::get('PS_LANG_DEFAULT')
        ) {
            throw new CannotDisableDefaultLanguageException(sprintf('Language with id "%s" is default language and thus it cannot be disabled', $command->getLanguageId()->getValue()));
        }
    }

    /**
     * If language that is being updated is disabled
     * and there are employees that use this language
     * then their language has to be updated to default
     */
    private function updateEmployeeLanguage(EditLanguageCommand $command)
    {
        if ($command->isActive() === false) {
            Db::getInstance()->execute(
                'UPDATE `'._DB_PREFIX_.'employee`
                 SET `id_lang`='.(int) Configuration::get('PS_LANG_DEFAULT').'
                 WHERE `id_lang`='.(int) $command->getLanguageId()->getValue()
            );
        }
    }

    /**
     * Move translation files if language's ISO code has changed
     */
    private function moveTranslationsIfIsoChanged(Language $language, EditLanguageCommand $command)
    {
        if ($command->getIsoCode() !== null
            && $language->iso_code !== $command->getIsoCode()->getValue()
        ) {
            $language->moveToIso($command->getLanguageId()->getValue());
        }
    }

    private function updateShopAssociationIfChanged(Language $language, EditLanguageCommand $command)
    {
        if ($command->getShopAssociation() === null) {
            return;
        }

        $this->associateWithShops(
            $language,
            $command->getShopAssociation()
        );
    }

    /**
     * Update language's flag image if it has changed
     */
    private function uploadFlagImageIfChanged(Language $language, EditLanguageCommand $command)
    {
        if ($command->getFlagImagePath() === null) {
            return;
        }

        $language->deleteImage();

        $this->uploadImage(
            $command->getLanguageId()->getValue(),
            $command->getFlagImagePath(),
            'l'.DIRECTORY_SEPARATOR
        );
    }

    /**
     * Assert that language with updated ISO code does not exist
     */
    private function assertLanguageWithIsoCodeDoesNotExist(Language $language, EditLanguageCommand $command)
    {
        if ($command->getIsoCode() !== null) {
            return;
        }

        /* @phpstan-ignore-next-line */
        if ($language->iso_code === $command->getIsoCode()->getValue() && Language::getIdByIso($command->getIsoCode()->getValue())
        ) {
            /* @phpstan-ignore-next-line */
            throw new LanguageConstraintException(sprintf('Language with ISO code "%s" already exists', $command->getIsoCode()->getValue()), LanguageConstraintException::INVALID_ISO_CODE);
        }
    }
}
