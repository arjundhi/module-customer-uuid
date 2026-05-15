# Quarry_CustomerUuid

This module adds a `uuid` field to every customer and shows it in admin and GraphQL.

## Compatibility

| | Version |
|---|---|
| PHP | 8.1 – 8.5 |
| Magento / Adobe Commerce | 2.4.6 – 2.4.9 |

## Install

### Composer (VCS / GitHub)

Add the repo to your `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
    "url": "https://github.com/arjundhi/magento2-customer-uuid.git"
    }
]
```

Install and run setup:

```bash
composer require quarry/module-customer-uuid
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

Reindex the customer grid so the UUID column appears:

```bash
bin/magento indexer:reindex customer_grid
```

### Manual

```bash
cp -r . app/code/Quarry/CustomerUuid
bin/magento module:enable Quarry_CustomerUuid
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
bin/magento indexer:reindex customer_grid
```

## What it does

- Adds a `uuid` attribute to customers.
- Fills UUID for existing customers during `setup:upgrade`.
- Generates UUID for new customers automatically.
- Shows UUID in **Customers → All Customers** grid.
- Makes UUID read-only in admin edit form.
- Exposes UUID in GraphQL for logged-in customers.

## GraphQL example

```graphql
query {
  customer {
    uuid
  }
}
```

## Tests

```bash
vendor/bin/phpunit app/code/Quarry/CustomerUuid/Test/Unit
```

## Uninstall

```bash
bin/magento module:uninstall Quarry_CustomerUuid --remove-data
```

## Backward compatibility

- Supports Magento 2.4.6 – 2.4.9 with PHP 8.1+.
- Uses core Magento APIs only, no new platform dependencies.
