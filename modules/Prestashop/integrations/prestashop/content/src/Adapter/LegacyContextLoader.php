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

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter;

use Context;
use Currency;
use DummyAdminController;
use Employee;
use PrestaShop\PrestaShop\Core\Domain\Currency\ValueObject\Precision;
use Shop;

/**
 * Helps loading specific context, for example in CLI context
 */
class LegacyContextLoader
{
    /**
     * @var Context
     */
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function loadGenericContext(
        ?string $controllerClassName = null,
        ?int $currencyId = null,
        ?int $employeeId = null,
        ?int $shopId = null,
        ?int $shopGroupId = null
    ): self {
        $this->loadCurrencyContext($currencyId);
        $this->loadControllerContext($controllerClassName);
        $this->loadEmployeeContext($employeeId);

        if ($shopId !== null) {
            $this->loadShopContext($shopId);
        }
        if ($shopGroupId !== null) {
            $this->loadShopGroupId($shopGroupId);
        }

        return $this;
    }

    public function loadControllerContext(?string $controllerClassName = null): self
    {
        if ($controllerClassName === null) {
            $this->context->controller = new DummyAdminController;

            return $this;
        }

        if (! class_exists($controllerClassName)) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot load controller context for classname %s',
                    $controllerClassName
                )
            );
        }

        $this->context->controller = new $controllerClassName;

        return $this;
    }

    public function loadCurrencyContext(?int $currencyId = null): self
    {
        $currency = new Currency($currencyId);
        if ($currencyId === null) {
            $currency->precision = Precision::DEFAULT_PRECISION;
        }

        $this->context->currency = $currency;

        return $this;
    }

    public function loadEmployeeContext(?int $employeeId = null): self
    {
        $this->context->employee = new Employee($employeeId);

        return $this;
    }

    public function loadShopContext(int $shopId = 1): self
    {
        $this->context->shop = new Shop($shopId);
        Shop::setContext(Shop::CONTEXT_SHOP, $shopId);

        return $this;
    }

    public function loadShopGroupId(int $shopGroupId): self
    {
        Shop::setContext(Shop::CONTEXT_GROUP, $shopGroupId);

        return $this;
    }
}
