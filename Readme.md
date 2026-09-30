# Guaranteed Opinion

This module allows you to import your opinion on your Thelia website and export your order using Avis-Garantis API

## Installation

### Composer

Add it in your main thelia composer.json file

```
composer require thelia/guaranteed-opinion-module:^2.1
```

## Changes in 2.1.0

The 1.1.x line (Thelia 2) handled one Guaranteed Reviews account per shop language; 2.0.0 had lost it. 2.1.0
brings it back to Thelia 3:

- reviews, store reviews and product ratings are stored per language (`locale` column, primary keys
  `(product_review_id, locale)`, `(site_review_id, locale)`, `(product_id, locale)`);
- the review key, the order key and the reviews page URL are configured per language (language switcher of the
  configuration page); the store rating is stored per language as well;
- the three commands take `--locale` and use the keys of that language. `SendOrder` only sends the queued orders
  placed in that language and only marks those as sent: the other languages wait for their own run;
- the product review purge only deletes the reviews of the language imported;
- the send queue never holds an order twice, and a status change of a queued order no longer stops the request;
- nothing is sent when the key of the language is empty, and the keys never appear in an error message;
- new read-only API resources (below), which replace the Smarty plugin, the front hooks (product tab, product
  reviews, store widget) with their Smarty templates, and the HTML rendering of the "next reviews" routes: TheliaSmarty
  is not part of Thelia 3, the theme reads the API resources instead. The hook and display settings of the
  configuration page are removed with them (and the Smarty back-office template); the widget and iframe codes stay
  and are returned by the store rating resource (`widget`, `widgetIframe`, the HTML saved on the configuration page);
- the loops no longer return `ID` and `ORDER_ID`: the surrogate `id` column and the `order_id` column of the 2.0.0
  review tables are dropped (the reference to the order of a review is lost, never the order itself, as 1.1.0 did);
- `Config/update/2.1.0.sql` brings a 2.0.0 or 1.1.x schema to the per-language one and may run again: each change is
  tested in `information_schema` first. Every review, rating and queued order is kept; rows without a language get
  the default language of the shop, as 1.1.0 did. `Config/TheliaMain.sql` creates the per-language schema for a new
  install and never drops a table; activating the module runs it, then the update script, so tables left by a 2.0.0
  module removed without being destroyed are brought up to date too.

### Updating

The update is not atomic: Thelia records the new version before it runs `Config/update/2.1.0.sql`, and every
`ALTER TABLE` of the script commits on its own. If it stops half way (lost connection, missing privilege), the module
shows as 2.1.0 with a partly converted schema. Fix the cause and run the script again by hand, as often as needed:
it only changes what is not converted yet and touches no data twice.

```
mysql -u <user> -p <database> < local/modules/GuaranteedOpinion/Config/update/2.1.0.sql
```

(`vendor/thelia/modules/GuaranteedOpinion/` when the module is installed by Composer.) Back up the four
`guaranteed_opinion_*` tables before updating.

## Usage

Configure the module in the back office with your API keys, for each language of the shop.

Then add the cron jobs, once per language:

```
php bin/console module:GuaranteedOpinion:GetProductReview --locale fr_FR
php bin/console module:GuaranteedOpinion:GetSiteReview --locale fr_FR
php bin/console module:GuaranteedOpinion:SendOrder --locale fr_FR
```

(If you are using the Guaranteed Reviews widget and widget iframe, you don't need to import your site reviews.)

## API

Read-only, public, in the language given by `locale` (by default the language of the visitor). Public fields only:
rate, display name, dates, text and reply, never the order nor the e-mail of the customer.

| Route | Content |
|-------|---------|
| `GET /api/front/guaranteed_opinion/product_reviews?productId={id}&locale={locale}` | reviews of a product, newest first, paginated (`page`, `itemsPerPage`, 10 by default, 50 at most) |
| `GET /api/front/guaranteed_opinion/product_ratings?productId[]={id}&productId[]={id}&locale={locale}` | rating (`total`, `average`) of up to 100 products in one query, products without a rating left out |
| `GET /api/front/guaranteed_opinion/site_reviews?locale={locale}` | store reviews, newest first, paginated |
| `GET /api/front/guaranteed_opinion/site_rating?locale={locale}` | store rating, URL of the page listing every review, and the widget and iframe codes (`widget`, `widgetIframe`, null when empty) |

From a Twig theme:

```twig
{% set ratings = resources('/api/front/guaranteed_opinion/product_ratings', {productId: productIds}) %}
```

The JSON routes of the "next reviews" buttons stay, in the language of the visitor:

- ```/guaranteed_opinion/site_reviews/offset/{offset}/limit/{limit}```
- ```/guaranteed_opinion/product_reviews/{id}/offset/{offset}/limit/{limit}```

## Tests

From the root of a Thelia 3 project where the module is installed, against a disposable database whose name ends
with `_test` (created by `php bin/test-prepare`):

```
DATABASE_HOST=… DATABASE_PORT=… DATABASE_NAME=guaranteedopinion_test DATABASE_USER=… DATABASE_PASSWORD=… \
  KERNEL_CLASS='App\Kernel' APP_ENV=test \
  vendor/bin/phpunit --bootstrap vendor/thelia/modules/GuaranteedOpinion/Tests/bootstrap.php vendor/thelia/modules/GuaranteedOpinion/Tests
```

`UpdateScriptTest` creates and drops a database of its own (`guaranteedopinion_update_test`), the database user must
be allowed to.

## Loop

[guaranteed_site_loop]

### Input arguments

| Argument     | Description                       |
|--------------|-----------------------------------|
| **min_rate** | minimum score allowed. (min 0)    |
| **limit**    | limit for pagination. (default 5) |
| **page**     | page for pagination. (default 0)  |
| **lang_id**  | language of the reviews (default: language of the visitor) |

### Output arguments

| Variable        | Description                       |
|-----------------|-----------------------------------|
| $SITE_REVIEW_ID | guaranteed opinion site review id |
| $LOCALE         | language of the review            |
| $NAME           | name of the reviewer              |
| $RATE           | score                             |
| $REVIEW         | review message                    |
| $REVIEW_DATE    | date of review                    |
| $ORDER_DATE     | date of the order                 |
| $REPLY          | reply of the review               |
| $REPLY_DATE     | reply date                        |

[guaranteed_product_loop]

### Input arguments

| Argument     | Description                             |
|--------------|-----------------------------------------|
| **min_rate** | minimum score allowed. (min 0)          |
| **product**  | id of your product                      |
| **limit**    | limit for pagination. (default 5)       |
| **page**     | offset/page for pagination. (default 0) |
| **lang_id**  | language of the reviews (default: language of the visitor) |

### Output arguments

| Variable           | Description                          |
|--------------------|--------------------------------------|
| $PRODUCT_REVIEW_ID | guaranteed opinion product review id |
| $LOCALE            | language of the review               |
| $NAME              | name of the reviewer                 |
| $RATE              | score                                |
| $REVIEW            | review message                       |
| $REVIEW_DATE       | date of review                       |
| $PRODUCT_ID        | id of the product                    |
| $ORDER_DATE        | date of the order                    |
| $REPLY             | reply of the review                  |
| $REPLY_DATE        | reply date                           |

## Documentations

Societe-des-avis-garantis API documentation is available at https://www.societe-des-avis-garantis.fr/configuration

API PUBLIC OPINIONS : https://www.societe-des-avis-garantis.fr/configuration/api-publique
API PRIVATE ORDERS : https://www.societe-des-avis-garantis.fr/configuration/api-orders