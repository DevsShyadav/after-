<?php
/**
 * Next-purchase discount timer module.
 *
 * @package APG
 */

namespace APG\Modules\DiscountTimer;

use APG\Modules\AbstractModule;
use APG\Support\Helpers;
use APG\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Issues a unique, time-limited coupon and renders a live countdown.
 */
final class DiscountTimerModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'discount_timer';
	}

	/**
	 * {@inheritDoc}
	 */
	public function title() {
		return __( 'Next-Purchase Discount Timer', 'after-purchase-goldmine' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Auto-issue a unique, expiring coupon for the next order with a live countdown to drive urgency.', 'after-purchase-goldmine' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2"/><path d="M5 3 2 6"/><path d="m22 6-3-3"/></svg>';
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'woocommerce_email_after_order_table', array( $this, 'inject_into_email' ), 20, 4 );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $order ) {
		$coupon = CouponGenerator::get_or_create_for_order( $order );

		if ( empty( $coupon ) ) {
			return '';
		}

		return Helpers::get_template(
			'thankyou/discount-timer.php',
			array(
				'order'       => $order,
				'coupon'      => $coupon,
				'amount_text' => $this->amount_text( $coupon ),
				'headline'    => Options::get( 'discount_timer', 'headline' ),
				'description' => Options::get( 'discount_timer', 'description' ),
			)
		);
	}

	/**
	 * Human-readable discount label, e.g. "15% off" or "$10 off".
	 *
	 * @param array $coupon Coupon data.
	 * @return string
	 */
	private function amount_text( array $coupon ) {
		if ( 'percent' === $coupon['type'] ) {
			return sprintf(
				/* translators: %s: percentage. */
				__( '%s%% OFF', 'after-purchase-goldmine' ),
				wc_format_localized_decimal( $coupon['amount'] )
			);
		}

		return sprintf(
			/* translators: %s: formatted price. */
			__( '%s OFF', 'after-purchase-goldmine' ),
			wp_strip_all_tags( wc_price( $coupon['amount'] ) )
		);
	}

	/**
	 * Append the coupon block to customer order emails.
	 *
	 * @param \WC_Order $order         Order.
	 * @param bool      $sent_to_admin Whether the email is for admin.
	 * @param bool      $plain_text    Whether the email is plain text.
	 * @param \WC_Email $email         Email object.
	 * @return void
	 */
	public function inject_into_email( $order, $sent_to_admin, $plain_text, $email = null ) {
		if ( $sent_to_admin || ! $this->is_enabled() ) {
			return;
		}

		if ( ! Options::get( 'discount_timer', 'email_coupon' ) ) {
			return;
		}

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$coupon = CouponGenerator::get_or_create_for_order( $order );

		if ( empty( $coupon ) ) {
			return;
		}

		$expires = $coupon['expires_ts'] ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $coupon['expires_ts'] ) : '';

		if ( $plain_text ) {
			echo "\n\n----------------------------------------\n";
			echo esc_html( Options::get( 'discount_timer', 'headline' ) ) . "\n";
			printf(
				/* translators: 1: discount label, 2: coupon code. */
				esc_html__( 'Use code %1$s for %2$s on your next order.', 'after-purchase-goldmine' ),
				esc_html( $coupon['code'] ),
				esc_html( $this->amount_text( $coupon ) )
			);
			if ( $expires ) {
				echo "\n" . esc_html( sprintf( /* translators: %s: date. */ __( 'Expires: %s', 'after-purchase-goldmine' ), $expires ) );
			}
			echo "\n----------------------------------------\n";
			return;
		}

		printf(
			'<div style="margin:24px 0;padding:24px;border-radius:16px;background:#0f172a;color:#fff;text-align:center;font-family:Arial,sans-serif;">
				<div style="font-size:13px;letter-spacing:.08em;text-transform:uppercase;opacity:.7;">%1$s</div>
				<div style="font-size:30px;font-weight:800;margin:8px 0;">%2$s</div>
				<div style="display:inline-block;margin:8px 0;padding:10px 18px;border:2px dashed rgba(255,255,255,.5);border-radius:10px;font-size:20px;font-weight:700;letter-spacing:.12em;">%3$s</div>
				<div style="font-size:13px;opacity:.7;margin-top:10px;">%4$s</div>
			</div>',
			esc_html( Options::get( 'discount_timer', 'headline' ) ),
			esc_html( $this->amount_text( $coupon ) ),
			esc_html( $coupon['code'] ),
			$expires ? esc_html( sprintf( /* translators: %s: date. */ __( 'Hurry — expires %s', 'after-purchase-goldmine' ), $expires ) ) : ''
		);
	}
}
