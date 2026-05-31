<?php
/**
 * After-Purchase Goldmine — landing backend configuration.
 *
 * EDIT THE VALUES BELOW. This is the only file you need to change.
 *
 * 1. Paste your Razorpay Key ID + Key Secret (https://dashboard.razorpay.com).
 * 2. Upload your two plugin ZIPs into the /files folder (see files/README.txt).
 * 3. Set the price (in the smallest currency unit — paise for INR).
 *
 * @package APG_Landing
 */

return array(

	// ---- Razorpay credentials -------------------------------------------
	// Use rzp_test_xxx keys while testing, rzp_live_xxx when you go live.
	'razorpay_key_id'     => 'rzp_test_XXXXXXXXXXXXXX',
	'razorpay_key_secret' => 'YOUR_RAZORPAY_KEY_SECRET',

	// ---- Pricing ---------------------------------------------------------
	// Amount is in the smallest unit: paise for INR (₹2499 => 249900).
	'currency'            => 'INR',
	'price'               => 249900,
	'product_name'        => 'After-Purchase Goldmine (Pro)',

	// ---- Plugin files ----------------------------------------------------
	// Place these ZIPs in the /files directory next to this api folder.
	'pro_zip'             => __DIR__ . '/../files/after-purchase-goldmine-pro.zip',
	'pro_zip_name'        => 'after-purchase-goldmine-pro.zip',
	'trial_zip'           => __DIR__ . '/../files/after-purchase-goldmine-trial.zip',
	'trial_zip_name'      => 'after-purchase-goldmine-trial.zip',

	// ---- Download token security ----------------------------------------
	// Any long random string. Used to sign one-time download links.
	'download_secret'     => 'change-this-to-a-long-random-string-please',
	// How long a download link stays valid, in seconds (default 24h).
	'download_ttl'        => 86400,

	// ---- Contact / notifications ----------------------------------------
	'support_email'       => 'itsdevsarun@gmail.com',
	'whatsapp'            => '917654758443',
	// Where to email new leads/sales notifications (leave blank to disable).
	'notify_email'        => 'itsdevsarun@gmail.com',
	// Send the customer their download link by email after purchase.
	'email_customer'      => true,
);
