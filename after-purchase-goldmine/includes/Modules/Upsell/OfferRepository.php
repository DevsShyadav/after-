<?php
/**
 * Data access for upsell offers.
 *
 * @package APG
 */

namespace APG\Modules\Upsell;

use APG\Install\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * CRUD and matching logic for the offers table.
 */
final class OfferRepository {

	/**
	 * Get the offers table name.
	 *
	 * @return string
	 */
	private static function table() {
		return Schema::table( 'offers' );
	}

	/**
	 * Sanitize an incoming offer payload.
	 *
	 * @param array $data Raw input.
	 * @return array Sanitized columns.
	 */
	public static function sanitize( array $data ) {
		$trigger_ids = array();
		if ( ! empty( $data['trigger_ids'] ) ) {
			$raw = is_array( $data['trigger_ids'] ) ? $data['trigger_ids'] : explode( ',', (string) $data['trigger_ids'] );
			foreach ( $raw as $id ) {
				$id = absint( $id );
				if ( $id ) {
					$trigger_ids[] = $id;
				}
			}
		}

		return array(
			'title'           => sanitize_text_field( $data['title'] ?? '' ),
			'status'          => in_array( $data['status'] ?? 'active', array( 'active', 'inactive' ), true ) ? $data['status'] : 'active',
			'trigger_type'    => in_array( $data['trigger_type'] ?? 'any', array( 'any', 'product', 'category' ), true ) ? $data['trigger_type'] : 'any',
			'trigger_ids'     => wp_json_encode( array_values( array_unique( $trigger_ids ) ) ),
			'product_id'      => absint( $data['product_id'] ?? 0 ),
			'discount_type'   => in_array( $data['discount_type'] ?? 'percent', array( 'percent', 'fixed', 'none' ), true ) ? $data['discount_type'] : 'percent',
			'discount_amount' => max( 0, (float) ( $data['discount_amount'] ?? 0 ) ),
			'headline'        => sanitize_text_field( $data['headline'] ?? '' ),
			'description'     => sanitize_textarea_field( $data['description'] ?? '' ),
			'image_id'        => absint( $data['image_id'] ?? 0 ),
			'priority'        => (int) ( $data['priority'] ?? 0 ),
		);
	}

	/**
	 * Insert a new offer.
	 *
	 * @param array $data Raw offer data.
	 * @return int|false New offer id or false.
	 */
	public static function create( array $data ) {
		global $wpdb;

		$clean               = self::sanitize( $data );
		$clean['created_at'] = current_time( 'mysql' );
		$clean['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->insert( self::table(), $clean ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return false === $result ? false : (int) $wpdb->insert_id;
	}

	/**
	 * Update an existing offer.
	 *
	 * @param int   $id   Offer id.
	 * @param array $data Raw offer data.
	 * @return bool
	 */
	public static function update( $id, array $data ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return false;
		}

		$clean               = self::sanitize( $data );
		$clean['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->update( self::table(), $clean, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return false !== $result;
	}

	/**
	 * Delete an offer.
	 *
	 * @param int $id Offer id.
	 * @return bool
	 */
	public static function delete( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return false;
		}

		return (bool) $wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Fetch a single offer as an associative array (with decoded trigger_ids).
	 *
	 * @param int $id Offer id.
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;

		$id    = absint( $id );
		$table = self::table();

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? self::hydrate( $row ) : null;
	}

	/**
	 * List offers.
	 *
	 * @param array $args Optional: status, limit, orderby.
	 * @return array[]
	 */
	public static function all( array $args = array() ) {
		global $wpdb;

		$table = self::table();
		$where = '1=1';
		$params = array();

		if ( ! empty( $args['status'] ) ) {
			$where   .= ' AND status = %s';
			$params[] = $args['status'];
		}

		$limit = isset( $args['limit'] ) ? absint( $args['limit'] ) : 100;

		$sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY priority DESC, id DESC LIMIT %d";
		$params[] = $limit;

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare( $sql, $params ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		return array_map( array( __CLASS__, 'hydrate' ), (array) $rows );
	}

	/**
	 * Decode JSON columns on a raw row.
	 *
	 * @param array $row Raw DB row.
	 * @return array
	 */
	private static function hydrate( array $row ) {
		$row['trigger_ids'] = json_decode( (string) $row['trigger_ids'], true );
		if ( ! is_array( $row['trigger_ids'] ) ) {
			$row['trigger_ids'] = array();
		}

		return $row;
	}

	/**
	 * Find the best matching active offer for an order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array|null Offer row or null when nothing matches.
	 */
	public static function find_for_order( $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return null;
		}

		$offers = self::all( array( 'status' => 'active' ) );

		if ( empty( $offers ) ) {
			return null;
		}

		// Build a set of product + category ids already in the order.
		$ordered_products   = array();
		$ordered_categories = array();

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			$pid                      = $item->get_product_id();
			$ordered_products[ $pid ] = true;

			$terms = get_the_terms( $pid, 'product_cat' );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$ordered_categories[ $term->term_id ] = true;
				}
			}
		}

		foreach ( $offers as $offer ) {
			$product = wc_get_product( $offer['product_id'] );

			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				continue;
			}

			// Don't offer something already purchased in this order.
			if ( isset( $ordered_products[ $offer['product_id'] ] ) ) {
				continue;
			}

			$matches = false;

			switch ( $offer['trigger_type'] ) {
				case 'any':
					$matches = true;
					break;

				case 'product':
					foreach ( $offer['trigger_ids'] as $tid ) {
						if ( isset( $ordered_products[ $tid ] ) ) {
							$matches = true;
							break;
						}
					}
					break;

				case 'category':
					foreach ( $offer['trigger_ids'] as $tid ) {
						if ( isset( $ordered_categories[ $tid ] ) ) {
							$matches = true;
							break;
						}
					}
					break;
			}

			if ( $matches ) {
				return $offer;
			}
		}

		return null;
	}

	/**
	 * Increment conversion stats for an offer.
	 *
	 * @param int   $id      Offer id.
	 * @param float $revenue Revenue generated.
	 * @return void
	 */
	public static function record_conversion( $id, $revenue ) {
		global $wpdb;

		$id    = absint( $id );
		$table = self::table();

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"UPDATE {$table} SET conversions = conversions + 1, revenue = revenue + %f WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(float) $revenue,
				$id
			)
		);
	}

	/**
	 * Increment the impression counter for an offer.
	 *
	 * @param int $id Offer id.
	 * @return void
	 */
	public static function record_impression( $id ) {
		global $wpdb;

		$id    = absint( $id );
		$table = self::table();

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"UPDATE {$table} SET impressions = impressions + 1 WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$id
			)
		);
	}

	/**
	 * Compute the effective price for an offer's product after discount.
	 *
	 * @param array       $offer   Offer row.
	 * @param \WC_Product $product Product object.
	 * @return float
	 */
	public static function effective_price( array $offer, $product ) {
		$base = (float) wc_get_price_to_display( $product );

		if ( 'percent' === $offer['discount_type'] ) {
			$base -= $base * ( (float) $offer['discount_amount'] / 100 );
		} elseif ( 'fixed' === $offer['discount_type'] ) {
			$base -= (float) $offer['discount_amount'];
		}

		return max( 0, round( $base, wc_get_price_decimals() ) );
	}
}
