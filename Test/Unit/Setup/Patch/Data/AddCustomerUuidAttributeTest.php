<?php

declare(strict_types=1);

namespace Magematch\CustomerUuid\Test\Unit\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magematch\CustomerUuid\Model\UuidGenerator;
use Magematch\CustomerUuid\Setup\Patch\Data\AddCustomerUuidAttribute;

class AddCustomerUuidAttributeTest extends TestCase
{
    private ModuleDataSetupInterface&MockObject $moduleDataSetup;
    private EavSetupFactory&MockObject $eavSetupFactory;
    private EavConfig&MockObject $eavConfig;
    private UuidGenerator&MockObject $uuidGenerator;
    private AddCustomerUuidAttribute $patch;

    protected function setUp(): void
    {
        $this->moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);
        $this->eavSetupFactory = $this->createMock(EavSetupFactory::class);
        $this->eavConfig = $this->createMock(EavConfig::class);
        $this->uuidGenerator = $this->createMock(UuidGenerator::class);

        $this->patch = new AddCustomerUuidAttribute(
            $this->moduleDataSetup,
            $this->eavSetupFactory,
            $this->eavConfig,
            $this->uuidGenerator,
        );
    }

    public function testApplyCreatesAttributeAndBackfillsCustomers(): void
    {
        $this->moduleDataSetup->expects($this->once())->method('startSetup');
        $this->moduleDataSetup->expects($this->once())->method('endSetup');

        $eavSetup = $this->createMock(EavSetup::class);
        $this->eavSetupFactory->method('create')->willReturn($eavSetup);

        $eavSetup->expects($this->once())
            ->method('addAttribute')
            ->with(Customer::ENTITY, 'uuid', $this->arrayHasKey('type'));

        $attribute = $this->createMock(Attribute::class);
        $attribute->method('getAttributeId')->willReturn('999');
        $this->eavConfig->method('getAttribute')->willReturn($attribute);

        $connection = $this->createMock(AdapterInterface::class);
        $this->moduleDataSetup->method('getConnection')->willReturn($connection);
        $this->moduleDataSetup->method('getTable')->willReturnCallback(
            static fn(string $table): string => $table
        );

        $select = $this->createMock(Select::class);
        $connection->method('select')->willReturn($select);
        $select->method('from')->willReturnSelf();
        $select->method('joinLeft')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('order')->willReturnSelf();
        $select->method('limit')->willReturnSelf();

        $connection->expects($this->exactly(2))
            ->method('fetchCol')
            ->with($select)
            ->willReturnOnConsecutiveCalls([10, 11], []);

        $this->uuidGenerator->expects($this->exactly(2))
            ->method('generateV4')
            ->willReturnOnConsecutiveCalls(
                '11111111-2222-4333-8444-555555555555',
                '66666666-7777-4888-9999-aaaaaaaaaaaa'
            );

        $connection->expects($this->once())
            ->method('insertMultiple')
            ->with(
                'customer_entity_varchar',
                [
                    [
                        'attribute_id' => 999,
                        'entity_id' => 10,
                        'value' => '11111111-2222-4333-8444-555555555555',
                    ],
                    [
                        'attribute_id' => 999,
                        'entity_id' => 11,
                        'value' => '66666666-7777-4888-9999-aaaaaaaaaaaa',
                    ],
                ]
            );

        $this->patch->apply();
    }

    public function testApplySkipsCustomersWithExistingUuid(): void
    {
        $this->moduleDataSetup->expects($this->once())->method('startSetup');
        $this->moduleDataSetup->expects($this->once())->method('endSetup');

        $eavSetup = $this->createMock(EavSetup::class);
        $this->eavSetupFactory->method('create')->willReturn($eavSetup);

        $attribute = $this->createMock(Attribute::class);
        $attribute->method('getAttributeId')->willReturn('999');
        $this->eavConfig->method('getAttribute')->willReturn($attribute);

        $connection = $this->createMock(AdapterInterface::class);
        $this->moduleDataSetup->method('getConnection')->willReturn($connection);
        $this->moduleDataSetup->method('getTable')->willReturnCallback(
            static fn(string $table): string => $table
        );

        $select = $this->createMock(Select::class);
        $connection->method('select')->willReturn($select);
        $select->method('from')->willReturnSelf();
        $select->method('joinLeft')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('order')->willReturnSelf();
        $select->method('limit')->willReturnSelf();

        $connection->expects($this->once())
            ->method('fetchCol')
            ->with($select)
            ->willReturn([]);

        $this->uuidGenerator->expects($this->never())->method('generateV4');
        $connection->expects($this->never())->method('insertMultiple');

        $this->patch->apply();
    }

    public function testGetDependenciesReturnsEmpty(): void
    {
        $this->assertSame([], AddCustomerUuidAttribute::getDependencies());
    }

    public function testGetAliasesReturnsEmpty(): void
    {
        $this->assertSame([], $this->patch->getAliases());
    }
}
