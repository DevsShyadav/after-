PLACE YOUR PLUGIN ZIP FILES HERE
================================

Put exactly these two files in this folder:

1. after-purchase-goldmine-pro.zip
   The full Pro plugin. Delivered automatically after a successful payment.

2. after-purchase-goldmine-trial.zip
   The 24-hour trial build. Delivered when someone clicks "Start free trial".

HOW TO BUILD THE TWO ZIPS
-------------------------
- PRO build:  zip the `after-purchase-goldmine` plugin folder as-is.
- TRIAL build: copy the same `after-purchase-goldmine` folder, add an EMPTY
  file named `trial.flag` inside it, then zip it. The presence of `trial.flag`
  turns the plugin into a self-deleting 24-hour trial automatically.

  (Optional) Edit APG_UPGRADE_URL in after-purchase-goldmine.php to point the
  trial's "Upgrade to Pro" banner at your landing page.

The file names must match the `pro_zip_name` / `trial_zip_name` values in
../api/config.php (defaults already match the names above).

These ZIPs are streamed through download.php with a signed, expiring token,
so visitors never get a direct, shareable link to the files.
