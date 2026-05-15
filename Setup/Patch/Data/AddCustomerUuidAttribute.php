<?php

declare(strict_types=1);

namespace Quarry\CustomerUuid\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Quarry\CustomerUuid\Model\UuidGenerator;

class AddCustomerUuidAttribute implements DataPatchInterface
{
    private const BACKFILL_BATCH_SIZE = 1000;

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly EavConfig $eavConfig,
        private readonly UuidGenerator $uuidGenerator,
    ) {}

    public function apply(): void
    {
        $this->moduleDataSetup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            Customer::ENTITY,
            'uuid',
            [
                'type'         => 'varchar',
                'label'        => 'UUID',
                'input'        => 'text',
                'required'     => false,
                'visible'      => false,
                'user_defined' => false,
                'system'       => true,
                'length'       => 36,
                'unique'       => true,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true,
                'position'     => 999,
            ]
        );

        $this->backfillExistingCustomers();

        $this->moduleDataSetup->endSetup();
    }

    private function backfillExistingCustomers(): void
    {
        $attributeId = (int) $this->eavConfig->getAttribute(Customer::ENTITY, 'uuid')->getAttributeId();
        if ($attributeId <= 0) {
            return;
        }

        $connection = $this->moduleDataSetup->getConnection();
        $customerEntityTable = $this->moduleDataSetup->getTable('customer_entity');
        $customerEntityVarcharTable = $this->moduleDataSetup->getTable('customer_entity_varchar');

        $lastEntityId = 0;

        while (true) {
            $select = $connection->select()
                ->from(['ce' => $customerEntityTable], ['entity_id'])
                ->joinLeft(
                    ['cev' => $customerEntityVarcharTable],
                    sprintf(
                        'cev.entity_id = ce.entity_id AND cev.attribute_id = %d',
                        $attributeId
                    ),
                    []
                )
                ->where('cev.value_id IS NULL')
                ->where('ce.entity_id > ?', $lastEntityId)
                ->order('ce.entity_id ASC')
                ->limit(self::BACKFILL_BATCH_SIZE);

            $entityIds = array_map('intval', $connection->fetchCol($select));
            if ($entityIds === []) {
                break;
            }

            $rows = [];
            foreach ($entityIds as $entityId) {
                $rows[] = [
                    'attribute_id' => $attributeId,
                    'entity_id' => $entityId,
                    'value' => $this->uuidGenerator->generateV4(),
                ];
            }

            $connection->insertMultiple($customerEntityVarcharTable, $rows);
            $lastEntityId = (int) end($entityIds);
        }
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }
}
