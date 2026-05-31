<?php
/**
 * Typed settings access layer.
 *
 * @package APG
 */

namespace APG\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Reads, writes and sanitizes the single autoloaded settings option.
 */
final class Options {

	/**
	 * Option key for the settings array.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'apg_settings';

	/**
	 * Runtime cache of the resolved settings.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Return the full default settings structure.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'general'        => array(
				'theme'                     => 'auto',
				'accent'                    => '#ef4444',
				'glass'                     => true,
				'card_radius'               => 20,
				'animations'                => true,
				'delete_data_on_uninstall'  => false,
				'title'                     => 'A little something before you go',
			),
			'modules'        => array(
				'upsell'         => true,
				'discount_timer' => true,
				'referral'       => true,
				'review'         => true,
				'social_share'   => true,
			),
			'module_order'   => array( 'upsell', 'discount_timer', 'referral', 'review', 'social_share' ),
			'upsell'         => array(
				'button_label'  => 'Add to my order — one click',
				'decline_label' => 'No thanks, maybe later',
				'success_label' => 'Added to your order!',
			),
			'discount_timer' => array(
				'discount_type'   => 'percent',
				'discount_amount' => 15,
				'expiry_hours'    => 24,
				'headline'        => 'Your exclusive reward is waiting',
				'description'     => 'A thank-you gift toward your next order. Use it before the timer runs out.',
				'email_coupon'    => true,
				'min_spend'       => 0,
			),
			'referral'       => array(
				'reward_type'            => 'fixed',
				'reward_amount'          => 10,
				'friend_discount_type'   => 'fixed',
				'friend_discount_amount' => 10,
				'headline'               => 'Give $10, Get $10',
				'description'            => 'Share your link. When a friend places their first order, you both get rewarded.',
				'cookie_days'            => 30,
			),
			'review'         => array(
				'headline'    => 'How did we do?',
				'description' => 'Your review helps other shoppers and means the world to our team.',
			),
			'social_share'   => array(
				'headline'    => 'Spread the word',
				'description' => 'Loved your purchase? Share the love with your friends.',
				'networks'    => array( 'x', 'facebook', 'whatsapp', 'linkedin', 'email', 'copy' ),
				'share_text'  => 'I just shopped at {site} and loved it!',
			),
		);
	}

	/**
	 * Seed default settings if none exist yet (merges to preserve new keys).
	 *
	 * @return void
	 */
	public static function seed_defaults() {
		$existing = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		$merged = self::deep_merge( self::defaults(), $existing );
		update_option( self::OPTION_KEY, $merged );
		self::$cache = $merged;
	}

	/**
	 * Get the full settings array, merged with defaults.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		self::$cache = self::deep_merge( self::defaults(), $stored );

		return self::$cache;
	}

	/**
	 * Get a single setting using a group and key.
	 *
	 * @param string $group   Settings group (e.g. 'general').
	 * @param string $key     Key inside the group.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public static function get( $group, $key = null, $default = null ) {
		$all = self::all();

		if ( null === $key ) {
			return isset( $all[ $group ] ) ? $all[ $group ] : $default;
		}

		if ( isset( $all[ $group ][ $key ] ) ) {
			return $all[ $group ][ $key ];
		}

		return $default;
	}

	/**
	 * Whether a module is enabled.
	 *
	 * @param string $module Module id.
	 * @return bool
	 */
	public static function module_enabled( $module ) {
		$modules = self::get( 'modules' );

		return ! empty( $modules[ $module ] );
	}

	/**
	 * Persist a sanitized settings array (full replacement after merge).
	 *
	 * @param array $data Raw settings (will be sanitized).
	 * @return array The stored, sanitized settings.
	 */
	public static function update( array $data ) {
		$sanitized   = self::sanitize( $data );
		$merged      = self::deep_merge( self::all(), $sanitized );
		self::$cache = $merged;
		update_option( self::OPTION_KEY, $merged );

		return $merged;
	}

