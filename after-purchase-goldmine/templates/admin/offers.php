<?php
/**
 * Admin upsell offers template.
 *
 * @package APG
 *
 * @var array $offers   List of offer rows.
 * @var array $settings All settings.
 */

defined( 'ABSPATH' ) || exit;

$apg_categories = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'number'     => 200,
	)
);
$apg_categories = is_wp_error( $apg_categories ) ? array() : $apg_categories;
?>
<div class="apg-app" data-apg-page="offers">
	<?php require __DIR__ . '/partials-topbar.php'; ?>

	<div class="apg-wrap">
		<div class="apg-pagehead">
			<div>
				<h1 class="apg-pagehead__title"><?php esc_html_e( 'Upsell Offers', 'after-purchase-goldmine' ); ?></h1>
				<p class="apg-pagehead__sub"><?php esc_html_e( 'Create irresistible one-click offers shown right after checkout.', 'after-purchase-goldmine' ); ?></p>
			</div>
			<div class="apg-pagehead__actions">
				<button type="button" class="apg-btn apg-btn--primary apg-js-new-offer">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
					<?php esc_html_e( 'New offer', 'after-purchase-goldmine' ); ?>
				</button>
			</div>
		</div>

		<div class="apg-panel">
			<div class="apg-table-wrap">
				<table class="apg-table" id="apg-offers-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Offer', 'after-purchase-goldmine' ); ?></th>
							<th><?php esc_html_e( 'Trigger', 'after-purchase-goldmine' ); ?></th>
							<th><?php esc_html_e( 'Discount', 'after-purchase-goldmine' ); ?></th>
							<th><?php esc_html_e( 'Status', 'after-purchase-goldmine' ); ?></th>
							<th><?php esc_html_e( 'Conv.', 'after-purchase-goldmine' ); ?></th>
							<th><?php esc_html_e( 'Revenue', 'after-purchase-goldmine' ); ?></th>
							<th class="apg-table__actions"></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $offers ) ) : ?>
							<tr class="apg-empty-row">
								<td colspan="7">
									<div class="apg-empty">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/></svg>
										<h3><?php esc_html_e( 'No offers yet', 'after-purchase-goldmine' ); ?></h3>
										<p><?php esc_html_e( 'Create your first one-click upsell to start earning more from every order.', 'after-purchase-goldmine' ); ?></p>
									</div>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $offers as $apg_offer ) : ?>
								<?php
								$apg_product = wc_get_product( $apg_offer['product_id'] );
								$apg_pname   = $apg_product ? $apg_product->get_name() : sprintf( '#%d', $apg_offer['product_id'] );
								$apg_disc    = 'percent' === $apg_offer['discount_type']
									? wc_format_localized_decimal( $apg_offer['discount_amount'] ) . '%'
									: ( 'none' === $apg_offer['discount_type'] ? '—' : wp_strip_all_tags( wc_price( $apg_offer['discount_amount'] ) ) );
								$apg_trigger_labels = array(
									'any'      => __( 'Any order', 'after-purchase-goldmine' ),
									'product'  => __( 'Specific products', 'after-purchase-goldmine' ),
									'category' => __( 'Categories', 'after-purchase-goldmine' ),
								);

								// Augment the row data with the product name for the edit modal.
								$apg_offer_data                 = $apg_offer;
								$apg_offer_data['product_name'] = $apg_pname;
								?>
								<tr data-offer='<?php echo esc_attr( wp_json_encode( $apg_offer_data ) ); ?>'>
									<td class="apg-table__name">
										<strong><?php echo esc_html( '' !== $apg_offer['title'] ? $apg_offer['title'] : $apg_pname ); ?></strong>
										<span class="apg-table__meta"><?php echo esc_html( $apg_pname ); ?></span>
									</td>
									<td><?php echo esc_html( $apg_trigger_labels[ $apg_offer['trigger_type'] ] ?? $apg_offer['trigger_type'] ); ?></td>
									<td><?php echo esc_html( $apg_disc ); ?></td>
									<td>
										<span class="apg-tag apg-tag--<?php echo 'active' === $apg_offer['status'] ? 'on' : 'off'; ?>">
											<?php echo esc_html( 'active' === $apg_offer['status'] ? __( 'Active', 'after-purchase-goldmine' ) : __( 'Inactive', 'after-purchase-goldmine' ) ); ?>
										</span>
									</td>
									<td><?php echo esc_html( number_format_i18n( $apg_offer['conversions'] ) ); ?></td>
									<td><?php echo wp_kses_post( wc_price( $apg_offer['revenue'] ) ); ?></td>
									<td class="apg-table__actions">
										<button type="button" class="apg-iconbtn apg-js-edit-offer" aria-label="<?php esc_attr_e( 'Edit', 'after-purchase-goldmine' ); ?>">
											<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
										</button>
										<button type="button" class="apg-iconbtn apg-iconbtn--danger apg-js-delete-offer" data-id="<?php echo esc_attr( $apg_offer['id'] ); ?>" aria-label="<?php esc_attr_e( 'Delete', 'after-purchase-goldmine' ); ?>">
											<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- Offer editor modal -->
	<div class="apg-modal" id="apg-offer-modal" hidden>
		<div class="apg-modal__backdrop apg-js-close-modal"></div>
		<div class="apg-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="apg-modal-title">
			<div class="apg-modal__head">
				<h2 id="apg-modal-title" class="apg-modal__title"><?php esc_html_e( 'New offer', 'after-purchase-goldmine' ); ?></h2>
				<button type="button" class="apg-iconbtn apg-js-close-modal" aria-label="<?php esc_attr_e( 'Close', 'after-purchase-goldmine' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
				</button>
			</div>

			<form class="apg-form" id="apg-offer-form">
				<input type="hidden" name="id" value="0" />
				<input type="hidden" name="image_id" value="0" />
				<input type="hidden" name="product_id" value="0" />

				<div class="apg-field">
					<label class="apg-label"><?php esc_html_e( 'Internal title', 'after-purchase-goldmine' ); ?></label>
					<input type="text" class="apg-input" name="title" placeholder="<?php esc_attr_e( 'e.g. Premium gift wrap', 'after-purchase-goldmine' ); ?>" />
				</div>

				<div class="apg-field">
					<label class="apg-label"><?php esc_html_e( 'Product to offer', 'after-purchase-goldmine' ); ?></label>
					<div class="apg-autocomplete" data-mode="single">
						<input type="text" class="apg-input apg-js-product-search" placeholder="<?php esc_attr_e( 'Search products…', 'after-purchase-goldmine' ); ?>" autocomplete="off" />
						<div class="apg-autocomplete__results" hidden></div>
						<div class="apg-autocomplete__chips"></div>
					</div>
				</div>

				<div class="apg-field-row">
					<div class="apg-field">
						<label class="apg-label"><?php esc_html_e( 'Discount type', 'after-purchase-goldmine' ); ?></label>
						<select class="apg-input" name="discount_type">
							<option value="percent"><?php esc_html_e( 'Percentage', 'after-purchase-goldmine' ); ?></option>
							<option value="fixed"><?php esc_html_e( 'Fixed amount', 'after-purchase-goldmine' ); ?></option>
							<option value="none"><?php esc_html_e( 'No discount', 'after-purchase-goldmine' ); ?></option>
						</select>
					</div>
					<div class="apg-field">
						<label class="apg-label"><?php esc_html_e( 'Discount value', 'after-purchase-goldmine' ); ?></label>
						<input type="number" step="0.01" min="0" class="apg-input" name="discount_amount" value="10" />
					</div>
				</div>

				<div class="apg-field">
					<label class="apg-label"><?php esc_html_e( 'Show this offer when the order contains…', 'after-purchase-goldmine' ); ?></label>
					<select class="apg-input apg-js-trigger-type" name="trigger_type">
						<option value="any"><?php esc_html_e( 'Any product (always)', 'after-purchase-goldmine' ); ?></option>
						<option value="product"><?php esc_html_e( 'Specific products', 'after-purchase-goldmine' ); ?></option>
						<option value="category"><?php esc_html_e( 'Specific categories', 'after-purchase-goldmine' ); ?></option>
					</select>
				</div>

				<div class="apg-field apg-js-trigger-products" hidden>
					<label class="apg-label"><?php esc_html_e( 'Trigger products', 'after-purchase-goldmine' ); ?></label>
					<div class="apg-autocomplete" data-mode="multi">
						<input type="text" class="apg-input apg-js-product-search" placeholder="<?php esc_attr_e( 'Search products…', 'after-purchase-goldmine' ); ?>" autocomplete="off" />
						<div class="apg-autocomplete__results" hidden></div>
						<div class="apg-autocomplete__chips"></div>
					</div>
				</div>

				<div class="apg-field apg-js-trigger-categories" hidden>
					<label class="apg-label"><?php esc_html_e( 'Trigger categories', 'after-purchase-goldmine' ); ?></label>
					<select class="apg-input apg-js-categories" name="trigger_categories" multiple size="5">
						<?php foreach ( $apg_categories as $apg_cat ) : ?>
							<option value="<?php echo esc_attr( $apg_cat->term_id ); ?>"><?php echo esc_html( $apg_cat->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="apg-field">
					<label class="apg-label"><?php esc_html_e( 'Headline', 'after-purchase-goldmine' ); ?></label>
					<input type="text" class="apg-input" name="headline" placeholder="<?php esc_attr_e( 'Add this to your order and save!', 'after-purchase-goldmine' ); ?>" />
				</div>

				<div class="apg-field">
					<label class="apg-label"><?php esc_html_e( 'Description', 'after-purchase-goldmine' ); ?></label>
					<textarea class="apg-input" name="description" rows="2" placeholder="<?php esc_attr_e( 'A short, persuasive sentence…', 'after-purchase-goldmine' ); ?>"></textarea>
				</div>

				<div class="apg-field-row">
					<div class="apg-field">
						<label class="apg-label"><?php esc_html_e( 'Custom image', 'after-purchase-goldmine' ); ?></label>
						<div class="apg-image-pick">
							<div class="apg-image-pick__preview apg-js-image-preview"></div>
							<button type="button" class="apg-btn apg-btn--soft apg-js-pick-image"><?php esc_html_e( 'Choose image', 'after-purchase-goldmine' ); ?></button>
							<button type="button" class="apg-btn apg-btn--ghost apg-js-remove-image" hidden><?php esc_html_e( 'Remove', 'after-purchase-goldmine' ); ?></button>
						</div>
					</div>
					<div class="apg-field">
						<label class="apg-label"><?php esc_html_e( 'Priority', 'after-purchase-goldmine' ); ?></label>
						<input type="number" class="apg-input" name="priority" value="0" />
					</div>
				</div>

				<div class="apg-field">
					<label class="apg-checkrow">
						<input type="checkbox" name="status" value="active" checked />
						<span><?php esc_html_e( 'Offer is active', 'after-purchase-goldmine' ); ?></span>
					</label>
				</div>

				<div class="apg-modal__foot">
					<button type="button" class="apg-btn apg-btn--ghost apg-js-close-modal"><?php esc_html_e( 'Cancel', 'after-purchase-goldmine' ); ?></button>
					<button type="submit" class="apg-btn apg-btn--primary">
						<span class="apg-btn__label"><?php esc_html_e( 'Save offer', 'after-purchase-goldmine' ); ?></span>
						<span class="apg-btn__spinner" aria-hidden="true"></span>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
