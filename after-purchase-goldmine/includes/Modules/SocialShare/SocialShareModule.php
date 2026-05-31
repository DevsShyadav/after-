<?php
/**
 * Social share module.
 *
 * @package APG
 */

namespace APG\Modules\SocialShare;

use APG\Modules\AbstractModule;
use APG\Support\Helpers;
use APG\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Renders incentive-free social sharing buttons.
 */
final class SocialShareModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'social_share';
	}

	/**
	 * {@inheritDoc}
	 */
	public function title() {
		return __( 'Social Share', 'after-purchase-goldmine' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Let happy customers share your store across their networks in one tap.', 'after-purchase-goldmine' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg>';
	}

	/**
	 * Build share link metadata for the configured networks.
	 *
	 * @param string $share_url  URL to share.
	 * @param string $share_text Text to share.
	 * @return array[]
	 */
	private function networks( $share_url, $share_text ) {
		$enabled = (array) Options::get( 'social_share', 'networks', array() );
		$u       = rawurlencode( $share_url );
		$t       = rawurlencode( $share_text );

		$catalog = array(
			'x'        => array(
				'label' => __( 'Share on X', 'after-purchase-goldmine' ),
				'href'  => "https://twitter.com/intent/tweet?url={$u}&text={$t}",
			),
			'facebook' => array(
				'label' => __( 'Share on Facebook', 'after-purchase-goldmine' ),
				'href'  => "https://www.facebook.com/sharer/sharer.php?u={$u}",
			),
			'whatsapp' => array(
				'label' => __( 'Share on WhatsApp', 'after-purchase-goldmine' ),
				'href'  => "https://api.whatsapp.com/send?text={$t}%20{$u}",
			),
			'linkedin' => array(
				'label' => __( 'Share on LinkedIn', 'after-purchase-goldmine' ),
				'href'  => "https://www.linkedin.com/sharing/share-offsite/?url={$u}",
			),
			'email'    => array(
				'label' => __( 'Share via Email', 'after-purchase-goldmine' ),
				'href'  => 'mailto:?subject=' . rawurlencode( get_bloginfo( 'name' ) ) . "&body={$t}%20{$u}",
			),
			'copy'     => array(
				'label' => __( 'Copy link', 'after-purchase-goldmine' ),
				'href'  => $share_url,
			),
		);

		$result = array();

		foreach ( $enabled as $net ) {
			if ( isset( $catalog[ $net ] ) ) {
				$result[ $net ] = $catalog[ $net ];
			}
		}

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $order ) {
		$share_url  = home_url( '/' );
		$share_text = Helpers::merge_tags( Options::get( 'social_share', 'share_text' ) );
		$networks   = $this->networks( $share_url, $share_text );

		if ( empty( $networks ) ) {
			return '';
		}

		return Helpers::get_template(
			'thankyou/social-share.php',
			array(
				'order'       => $order,
				'networks'    => $networks,
				'share_url'   => $share_url,
				'headline'    => Options::get( 'social_share', 'headline' ),
				'description' => Options::get( 'social_share', 'description' ),
			)
		);
	}
}
