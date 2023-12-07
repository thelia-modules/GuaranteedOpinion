# Guaranteed Opinion

This module allows you to import your opinion on your Thelia website and export your order using Avis-Garantis API

## Installation

### Composer

Add it in your main thelia composer.json file

```
composer require theloa/guaranteed-opinion-module:~1.0
```

## Usage

Configure the module backoffice with your api keys

Then, add a crong :

To import your product opinion :
```
php Thelia module:GuaranteedOpinion:GetProductReview
```

To export your order :
```
php Thelia module:GuaranteedOpinion:SendOrder
```

No need to import your site opinion, you only need to use the widget and widget iframe

## Hook

Configure your hook in the backOffice
