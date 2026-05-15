<?php

declare(strict_types=1);

namespace Quarry\CustomerUuid\Setup;

use Magento\Customer\Model\Customer;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class UninstallData implements UninstallInterface
{
    public function __construct(
        private readonly EavSetupFactory $eavSetupFactory
    ) {}

    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->removeAttribute(Customer::ENTITY, 'uuid');

        $setup->endSetup();
    }
}
