# Guaranteed Opinion

This module allows you to import your opinion on your Thelia website and export your order using Avis-Garantis API

## Installation

### Composer

Add it in your main thelia composer.json file

```
composer require thelia/guaranteed-opinion-module:~1.0
```

## Usage

Configure the module backoffice with your api keys

Then, add a cron :

To import your product opinion :
```
php Thelia module:GuaranteedOpinion:GetProductReview
```

To import your site opinion :
```
php Thelia module:GuaranteedOpinion:GetProductReview
```
(Note: If you are using Avis Garantis widget and widget iframe, you don't need to import your site opinion)

To export your order :
```
php Thelia module:GuaranteedOpinion:SendOrder
```

Now you can add these routes to your opinion page or use the loop

- ```/site_reviews/offset/{offset}/limit/{limit}```
- ```/product_reviews/{id}/offset/{offset}/limit/{limit}```

## Loop

[guaranteed_site_loop]

### Input arguments

| Argument     | Description                       |
|--------------|-----------------------------------|
| **min_rate** | minimum score allowed. (min 0)    |
| **limit**    | limit for pagination. (default 5) |
| **page**     | page for pagination. (default 0)  |

### Output arguments

| Variable        | Description                       |
|-----------------|-----------------------------------|
| $ID             | id of your review                 |
| $SITE_REVIEW_ID | guaranteed opinion site review id |
| $NAME           | name of the reviewer              |
| $RATE           | score                             |
| $REVIEW         | review message                    |
| $REVIEW_DATE    | date of review                    |
| $ORDER_ID       | order related to the review       |
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

### Output arguments

| Variable           | Description                          |
|--------------------|--------------------------------------|
| $ID                | id of your review                    |
| $PRODUCT_REVIEW_ID | guaranteed opinion product review id |
| $NAME              | name of the reviewer                 |
| $RATE              | score                                |
| $REVIEW            | review message                       |
| $REVIEW_DATE       | date of review                       |
| $PRODUCT_ID        | id of the product                    |
| $ORDER_ID          | order related to the review          |
| $ORDER_DATE        | date of the order                    |
| $REPLY             | reply of the review                  |
| $REPLY_DATE        | reply date                           |

## Documentations

Societe-des-avis-garantis API documentation is available at https://www.societe-des-avis-garantis.fr/configuration

API PUBLIC OPINIONS : https://www.societe-des-avis-garantis.fr/configuration/api-publique
API PRIVATE ORDERS : https://www.societe-des-avis-garantis.fr/configuration/api-orders