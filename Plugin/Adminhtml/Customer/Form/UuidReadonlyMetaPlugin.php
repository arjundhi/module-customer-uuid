<?php

declare(strict_types=1);

namespace Magematch\CustomerUuid\Plugin\Adminhtml\Customer\Form;

class UuidReadonlyMetaPlugin
{
    public function afterGetMeta(
        \Magento\Customer\Model\Customer\DataProviderWithDefaultAddresses $subject,
        array $meta
    ): array {
        if (!isset($meta['customer']['children']['uuid']['arguments']['data']['config'])) {
            return $meta;
        }

        $meta['customer']['children']['uuid']['arguments']['data']['config']['disabled'] = true;
        $meta['customer']['children']['uuid']['arguments']['data']['config']['visible'] = true;
        $meta['customer']['children']['uuid']['arguments']['data']['config']['notice'] =
            (string) __('UUID is system-assigned and cannot be modified.');

        return $meta;
    }
}
