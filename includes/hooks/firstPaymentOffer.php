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

        '2' => [
            'monthly'   => '2.50',
            'annually'  => '25.00',
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

                # Update our total adjustment $amount variable to that of the product's price minus the new offer price listed in the $productOverrides array
                $amount += ($productPrice - $productOverrides[$productID][$billingCycle]);

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