<?php

use WHMCS\Database\Capsule;

/**
 * WHMCS First Payment Offer
 *
 * Gives you the ability to offer a discount on the first payment term of a new product. 
 * 
 * This hook will amend the cart totals to show the offer price without actually touching the
 * base price of the product.
 * 
 * This allows you to run a promotion on the first month/year/etc.. for certain products while 
 * leaving their base price intact.
 *
 * Simply add your product ID's to the $productOverrides array below and define the billing cycles
 * for each ID that you wish to add the new offer price to.
 * 
 * Format goes like:
 * 
 * $productOverrides = [
 *      'PRODUCT ID' => [
 *          'BILLING CYCLE'         => 'NEW PRICE',
 *          'ANOTHER BILLING CYCLE' => 'ANOTHER NEW PRICE',
 *      ]
 * ];
 * 
 * You can amend the description shown on the invoice by changing the $description variable below
 * 
 * If you charge tax, ensure to keep the $taxed variable set to true, otherwise the original base price will be taxed.
 * If you do not charge tax, set $taxed to false.
 * 
 * WARNING: If you offer promotional codes, this currently only works with percentage and fixed value promotional codes, using others will likely
 *          result in a negative cart total and cause other issues.
 * 
 * @package    WHMCS
 * @author     Lee Mahoney <lee@leemahoney.dev>
 * @copyright  Copyright (c) Lee Mahoney 2022
 * @license    MIT License
 * @version    1.0.0
 * @link       https://leemahoney.dev
 */



if (!defined('WHMCS')) {
    die('You cannot access this file directly.');
}


function first_payment_offer($vars) {

    # define the product's ID and then the billing cycle you wish for the discounts to be applied to (can apply to multiple billing cycles). 
    # The price here is the new offer price of the product, it will only apply to the checkout and won't recur.
    $productOverrides = [

        '7' => [
            'monthly'   => '3.50',
            'annually'  => '25.00',
        ],
        '2' => [
            'monthly' => '3.50',
            'annually' => '25.00'
        ]

    ];

    # Description that shows on the invoice
    $description = "New hosting account promotion";
    
    # Whether or not to tax the adjustment (if you charge tax, enable this otherwise the tax amount will be of the original price and incorrect)
    $taxed  = true;

    /* ------------------------------------------------- */

    # Initialize some variables
    $amount             = 0;
    $cartAdjustments    = [];

    # Loop through the products in the cart
    foreach ($vars['products'] as $product) {

        # Pull out the product ID and billing cycle for the current product
        $productID      = $product['pid'];
        $billingCycle   = $product['billingcycle'];
        
        # Check if the product ID is in our $productOverrides array
        if (array_key_exists($productID, $productOverrides)) {

            # If it is, check that the current billing cycle on the product (that the client chose) is present in the $productOverrides for this product ID
            if (array_key_exists($billingCycle, $productOverrides[$productID])) {

                # Grab the current price of the product based on the $billingCycle variable
                $productPrice = Capsule::table('tblpricing')->where('type', 'product')->where('relid', $productID)->pluck($billingCycle)->first();

                $newPrice = $productOverrides[$productID][$billingCycle];

                # Check for promo code (could make the total negative..)
                if ($_SESSION['cart']['promo']) {

                    # Grab the promo code details
                    $promoDetails = Capsule::table('tblpromotions')->where('code', $_SESSION['cart']['promo'])->first();

                    # Retrieve the applicable cycles from the database and make them an array
                    $applicablePromoCycles = array_map('strtolower', explode(',', $promoDetails->cycles));
                    
                    # Retrieve the applicable products from the database and make them an array
                    $applicablePromoProducts = explode(',', $promoDetails->appliesto);

                    # Check if the billing cycle of the product is in the $applicablePromoCycles (or if the promo code has none = all cycles), and check the product ID is in the $applicablePromoCycles array
                    if ((in_array($billingCycle, $applicablePromoCycles) || $promoDetails->cycles == "") && in_array($productID, $applicablePromoProducts)) {
                        
                        # If the promo code is a percentage
                        if ($promoDetails->type == "Percentage") {

                            # Check if the current product price percentage value is greater than the new price percentage value
                            # If it is, then set the new price (not going to explain this, would take 2 paragraphs)
                            if (($productPrice / 100) * $promoDetails->value > ($newPrice / 100) * $promoDetails->value) {
                                $newPrice = (($newPrice / 100) * $promoDetails->value + ($productPrice / 100) * $promoDetails->value - $newPrice);
                                $newPrice = ($productPrice / 100) * $promoDetails->value - $newPrice;
                                $override = true;
                            }
                            
                        # If the promo code is a fixed value
                        } else if ($promoDetails->type == "Fixed Amount") {

                            # Check that the new price minux the fixed value is less than zero, if so then set the new price to the fixed value (will make the final total 0)
                            if ($newPrice - $promoDetails->value < 0) {
                                $newPrice = $promoDetails->value;
                            }

                        }

                    }

                }
                
                # If the percentage override is true, handle the final amount differently.
                if ($override) {
                    $amount += $newPrice;
                } else {
                    $amount += ($productPrice - $newPrice);
                }
                
            }

        }

    }

    # Only update the $cartAdjustments array if the cart total has changed
    if ($amount != 0) {

        $cartAdjustments = [
            "description"   => $description,
            "amount"        => -$amount,
            "taxed"         => $taxed,
        ];

    }

    return $cartAdjustments; 

}

add_hook('CartTotalAdjustment', 1, 'first_payment_offer');