	/**
	 * Sanitize an incoming settings payload against the known schema.
	 *
	 * @param array $data Raw input.
	 * @return array Sanitized data limited to known keys.
	 */
	public static function sanitize( array $data ) {
		$clean = array();

		if ( isset( $data['general'] ) && is_array( $data['general'] ) ) {
			$g                               = $data['general'];
			$clean['general']['theme']       = in_array( $g['theme'] ?? '', array( 'auto', 'light', 'dark' ), true ) ? $g['theme'] : 'auto';
			$clean['general']['accent']      = self::sanitize_hex( $g['accent'] ?? '#ef4444' );
			$clean['general']['glass']       = ! empty( $g['glass'] );
			$clean['general']['animations']  = ! empty( $g['animations'] );
			$clean['general']['card_radius'] = max( 0, min( 40, (int) ( $g['card_radius'] ?? 20 ) ) );
			$clean['general']['title']       = sanitize_text_field( $g['title'] ?? '' );
			$clean['general']['delete_data_on_uninstall'] = ! empty( $g['delete_data_on_uninstall'] );
		}

		if ( isset( $data['modules'] ) && is_array( $data['modules'] ) ) {
			foreach ( array( 'upsell', 'discount_timer', 'referral', 'review', 'social_share' ) as $mod ) {
				$clean['modules'][ $mod ] = ! empty( $data['modules'][ $mod ] );
			}
		}

		if ( isset( $data['module_order'] ) && is_array( $data['module_order'] ) ) {
			$allowed = array( 'upsell', 'discount_timer', 'referral', 'review', 'social_share' );
			$order   = array();
			foreach ( $data['module_order'] as $mod ) {
				$mod = sanitize_key( $mod );
				if ( in_array( $mod, $allowed, true ) && ! in_array( $mod, $order, true ) ) {
					$order[] = $mod;
				}
			}
			// Append any missing modules to keep the list complete.
			foreach ( $allowed as $mod ) {
				if ( ! in_array( $mod, $order, true ) ) {
					$order[] = $mod;
				}
			}
			$clean['module_order'] = $order;
		}

		if ( isset( $data['upsell'] ) && is_array( $data['upsell'] ) ) {
			$clean['upsell']['button_label']  = sanitize_text_field( $data['upsell']['button_label'] ?? '' );
			$clean['upsell']['decline_label'] = sanitize_text_field( $data['upsell']['decline_label'] ?? '' );
			$clean['upsell']['success_label'] = sanitize_text_field( $data['upsell']['success_label'] ?? '' );
		}

		if ( isset( $data['discount_timer'] ) && is_array( $data['discount_timer'] ) ) {
			$d                                       = $data['discount_timer'];
			$clean['discount_timer']['discount_type']   = in_array( $d['discount_type'] ?? '', array( 'percent', 'fixed' ), true ) ? $d['discount_type'] : 'percent';
			$clean['discount_timer']['discount_amount'] = max( 0, (float) ( $d['discount_amount'] ?? 0 ) );
			$clean['discount_timer']['expiry_hours']    = max( 1, min( 720, (int) ( $d['expiry_hours'] ?? 24 ) ) );
			$clean['discount_timer']['headline']        = sanitize_text_field( $d['headline'] ?? '' );
			$clean['discount_timer']['description']     = sanitize_textarea_field( $d['description'] ?? '' );
			$clean['discount_timer']['email_coupon']    = ! empty( $d['email_coupon'] );
			$clean['discount_timer']['min_spend']       = max( 0, (float) ( $d['min_spend'] ?? 0 ) );
		}

		if ( isset( $data['referral'] ) && is_array( $data['referral'] ) ) {
			$r                                          = $data['referral'];
			$clean['referral']['reward_type']           = in_array( $r['reward_type'] ?? '', array( 'percent', 'fixed' ), true ) ? $r['reward_type'] : 'fixed';
			$clean['referral']['reward_amount']         = max( 0, (float) ( $r['reward_amount'] ?? 0 ) );
			$clean['referral']['friend_discount_type']  = in_array( $r['friend_discount_type'] ?? '', array( 'percent', 'fixed' ), true ) ? $r['friend_discount_type'] : 'fixed';
			$clean['referral']['friend_discount_amount'] = max( 0, (float) ( $r['friend_discount_amount'] ?? 0 ) );
			$clean['referral']['headline']              = sanitize_text_field( $r['headline'] ?? '' );
			$clean['referral']['description']           = sanitize_textarea_field( $r['description'] ?? '' );
			$clean['referral']['cookie_days']           = max( 1, min( 365, (int) ( $r['cookie_days'] ?? 30 ) ) );
		}

		if ( isset( $data['review'] ) && is_array( $data['review'] ) ) {
			$clean['review']['headline']    = sanitize_text_field( $data['review']['headline'] ?? '' );
			$clean['review']['description'] = sanitize_textarea_field( $data['review']['description'] ?? '' );
		}

		if ( isset( $data['social_share'] ) && is_array( $data['social_share'] ) ) {
			$s                                  = $data['social_share'];
			$clean['social_share']['headline']    = sanitize_text_field( $s['headline'] ?? '' );
			$clean['social_share']['description'] = sanitize_textarea_field( $s['description'] ?? '' );
			$clean['social_share']['share_text']  = sanitize_text_field( $s['share_text'] ?? '' );

			$allowed_networks = array( 'x', 'facebook', 'whatsapp', 'linkedin', 'email', 'copy' );
			$networks         = array();
			if ( ! empty( $s['networks'] ) && is_array( $s['networks'] ) ) {
				foreach ( $s['networks'] as $net ) {
					$net = sanitize_key( $net );
					if ( in_array( $net, $allowed_networks, true ) && ! in_array( $net, $networks, true ) ) {
						$networks[] = $net;
					}
				}
			}
			$clean['social_share']['networks'] = $networks;
		}

		return $clean;
	}

	/**
	 * Sanitize a hex color, returning a safe fallback when invalid.
	 *
	 * @param string $value Raw color value.
	 * @return string
	 */
	private static function sanitize_hex( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		$clean = sanitize_hex_color( $value );

		return $clean ? $clean : '#ef4444';
	}

	/**
	 * Recursively merge defaults with stored values (stored wins on scalars).
	 *
	 * @param array $defaults Default structure.
	 * @param array $stored   Stored structure.
	 * @return array
	 */
	private static function deep_merge( array $defaults, array $stored ) {
		$result = $defaults;

		foreach ( $stored as $key => $value ) {
			if ( is_array( $value ) && isset( $result[ $key ] ) && is_array( $result[ $key ] ) && ! self::is_list( $result[ $key ] ) ) {
				$result[ $key ] = self::deep_merge( $result[ $key ], $value );
			} else {
				$result[ $key ] = $value;
			}
		}

		return $result;
	}

	/**
	 * Determine if an array is a sequential list (vs associative map).
	 *
	 * @param array $arr Array to inspect.
	 * @return bool
	 */
	private static function is_list( array $arr ) {
		if ( array() === $arr ) {
			return true;
		}

		return array_keys( $arr ) === range( 0, count( $arr ) - 1 );
	}

	/**
	 * Clear the runtime cache (useful in tests / after external updates).
	 *
	 * @return void
	 */
	public static function flush_cache() {
		self::$cache = null;
	}
}
