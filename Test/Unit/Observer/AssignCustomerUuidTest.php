<?php

declare(strict_types=1);

namespace Magematch\CustomerUuid\Test\Unit\Observer;

use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magematch\CustomerUuid\Model\UuidGenerator;
use Magematch\CustomerUuid\Observer\AssignCustomerUuid;

class AssignCustomerUuidTest extends TestCase
{
    private AssignCustomerUuid $observer;
    private UuidGenerator&MockObject $uuidGenerator;

    protected function setUp(): void
    {
        $this->uuidGenerator = $this->createMock(UuidGenerator::class);
        $this->observer = new AssignCustomerUuid($this->uuidGenerator);
    }

    private function buildObserver(Customer&MockObject $customer): Observer
    {
        return new Observer(['customer' => $customer]);
    }

    public function testUuidIsAssignedToNewCustomer(): void
    {
        $this->uuidGenerator->expects($this->once())
            ->method('generateV4')
            ->willReturn('11111111-2222-4333-8444-555555555555');

        $customer = $this->createMock(Customer::class);
        $customer->method('getData')->with('uuid')->willReturn(null);

        $customer->expects($this->once())
            ->method('setData')
            ->with('uuid', '11111111-2222-4333-8444-555555555555');

        $this->observer->execute($this->buildObserver($customer));
    }

    public function testUuidIsNotOverwrittenWhenAlreadySet(): void
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('getData')->with('uuid')->willReturn('existing-uuid-value');

        $this->uuidGenerator->expects($this->never())->method('generateV4');
        $customer->expects($this->never())->method('setData');

        $this->observer->execute($this->buildObserver($customer));
    }

    public function testGeneratedUuidIsVersion4(): void
    {
        $this->uuidGenerator->expects($this->exactly(100))
            ->method('generateV4')
            ->willReturnCallback(
                static function (): string {
                    $data = random_bytes(16);
                    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
                    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

                    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
                }
            );

        $uuids = [];
        for ($i = 0; $i < 100; $i++) {
            $capturedUuid = null;

            $customer = $this->createMock(Customer::class);
            $customer->method('getData')->with('uuid')->willReturn(null);
            $customer->method('setData')->willReturnCallback(
                function (string $key, string $value) use (&$capturedUuid) {
                    if ($key === 'uuid') {
                        $capturedUuid = $value;
                    }
                }
            );

            $this->observer->execute($this->buildObserver($customer));

            $this->assertNotNull($capturedUuid);
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                (string) $capturedUuid
            );
            $uuids[] = $capturedUuid;
        }

        $this->assertCount(100, array_unique($uuids), 'UUIDs should be unique across generations.');
    }
}
