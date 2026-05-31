=== After-Purchase Goldmine — Monetize the Thank-You Page ===
Contributors: goldminelabs
Tags: woocommerce, upsell, thank you page, referral, coupon, reviews, conversion
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 6.0
WC tested up to: 9.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn the WooCommerce "Order Received" page into your most profitable page: one-click upsells, a referral program, review requests, social sharing, and a next-purchase discount timer — in a premium SaaS-grade UI.

== Description ==

The moment right after a customer buys is the single highest-trust, highest-conversion moment in the entire shopping journey — and 99% of stores waste it with "Thanks, here's your order number."

**After-Purchase Goldmine** transforms that dead-end thank-you page into a revenue and growth engine with five composable modules:

* **One-Click Upsell** — Offer a complementary product the customer can add with a single click. When their gateway supports off-session charging it is truly zero-click; otherwise they go to a fully pre-filled one-step payment page. No re-entering payment details.
* **Next-Purchase Discount Timer** — Automatically issue a unique, single-use, expiring coupon for the next order, complete with a live countdown to drive urgency. The coupon is also added to the order confirmation email.
* **Referral Program** — Give every buyer a personal share link. When a friend orders, the referrer is automatically rewarded with a store-credit coupon by email, and the friend gets a welcome discount.
* **Review Request** — Capture product reviews at peak satisfaction with a beautiful per-product prompt.
* **Social Share** — One-tap sharing to X, Facebook, WhatsApp, LinkedIn, email, or copy-link.

Everything is wrapped in a premium, Stripe/Linear-grade admin dashboard with analytics, charts, a drag-and-drop module manager, an offer builder, and a guided onboarding wizard. Light & dark themes, full responsiveness, and glassmorphism are built in.

= Key features =
* HPOS (High-Performance Order Storage) compatible
* Works with the block-based and classic checkout
* Light / dark / auto themes with a custom accent color
* Built-in analytics (impressions, conversions, conversion rate, revenue) with dependency-free charts
* Theme-overridable templates
* Privacy-friendly: no external services, no tracking pixels

== Installation ==

1. Upload the `after-purchase-goldmine` folder to `/wp-content/plugins/`, or install the ZIP via **Plugins → Add New → Upload Plugin**.
2. Ensure **WooCommerce** is installed and active.
3. Activate **After-Purchase Goldmine** through the **Plugins** menu.
4. You will be taken to the **Welcome** onboarding wizard automatically. Pick your modules, brand color and reward, then finish.
5. Visit **Goldmine → Upsell Offers** to create your first one-click offer.

== Configuration ==

* **Goldmine → Dashboard** — KPIs and trends for the last 30 days.
* **Goldmine → Modules** — Toggle modules on/off and drag to reorder how they appear on the thank-you page.
* **Goldmine → Upsell Offers** — Create offers: choose a product, set a discount, target by product/category/any order, add a headline, image and priority.
* **Goldmine → Referrals** — Review ambassadors, conversions and rewards.
* **Goldmine → Settings** — Appearance (theme, accent, glass, radius), and per-module copy and behavior. The **Advanced** tab controls whether data is deleted on uninstall.

== Frequently Asked Questions ==

= Is the upsell really one-click? =
If the customer paid with a saved payment method on a gateway that supports off-session charging (via the `apg_offsession_charge` filter integration), the purchase completes instantly with zero extra clicks. For every other gateway, the customer is sent to a pre-filled WooCommerce "Pay for order" page — a genuine one-step checkout that requires no re-entry of shipping or product details.

= Does it work with HPOS? =
Yes. The plugin declares compatibility with custom order tables and uses the WooCommerce CRUD APIs throughout.

= Can I override the templates? =
Yes. Copy any file from `after-purchase-goldmine/templates/` into `your-theme/after-purchase-goldmine/` and edit it there.

= Will it slow down my store? =
No. Assets load only on the thank-you page (frontend) and on plugin admin screens. Analytics writes are de-duplicated and the dashboard is cached.

== Testing Instructions ==

1. **Upsell:** Create an active offer under *Upsell Offers* (Trigger = "Any order"). Place a test order. On the thank-you page the upsell card appears; click the accept button and confirm a child order is created and you reach the pay/confirmation step.
2. **Discount Timer:** Place a test order and confirm a coupon code with a live countdown appears, and that the same code is included in the order confirmation email. Apply the code on a new order to verify it discounts and is single-use.
3. **Referral:** On the thank-you page copy the referral link. Open it in a private window, place an order with a different email, then mark that order **Completed**. Confirm a reward coupon email is sent to the referrer and a row appears under *Referrals*.
4. **Review:** Confirm each purchased product shows a "Review" button linking to its review tab.
5. **Social Share:** Confirm the configured network buttons open the correct share dialogs and that "Copy link" works.
6. **Admin:** Toggle and reorder modules, change the accent color and theme, and confirm the thank-you page reflects the changes.

== Troubleshooting ==

* **Cards don't appear on the thank-you page:** Ensure at least one module is enabled (Goldmine → Modules) and that you are viewing a real order-received page. For the upsell card specifically, an active offer with an in-stock, purchasable product must match the order.
* **Upsell sends to a pay page instead of charging instantly:** This is expected unless a gateway integration implements off-session charging via the `apg_offsession_charge` filter. The pay page is fully pre-filled and one step.
* **Coupon not in the email:** Enable "Include the coupon in the order confirmation email" under Settings → Discount Timer, and confirm your store sends WooCommerce customer emails.
* **Referral reward not issued:** Rewards are issued when the referred order reaches the **Completed** status, and self-referrals (same email) are intentionally ignored.
* **Styles look unstyled in admin:** Clear any caching/minification plugin so the plugin CSS/JS can load.

== Changelog ==

= 1.0.2 =
* New premium white + green theme across the admin and thank-you page.
* Settings: added a live appearance preview that updates instantly; changing the accent color now recolors the whole admin in real time.
* Fixed: the "Glassmorphism" and "Entrance animations" toggles now actually take effect on the thank-you page (previously cosmetic only).
* Hardened the accent/theme CSS injection so custom colors always apply reliably.
* Admin now uses a consistent white theme instead of following the OS dark mode.
* Existing installs on a previous default accent are migrated to green automatically.

= 1.0.1 =
* Premium UI theme refresh.
* Fixed: the Welcome/onboarding screen could show "Sorry, you are not allowed to access this page" — the hidden page is now kept accessible and hidden via CSS instead of remove_submenu_page().

= 1.0.0 =
* Initial release: one-click upsell, discount timer, referral program, review request, social share, analytics dashboard, onboarding wizard, HPOS support.

== Upgrade Notice ==

= 1.0.2 =
White + green premium UI, live settings preview, and working glass/animation toggles. Safe in-place upgrade.

= 1.0.0 =
First public release.
