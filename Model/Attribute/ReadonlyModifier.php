<?php

declare(strict_types=1);

namespace Magematch\CustomerUuid\Model\Attribute;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Makes the `uuid` field read-only in the customer admin edit form
 * by disabling and setting it as non-editable via UI component meta.
 */
class ReadonlyModifier implements ModifierInterface
{
    public function modifyData(array $data): array
    {
        return $data;
    }

    public function modifyMeta(array $meta): array
    {
        $meta['customer']['children']['uuid']['arguments']['data']['config'] = [
            'disabled' => true,
            'notice'   => (string)__('UUID is system-assigned and cannot be modified.'),
        ];

        return $meta;
    }
}
