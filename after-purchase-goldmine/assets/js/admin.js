/**
 * After-Purchase Goldmine — admin app.
 * Dependency-free (jQuery available but not required).
 */
( function () {
	'use strict';

	if ( typeof window.apgAdmin === 'undefined' ) {
		return;
	}

	var cfg = window.apgAdmin;
	var i18n = cfg.i18n || {};

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------- */
	function $( sel, ctx ) {
		return ( ctx || document ).querySelector( sel );
	}
	function $all( sel, ctx ) {
		return Array.prototype.slice.call( ( ctx || document ).querySelectorAll( sel ) );
	}
	function escapeHtml( str ) {
		var d = document.createElement( 'div' );
		d.textContent = str == null ? '' : String( str );
		return d.innerHTML;
	}

	function toast( message, kind ) {
		var el = $( '#apg-toast' );
		if ( ! el ) { return; }
		el.textContent = message;
		el.className = 'apg-toast is-' + ( kind || 'success' );
		el.hidden = false;
		clearTimeout( el._t );
		el._t = setTimeout( function () { el.hidden = true; }, 3200 );
	}

	function ajax( action, fields ) {
		var body = new FormData();
		body.append( 'action', action );
		body.append( 'nonce', cfg.nonce );
		fields = fields || {};
		Object.keys( fields ).forEach( function ( k ) {
			body.append( k, fields[ k ] );
		} );
		return fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} ).then( function ( r ) { return r.json(); } );
	}

	function setLoading( btn, on ) {
		if ( ! btn ) { return; }
		if ( on ) {
			btn.classList.add( 'is-loading' );
			btn.disabled = true;
		} else {
			btn.classList.remove( 'is-loading' );
			btn.disabled = false;
		}
	}

	/* ---------------------------------------------------------------------
	 * Animated counters
	 * ------------------------------------------------------------------- */
	function initCounters() {
		$all( '[data-apg-count]' ).forEach( function ( el ) {
			var target = parseInt( el.getAttribute( 'data-apg-count' ), 10 ) || 0;
			if ( target === 0 ) { el.textContent = '0'; return; }
			var start = null;
			var dur = 900;
			function step( ts ) {
				if ( ! start ) { start = ts; }
				var p = Math.min( ( ts - start ) / dur, 1 );
				var eased = 1 - Math.pow( 1 - p, 3 );
				el.textContent = Math.round( eased * target ).toLocaleString();
				if ( p < 1 ) { requestAnimationFrame( step ); }
			}
			requestAnimationFrame( step );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Dashboard charts
	 * ------------------------------------------------------------------- */
	function initDashboard() {
		var dataEl = $( '#apg-dashboard-data' );
		if ( ! dataEl || ! window.APGCharts ) { return; }

		var stats;
		try { stats = JSON.parse( dataEl.textContent ); } catch ( e ) { return; }

		var series = stats.series || { labels: [], revenue: [], conversions: [] };

		window.APGCharts.line( $( '#apg-chart-revenue' ), {
			labels: series.labels,
			data: series.revenue
		} );

		var labels = [];
		var conv = [];
		var names = {
			upsell: 'Upsell',
			discount_timer: 'Timer',
			referral: 'Referral',
			review: 'Review',
			social_share: 'Social'
		};
		Object.keys( stats.by_module || {} ).forEach( function ( k ) {
			labels.push( names[ k ] || k );
			conv.push( stats.by_module[ k ].conversion || 0 );
		} );

		window.APGCharts.bar( $( '#apg-chart-modules' ), { labels: labels, data: conv } );

		var redraw = function () {
			window.APGCharts.line( $( '#apg-chart-revenue' ), { labels: series.labels, data: series.revenue } );
			window.APGCharts.bar( $( '#apg-chart-modules' ), { labels: labels, data: conv } );
		};
		var rt;
		window.addEventListener( 'resize', function () {
			clearTimeout( rt );
			rt = setTimeout( redraw, 200 );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Modules: toggles + drag reorder
	 * ------------------------------------------------------------------- */
	function initModules() {
		var list = $( '#apg-modules-list' );
		if ( ! list ) { return; }

		$all( '.apg-js-toggle-module', list ).forEach( function ( input ) {
			input.addEventListener( 'change', function () {
				ajax( 'apg_toggle_module', {
					module: input.getAttribute( 'data-module' ),
					enabled: input.checked ? 'true' : 'false'
				} ).then( function ( res ) {
					toast( res.success ? i18n.saved : ( res.data && res.data.message ) || i18n.error, res.success ? 'success' : 'error' );
				} ).catch( function () { toast( i18n.error, 'error' ); } );
			} );
		} );

		// Drag and drop reorder.
		var dragEl = null;

		$all( '.apg-module', list ).forEach( function ( item ) {
			item.addEventListener( 'dragstart', function () {
				dragEl = item;
				item.classList.add( 'is-dragging' );
			} );
			item.addEventListener( 'dragend', function () {
				item.classList.remove( 'is-dragging' );
				saveOrder();
			} );
		} );

		list.addEventListener( 'dragover', function ( e ) {
			e.preventDefault();
			if ( ! dragEl ) { return; }
			var after = getDragAfter( list, e.clientY );
			if ( after == null ) {
				list.appendChild( dragEl );
			} else {
				list.insertBefore( dragEl, after );
			}
		} );

		function getDragAfter( container, y ) {
			var els = $all( '.apg-module:not(.is-dragging)', container );
			var closest = { offset: Number.NEGATIVE_INFINITY, element: null };
			els.forEach( function ( child ) {
				var box = child.getBoundingClientRect();
				var offset = y - box.top - box.height / 2;
				if ( offset < 0 && offset > closest.offset ) {
					closest = { offset: offset, element: child };
				}
			} );
			return closest.element;
		}

		function saveOrder() {
			var order = $all( '.apg-module', list ).map( function ( el ) {
				return el.getAttribute( 'data-module' );
			} );
			var body = new FormData();
			body.append( 'action', 'apg_reorder_modules' );
			body.append( 'nonce', cfg.nonce );
			order.forEach( function ( id ) { body.append( 'order[]', id ); } );
			fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) { if ( res.success ) { toast( i18n.saved ); } } )
				.catch( function () {} );
		}
	}

	/* ---------------------------------------------------------------------
	 * Accent color text <-> picker sync
	 * ------------------------------------------------------------------- */
	function initAccent() {
		$all( '.apg-colorpick' ).forEach( function ( wrap ) {
			var color = $( 'input[type="color"]', wrap );
			var text = $( '.apg-js-accent-text', wrap );
			if ( ! color || ! text ) { return; }
			color.addEventListener( 'input', function () { text.value = color.value; } );
			text.addEventListener( 'change', function () {
				if ( /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test( text.value ) ) { color.value = text.value; }
			} );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Product autocomplete
	 * ------------------------------------------------------------------- */
	function initAutocomplete( root ) {
		var input = $( '.apg-js-product-search', root );
		var results = $( '.apg-autocomplete__results', root );
		var chips = $( '.apg-autocomplete__chips', root );
		var mode = root.getAttribute( 'data-mode' ) || 'single';
		root._sel = [];

		function syncHidden() {
			if ( mode !== 'single' ) { return; }
			var form = root.closest( 'form' );
			var hidden = form ? $( 'input[name="product_id"]', form ) : null;
			if ( hidden ) { hidden.value = root._sel.length ? root._sel[0].id : 0; }
		}

		function render() {
			chips.innerHTML = '';
			root._sel.forEach( function ( item ) {
				var chip = document.createElement( 'span' );
				chip.className = 'apg-chip apg-chip--rm';
				chip.innerHTML = '<span>' + escapeHtml( item.text ) + '</span>';
				var x = document.createElement( 'button' );
				x.type = 'button';
				x.setAttribute( 'aria-label', 'Remove' );
				x.textContent = '×';
				x.addEventListener( 'click', function () {
					root._sel = root._sel.filter( function ( s ) { return s.id !== item.id; } );
					render();
					syncHidden();
				} );
				chip.appendChild( x );
				chips.appendChild( chip );
			} );
		}

		function add( item ) {
			if ( mode === 'single' ) {
				root._sel = [ item ];
			} else if ( ! root._sel.some( function ( s ) { return s.id === item.id; } ) ) {
				root._sel.push( item );
			}
			render();
			syncHidden();
			results.hidden = true;
			input.value = '';
		}

		var timer;
		input.addEventListener( 'input', function () {
			clearTimeout( timer );
			var term = input.value.trim();
			timer = setTimeout( function () {
				var url = cfg.ajaxUrl + '?action=apg_search_products&nonce=' + encodeURIComponent( cfg.nonce ) + '&term=' + encodeURIComponent( term );
				fetch( url, { credentials: 'same-origin' } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( res ) {
						if ( ! res.success ) { return; }
						results.innerHTML = '';
						res.data.products.forEach( function ( p ) {
							var b = document.createElement( 'button' );
							b.type = 'button';
							b.className = 'apg-autocomplete__item';
							b.innerHTML = '<span>' + escapeHtml( p.text ) + '</span><em>' + escapeHtml( p.price ) + '</em>';
							b.addEventListener( 'click', function () { add( { id: p.id, text: p.text } ); } );
							results.appendChild( b );
						} );
						results.hidden = res.data.products.length === 0;
					} )
					.catch( function () {} );
			}, 250 );
		} );

		document.addEventListener( 'click', function ( e ) {
			if ( ! root.contains( e.target ) ) { results.hidden = true; }
		} );

		root._set = function ( items ) { root._sel = items || []; render(); syncHidden(); };
		root._get = function () { return root._sel.map( function ( s ) { return s.id; } ); };
	}

	/* ---------------------------------------------------------------------
	 * Offers page
	 * ------------------------------------------------------------------- */
	function initOffers() {
		var page = $( '[data-apg-page="offers"]' );
		if ( ! page ) { return; }

		var modal = $( '#apg-offer-modal' );
		var form = $( '#apg-offer-form' );
		var title = $( '#apg-modal-title' );
		var offeredAC = $( '.apg-autocomplete[data-mode="single"]', form );
		var triggerAC = $( '.apg-autocomplete[data-mode="multi"]', form );
		var triggerType = $( '.apg-js-trigger-type', form );
		var triggerProducts = $( '.apg-js-trigger-products', form );
		var triggerCats = $( '.apg-js-trigger-categories', form );
		var categories = $( '.apg-js-categories', form );
		var imagePreview = $( '.apg-js-image-preview', form );
		var pickImage = $( '.apg-js-pick-image', form );
		var removeImage = $( '.apg-js-remove-image', form );
		var imageId = $( 'input[name="image_id"]', form );

		initAutocomplete( offeredAC );
		initAutocomplete( triggerAC );

		function openModal( isNew ) {
			modal.hidden = false;
			document.body.classList.add( 'apg-modal-open' );
			title.textContent = isNew ? 'New offer' : 'Edit offer';
		}
		function closeModal() {
			modal.hidden = true;
			document.body.classList.remove( 'apg-modal-open' );
		}

		function resetForm() {
			form.reset();
			$( 'input[name="id"]', form ).value = '0';
			$( 'input[name="product_id"]', form ).value = '0';
			imageId.value = '0';
			imagePreview.style.backgroundImage = '';
			imagePreview.classList.remove( 'has-image' );
			removeImage.hidden = true;
			offeredAC._set( [] );
			triggerAC._set( [] );
			updateTriggerVisibility();
		}

		function updateTriggerVisibility() {
			var t = triggerType.value;
			triggerProducts.hidden = t !== 'product';
			triggerCats.hidden = t !== 'category';
		}

		triggerType.addEventListener( 'change', updateTriggerVisibility );

		// New offer.
		$all( '.apg-js-new-offer', page ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () { resetForm(); openModal( true ); } );
		} );

		// Edit offer.
		$all( '.apg-js-edit-offer', page ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var row = btn.closest( 'tr' );
				var offer;
				try { offer = JSON.parse( row.getAttribute( 'data-offer' ) ); } catch ( e ) { return; }
				resetForm();
				$( 'input[name="id"]', form ).value = offer.id;
				$( 'input[name="title"]', form ).value = offer.title || '';
				$( 'select[name="discount_type"]', form ).value = offer.discount_type || 'percent';
				$( 'input[name="discount_amount"]', form ).value = offer.discount_amount || 0;
				$( 'input[name="headline"]', form ).value = offer.headline || '';
				$( 'textarea[name="description"]', form ).value = offer.description || '';
				$( 'input[name="priority"]', form ).value = offer.priority || 0;
				$( 'input[name="status"]', form ).checked = offer.status !== 'inactive';
				$( 'input[name="product_id"]', form ).value = offer.product_id || 0;
				if ( offer.product_id ) {
					offeredAC._set( [ { id: offer.product_id, text: offer.product_name || ( 'Product #' + offer.product_id ) } ] );
				}
				triggerType.value = offer.trigger_type || 'any';
				updateTriggerVisibility();
				var ids = Array.isArray( offer.trigger_ids ) ? offer.trigger_ids : [];
				if ( offer.trigger_type === 'product' ) {
					triggerAC._set( ids.map( function ( id ) { return { id: id, text: 'Product #' + id }; } ) );
				} else if ( offer.trigger_type === 'category' && categories ) {
					$all( 'option', categories ).forEach( function ( opt ) {
						opt.selected = ids.indexOf( parseInt( opt.value, 10 ) ) !== -1;
					} );
				}
				if ( offer.image_id && offer.image_id > 0 ) {
					imageId.value = offer.image_id;
					removeImage.hidden = false;
				}
				openModal( false );
			} );
		} );

		// Close modal.
		$all( '.apg-js-close-modal', modal ).forEach( function ( btn ) {
			btn.addEventListener( 'click', closeModal );
		} );

		// Image picker (wp.media).
		var frame;
		if ( pickImage ) {
			pickImage.addEventListener( 'click', function () {
				if ( typeof wp === 'undefined' || ! wp.media ) { return; }
				if ( frame ) { frame.open(); return; }
				frame = wp.media( {
					title: i18n.selectImg,
					button: { text: i18n.useImg },
					multiple: false
				} );
				frame.on( 'select', function () {
					var att = frame.state().get( 'selection' ).first().toJSON();
					imageId.value = att.id;
					var url = ( att.sizes && att.sizes.thumbnail ) ? att.sizes.thumbnail.url : att.url;
					imagePreview.style.backgroundImage = 'url(' + url + ')';
					imagePreview.classList.add( 'has-image' );
					removeImage.hidden = false;
				} );
				frame.open();
			} );
		}
		if ( removeImage ) {
			removeImage.addEventListener( 'click', function () {
				imageId.value = '0';
				imagePreview.style.backgroundImage = '';
				imagePreview.classList.remove( 'has-image' );
				removeImage.hidden = true;
			} );
		}

		// Save offer.
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var submitBtn = $( 'button[type="submit"]', form );

			var triggerIds = [];
			if ( triggerType.value === 'product' ) {
				triggerIds = triggerAC._get();
			} else if ( triggerType.value === 'category' && categories ) {
				triggerIds = $all( 'option:checked', categories ).map( function ( o ) { return parseInt( o.value, 10 ); } );
			}

			var payload = {
				id: parseInt( $( 'input[name="id"]', form ).value, 10 ) || 0,
				title: $( 'input[name="title"]', form ).value,
				product_id: parseInt( $( 'input[name="product_id"]', form ).value, 10 ) || 0,
				discount_type: $( 'select[name="discount_type"]', form ).value,
				discount_amount: $( 'input[name="discount_amount"]', form ).value,
				trigger_type: triggerType.value,
				trigger_ids: triggerIds,
				headline: $( 'input[name="headline"]', form ).value,
				description: $( 'textarea[name="description"]', form ).value,
				image_id: parseInt( imageId.value, 10 ) || 0,
				priority: parseInt( $( 'input[name="priority"]', form ).value, 10 ) || 0,
				status: $( 'input[name="status"]', form ).checked ? 'active' : 'inactive'
			};

			if ( ! payload.product_id ) {
				toast( 'Please choose a product to offer.', 'error' );
				return;
			}

			setLoading( submitBtn, true );
			ajax( 'apg_save_offer', { payload: JSON.stringify( payload ) } )
				.then( function ( res ) {
					setLoading( submitBtn, false );
					if ( res.success ) {
						toast( res.data.message || i18n.saved );
						closeModal();
						setTimeout( function () { window.location.reload(); }, 600 );
					} else {
						toast( ( res.data && res.data.message ) || i18n.error, 'error' );
					}
				} )
				.catch( function () { setLoading( submitBtn, false ); toast( i18n.error, 'error' ); } );
		} );

		// Delete offer.
		$all( '.apg-js-delete-offer', page ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				if ( ! window.confirm( i18n.confirmDel ) ) { return; }
				ajax( 'apg_delete_offer', { id: btn.getAttribute( 'data-id' ) } )
					.then( function ( res ) {
						if ( res.success ) {
							toast( res.data.message || i18n.saved );
							var row = btn.closest( 'tr' );
							if ( row ) { row.parentNode.removeChild( row ); }
						} else {
							toast( ( res.data && res.data.message ) || i18n.error, 'error' );
						}
					} )
					.catch( function () { toast( i18n.error, 'error' ); } );
			} );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Settings page
	 * ------------------------------------------------------------------- */
	function serializeNested( form ) {
		var out = {};
		$all( '[name]', form ).forEach( function ( el ) {
			var name = el.getAttribute( 'name' );
			var m = name.match( /^([\w]+)\[([\w]+)\](\[\])?$/ );
			if ( ! m ) { return; }
			var group = m[1], key = m[2], isArr = !! m[3];
			if ( ! out[ group ] ) { out[ group ] = {}; }
			var val;
			if ( el.type === 'checkbox' ) {
				if ( isArr ) {
					if ( ! el.checked ) { return; }
					val = el.value;
				} else {
					out[ group ][ key ] = el.checked;
					return;
				}
			} else {
				val = el.value;
			}
			if ( isArr ) {
				if ( ! Array.isArray( out[ group ][ key ] ) ) { out[ group ][ key ] = []; }
				out[ group ][ key ].push( val );
			} else {
				out[ group ][ key ] = val;
			}
		} );
		return out;
	}

	function initSettings() {
		var form = $( '#apg-settings-form' );
		if ( ! form ) { return; }

		// Tabs.
		$all( '.apg-tab' ).forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				var target = tab.getAttribute( 'data-tab' );
				$all( '.apg-tab' ).forEach( function ( t ) { t.classList.toggle( 'is-active', t === tab ); } );
				$all( '.apg-tabpanel' ).forEach( function ( p ) {
					p.classList.toggle( 'is-active', p.getAttribute( 'data-panel' ) === target );
				} );
			} );
		} );

		// Save.
		$all( '.apg-js-save-settings' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				setLoading( btn, true );
				var payload = serializeNested( form );
				ajax( 'apg_save_settings', { payload: JSON.stringify( payload ) } )
					.then( function ( res ) {
						setLoading( btn, false );
						toast( res.success ? ( res.data.message || i18n.saved ) : i18n.error, res.success ? 'success' : 'error' );
					} )
					.catch( function () { setLoading( btn, false ); toast( i18n.error, 'error' ); } );
			} );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Onboarding wizard
	 * ------------------------------------------------------------------- */
	function initWizard() {
		var wizard = $( '#apg-wizard' );
		if ( ! wizard ) { return; }

		var form = $( '#apg-wizard-form' );
		var steps = $all( '.apg-step', form );
		var dots = $all( '[data-step-dot]', wizard );
		var backBtn = $( '.apg-js-wizard-back', form );
		var nextBtn = $( '.apg-js-wizard-next', form );
		var finishBtn = $( '.apg-js-wizard-finish', form );
		var current = 1;
		var total = steps.length;

		function show( step ) {
			current = Math.max( 1, Math.min( total, step ) );
			steps.forEach( function ( s ) {
				s.classList.toggle( 'is-active', parseInt( s.getAttribute( 'data-step' ), 10 ) === current );
			} );
			dots.forEach( function ( d ) {
				var n = parseInt( d.getAttribute( 'data-step-dot' ), 10 );
				d.classList.toggle( 'is-active', n === current );
				d.classList.toggle( 'is-done', n < current );
			} );
			backBtn.hidden = current === 1;
			nextBtn.hidden = current === total;
			finishBtn.hidden = current !== total;
		}

		nextBtn.addEventListener( 'click', function () { show( current + 1 ); } );
		backBtn.addEventListener( 'click', function () { show( current - 1 ); } );

		finishBtn.addEventListener( 'click', function () {
			setLoading( finishBtn, true );
			var payload = serializeNested( form );
			ajax( 'apg_complete_onboarding', { payload: JSON.stringify( payload ) } )
				.then( function ( res ) {
					setLoading( finishBtn, false );
					if ( res.success ) {
						window.location.href = cfg.dashboardUrl || ( cfg.ajaxUrl.replace( 'admin-ajax.php', 'admin.php?page=apg-dashboard' ) );
					} else {
						toast( i18n.error, 'error' );
					}
				} )
				.catch( function () { setLoading( finishBtn, false ); toast( i18n.error, 'error' ); } );
		} );

		show( 1 );
	}

	/* ---------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------- */
	function boot() {
		initCounters();
		initDashboard();
		initModules();
		initAccent();
		initOffers();
		initSettings();
		initWizard();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
