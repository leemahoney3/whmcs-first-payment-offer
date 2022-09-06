# WHMCS First Payment Offer

Gives you the ability to offer a discount on the first payment term of a new product. 

This hook will amend the cart totals to show the offer price without actually touching the
base price of the product.

This allows you to run a promotion on the first month/year/etc.. for certain products while 
leaving their base price intact.

Simply add your product ID's to the ```$productOverrides``` array below and define the billing cycles
for each ID that you wish to add the new offer price to.

Format goes like:

```
$productOverrides = [
     'PRODUCT ID' => [
         'BILLING CYCLE'         => 'NEW PRICE',
         'ANOTHER BILLING CYCLE' => 'ANOTHER NEW PRICE',
     ]
];
```

You can amend the description shown on the invoice by changing the ```$description``` variable below

If you charge tax, ensure to keep the ```$taxed``` variable set to **true**, otherwise the original base price will be taxed.

If you do not charge tax, set ```$taxed``` to **false**.

## WARNING: If you offer promotional codes, this currently only works with percentage and fixed value promotional codes, using others will likely result in a negative cart total and cause other issues.



## How to install

1. Copy the ```includes``` folder to your root WHMCS directory.
2. Amend the variables as mentioned above to suit your products.

## Contributions

Feel free to fork the repo, make changes, then create a pull request! For ideas on what you can help with, check the project issues.