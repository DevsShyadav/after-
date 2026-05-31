# After-Purchase Goldmine — Landing Page

A premium, conversion-focused landing page for the **After-Purchase Goldmine** WooCommerce plugin, with:

- 🟢 Silicon-Valley-grade UI (white + green), fully mobile responsive with a hamburger menu
- 💳 **Razorpay** checkout — you only paste your Key ID + Key Secret
- ⚡ **Automatic Pro download** the instant payment is verified (+ emailed copy)
- ⏳ **Free 24-hour trial** download (the trial plugin self-deletes after 24h)
- 💬 Floating **WhatsApp widget** (name, mobile, message → sent to your number)

Tech: **HTML + Tailwind CSS (CDN) + vanilla JavaScript** front-end, with a tiny
**PHP** backend for secure payment verification and gated downloads.

---

## Folder structure

```
landing/
├── index.html                 # The landing page (HTML + Tailwind)
├── assets/
│   ├── css/landing.css         # Custom component styles + animations
│   └── js/app.js               # Nav, reveal, FAQ, Razorpay, trial, WhatsApp
├── api/                        # PHP backend (needs PHP hosting)
│   ├── config.php              # ← EDIT THIS (keys, price, paths, emails)
│   ├── lib/bootstrap.php       # Shared helpers + minimal Razorpay client
│   ├── create-order.php        # Creates a Razorpay order
│   ├── verify-payment.php      # Verifies the signature → issues download
│   ├── download.php            # Streams the ZIP for a valid signed token
│   └── trial.php               # Captures a lead → trial download link
└── files/
    ├── README.txt              # Where to drop your two plugin ZIPs
    ├── after-purchase-goldmine-pro.zip     (you add this)
    └── after-purchase-goldmine-trial.zip   (you add this)
```

## Setup (5 minutes)

1. **Upload** the whole `landing/` folder to your PHP web host (cPanel, VPS, etc.).
2. **Add your plugin ZIPs** to `landing/files/` (see `files/README.txt`).
3. **Edit `api/config.php`:**
   - `razorpay_key_id` and `razorpay_key_secret` (from the Razorpay dashboard)
   - `price` (in paise — ₹2499 = `249900`)
   - `download_secret` (any long random string)
   - `support_email` / `notify_email`
4. Open the site. Click **Start free trial** or **Get Goldmine Pro** to test.

> Use Razorpay **test** keys (`rzp_test_…`) first. Test card: `4111 1111 1111 1111`,
> any future expiry, any CVV. Switch to live keys when ready.

## How the flows work

**Trial:** modal collects name/email/mobile → `trial.php` logs the lead and
returns a signed, 24-hour download link → the browser downloads the trial ZIP.

**Buy:** modal collects details → `create-order.php` creates a Razorpay order
(secret stays server-side) → Razorpay Checkout opens → on success the browser
sends the response to `verify-payment.php`, which validates the HMAC signature,
issues a signed download link, emails the buyer, and the Pro ZIP downloads
automatically.

**Security:** the key secret never reaches the browser; payments are verified
server-side; downloads are gated behind HMAC-signed, expiring tokens, so the ZIP
URLs are never directly shareable. Leads/sales are logged to `api/data/` which is
protected by an auto-generated `.htaccess`.

## Customising

- **Colors/copy:** edit `index.html` (Tailwind `brand` palette is in the inline
  config) and `assets/css/landing.css`.
- **Price display:** update the number in the pricing card and `config.php`.
- **WhatsApp number / support email:** set at the top of `assets/js/app.js` and
  in `api/config.php` (already pre-filled with your details).

## Requirements

- PHP 7.4+ with cURL enabled (for the Razorpay API).
- HTTPS strongly recommended (required for live Razorpay).
