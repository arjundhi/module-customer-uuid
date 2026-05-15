<?php

declare(strict_types=1);

namespace Magematch\CustomerUuid\GraphQl\Resolver;

use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class CustomerUuid implements ResolverInterface
{
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): ?string
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer is not authorized.'));
        }

        if (!isset($value['model'])) {
            throw new LocalizedException(__('Missing customer model in resolver context.'));
        }

        /** @var Customer $customer */
        $customer = $value['model'];

        return $customer->getData('uuid') ?: null;
    }
}
