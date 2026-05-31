<?php
/**
 * Referral program module.
 *
 * @package APG
 */

namespace APG\Modules\Referral;

use APG\Modules\AbstractModule;
use APG\Modules\DiscountTimer\CouponGenerator;
use APG\Support\Helpers;
use APG\Support\Logger;
use APG\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the referral card and powers the reward lifecycle.
 */
final class ReferralModule extends AbstractModule {

	/**
	 * WC session key for an applied friend coupon.
	 *
	 * @var string
	 */
	const SESSION_FRIEND_COUPON = 'apg_friend_coupon';

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'referral';
	}

	/**
	 * {@inheritDoc}
	 */
	public function title() {
		return __( 'Referral Program', 'after-purchase-goldmine' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Reward customers for sharing. Friends get a discount, the referrer earns store credit.', 'after-purchase-goldmine' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'init', array( $this, 'capture_referral' ), 5 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'attach_code_to_order' ), 10, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'attach_code_to_order_object' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'process_referral' ), 10, 1 );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'apply_friend_coupon' ), 10 );
	}

	/**
	 * Persist the referral code from the cookie onto a new order (classic checkout).
	 *
	 * @param int $order_id Order id.
	 * @return void
	 */
	public function attach_code_to_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order instanceof \WC_Order ) {
			$this->attach_code_to_order_object( $order );
		}
	}

	/**
	 * Persist the referral code from the cookie onto a new order object.
	 *
	 * @param \WC_Order $order Order object.
	 * @return void
	 */
	public function attach_code_to_order_object( $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$cookie = Helpers::referral_cookie_name();

		if ( empty( $_COOKIE[ $cookie ] ) ) {
			return;
		}

		$code = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie ] ) );

		if ( '' !== $code && ! $order->get_meta( '_apg_referral_code' ) ) {
			$order->update_meta_data( '_apg_referral_code', $code );
			$order->save();
		}
	}

	/**
	 * Capture an inbound referral code into a cookie.
	 *
	 * @return void
	 */
	public function capture_referral() {
		if ( is_admin() ) {
			return;
		}

		$cookie = Helpers::referral_cookie_name();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ $cookie ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$code = sanitize_text_field( wp_unslash( $_GET[ $cookie ] ) );

		if ( '' === $code ) {
			return;
		}

		$days = (int) Options::get( 'referral', 'cookie_days', 30 );

		if ( ! headers_sent() ) {
			setcookie(
				$cookie,
				$code,
				array(
					'expires'  => time() + ( $days * DAY_IN_SECONDS ),
					'path'     => COOKIEPATH ? COOKIEPATH : '/',
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}

		// Make it available immediately within this request.
		$_COOKIE[ $cookie ] = $code;
	}

	/**
	 * Auto-apply a friend discount coupon at checkout when referred.
	 *
	 * @return void
	 */
	public function apply_friend_coupon() {
		if ( ! $this->is_enabled() || ! function_exists( 'WC' ) ) {
			return;
		}

		$wc = WC();

		if ( ! $wc || ! $wc->cart || ! $wc->session ) {
			return;
		}

		$cookie = Helpers::referral_cookie_name();
		$code   = isset( $_COOKIE[ $cookie ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie ] ) ) : '';

		if ( '' === $code ) {
			return;
		}

		$amount = (float) Options::get( 'referral', 'friend_discount_amount', 0 );

		if ( $amount <= 0 ) {
			return;
		}

		$friend_coupon = $wc->session->get( self::SESSION_FRIEND_COUPON );

		// Create the friend coupon once per session.
		if ( ! $friend_coupon ) {
			$friend_coupon = CouponGenerator::create_reward(
				array(
					'type'   => Options::get( 'referral', 'friend_discount_type', 'fixed' ),
					'amount' => $amount,
					'days'   => (int) Options::get( 'referral', 'cookie_days', 30 ),
					'source' => 'referral_friend',
					'prefix' => 'WELCOME',
				)
			);

			if ( ! $friend_coupon ) {
				return;
			}

			$wc->session->set( self::SESSION_FRIEND_COUPON, $friend_coupon );
		}

		if ( $friend_coupon && ! $wc->cart->has_discount( $friend_coupon ) ) {
			$wc->cart->apply_coupon( $friend_coupon );
		}
	}

	/**
	 * On a completed order, reward the referrer if applicable.
	 *
	 * @param int $order_id Completed order id.
	 * @return void
	 */
	public function process_referral( $order_id ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( ReferralRepository::order_has_conversion( $order_id ) ) {
			return;
		}

		$cookie = Helpers::referral_cookie_name();
		$code   = $order->get_meta( '_apg_referral_code' );

		if ( ! $code && isset( $_COOKIE[ $cookie ] ) ) {
			$code = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie ] ) );
		}

		if ( ! $code ) {
			return;
		}

		$referrer_email = ReferralRepository::referrer_email_by_code( $code );
		$buyer_email    = $order->get_billing_email();

		// Must have a referrer, and self-referrals are not rewarded.
		if ( ! $referrer_email || strtolower( $referrer_email ) === strtolower( $buyer_email ) ) {
			return;
		}

		$reward_coupon = CouponGenerator::create_reward(
			array(
				'type'     => Options::get( 'referral', 'reward_type', 'fixed' ),
				'amount'   => (float) Options::get( 'referral', 'reward_amount', 0 ),
				'days'     => (int) Options::get( 'referral', 'cookie_days', 30 ),
				'email'    => $referrer_email,
				'source'   => 'referral',
				'order_id' => $order_id,
				'prefix'   => 'THANKYOU',
			)
		);

		ReferralRepository::record_conversion(
			array(
				'referrer_email'    => $referrer_email,
				'referral_code'     => $code,
				'referred_order_id' => $order_id,
				'referred_email'    => $buyer_email,
				'reward_coupon'     => (string) $reward_coupon,
				'order_total'       => (float) $order->get_total(),
			)
		);

		if ( $reward_coupon ) {
			$this->email_referrer( $referrer_email, $reward_coupon );
		}

		\APG\Analytics\EventRepository::record(
			'referral',
			'conversion',
			array(
				'order_id' => $order_id,
				'revenue'  => 0,
			)
		);
	}

	/**
	 * Email the referrer their reward coupon.
	 *
	 * @param string $email  Referrer email.
	 * @param string $coupon Coupon code.
	 * @return void
	 */
	private function email_referrer( $email, $coupon ) {
		$type   = Options::get( 'referral', 'reward_type', 'fixed' );
		$amount = (float) Options::get( 'referral', 'reward_amount', 0 );
		$label  = 'percent' === $type
			? sprintf( '%s%%', wc_format_localized_decimal( $amount ) )
			: wp_strip_all_tags( wc_price( $amount ) );

		$subject = sprintf(
			/* translators: %s: site name. */
			__( 'You earned a reward at %s!', 'after-purchase-goldmine' ),
			get_bloginfo( 'name' )
		);

		$message  = '<p>' . esc_html__( 'Great news — someone you referred just made a purchase. Here is your reward:', 'after-purchase-goldmine' ) . '</p>';
		$message .= '<p style="font-size:22px;font-weight:700;">' . esc_html( $coupon ) . ' — ' . esc_html( $label ) . ' ' . esc_html__( 'off your next order', 'after-purchase-goldmine' ) . '</p>';
		$message .= '<p><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Shop now', 'after-purchase-goldmine' ) . '</a></p>';

		$mailer  = WC()->mailer();
		$wrapped = $mailer->wrap_message( $subject, $message );

		$mailer->send( $email, $subject, $wrapped, "Content-Type: text/html\r\n" );

		Logger::debug( 'Referral reward emailed', array( 'email' => $email, 'coupon' => $coupon ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $order ) {
		$email = $order->get_billing_email();

		if ( ! $email ) {
			return '';
		}

		$code = Helpers::referral_code( $email, $order->get_id() );

		// Persist the ambassador mapping so conversions can resolve the email.
		ReferralRepository::register_ambassador( $email, $code );

		$share_url = add_query_arg( Helpers::referral_cookie_name(), rawurlencode( $code ), home_url( '/' ) );

		$reward_type   = Options::get( 'referral', 'reward_type', 'fixed' );
		$reward_amount = (float) Options::get( 'referral', 'reward_amount', 0 );
		$reward_label  = 'percent' === $reward_type
			? sprintf( '%s%%', wc_format_localized_decimal( $reward_amount ) )
			: wp_strip_all_tags( wc_price( $reward_amount ) );

		return Helpers::get_template(
			'thankyou/referral.php',
			array(
				'order'        => $order,
				'code'         => $code,
				'share_url'    => $share_url,
				'headline'     => Helpers::merge_tags( Options::get( 'referral', 'headline' ) ),
				'description'  => Helpers::merge_tags( Options::get( 'referral', 'description' ) ),
				'reward_label' => $reward_label,
			)
		);
	}
}
