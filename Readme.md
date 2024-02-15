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

To export your order :
```
php Thelia module:GuaranteedOpinion:SendOrder
```

No need to import your site opinion, you only need to use the widget and widget iframe

## Loop

[google_reviews_loop]

### Input arguments

| Argument     | Description                    |
|--------------|--------------------------------|
| **min_rate** | minimum score allowed. (min 0) |
| **product**  | id of your product             |

### Output arguments

| Variable           | Description                  |
|--------------------|------------------------------|
| $ID                | id of your review            |
| $PRODUCT_REVIEW_ID | guaranteed opinion review id |
| $NAME              | name of the reviewer         |
| $RATE              | score                        |
| $REVIEW            | review message               |
| $REVIEW_DATE       | date of review               |
| $PRODUCT_ID        | id of the product            |
| $ORDER_ID          | order related to the review  |
| $ORDER_DATE        | date of the order            |
| $REPLY             | reply of the review          |
| $REPLY_DATE        | reply date                   |

## Documentations

Societe-des-avis-garantis API documentation is available at https://www.societe-des-avis-garantis.fr/configuration

API PUBLIC OPINIONS : https://www.societe-des-avis-garantis.fr/configuration/api-publique
API PRIVATE ORDERS : https://www.societe-des-avis-garantis.fr/configuration/api-orders