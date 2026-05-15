<?php

declare(strict_types=1);

namespace Quarry\CustomerUuid\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Quarry\CustomerUuid\Model\UuidGenerator;

class AssignCustomerUuid implements ObserverInterface
{
    public function __construct(
        private readonly UuidGenerator $uuidGenerator
    ) {}

    public function execute(Observer $observer): void
    {
        /** @var \Magento\Customer\Model\Customer|null $customer */
        $customer = $observer->getData('customer');
        if ($customer === null && $observer->getEvent() !== null) {
            $customer = $observer->getEvent()->getCustomer();
        }
        if ($customer === null) {
            return;
        }

        if (!empty($customer->getData('uuid'))) {
            return;
        }

        $customer->setData('uuid', $this->uuidGenerator->generateV4());
    }
}
