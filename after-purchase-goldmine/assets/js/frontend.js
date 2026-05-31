/**
 * After-Purchase Goldmine — frontend (thank-you) interactions.
 * Dependency-free, progressive-enhancement vanilla JS.
 */
( function () {
	'use strict';

	if ( typeof window.apgFrontend === 'undefined' ) {
		return;
	}

	var cfg = window.apgFrontend;
	var i18n = cfg.i18n || {};

	/**
	 * Send a fire-and-forget analytics event.
	 *
	 * @param {string} module Module id.
	 * @param {string} type   Event type.
	 */
	function track( module, type ) {
		var payload = {
			module: module,
			type: type,
			order_id: cfg.orderId,
			order_key: cfg.orderKey
		};

		try {
			if ( navigator.sendBeacon ) {
				var blob = new Blob( [ JSON.stringify( payload ) ], { type: 'application/json' } );
				navigator.sendBeacon( cfg.restUrl + 'event', blob );
				return;
			}
		} catch ( e ) {}

		fetch( cfg.restUrl + 'event', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.restNonce },
			body: JSON.stringify( payload ),
			keepalive: true
		} ).catch( function () {} );
	}

	/**
	 * Copy text to the clipboard with a graceful fallback.
	 *
	 * @param {string} text Text to copy.
	 * @return {Promise}
	 */
	function copyText( text ) {
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			return navigator.clipboard.writeText( text );
		}

		return new Promise( function ( resolve, reject ) {
			try {
				var ta = document.createElement( 'textarea' );
				ta.value = text;
				ta.style.position = 'fixed';
				ta.style.opacity = '0';
				document.body.appendChild( ta );
				ta.select();
				document.execCommand( 'copy' );
				document.body.removeChild( ta );
				resolve();
			} catch ( e ) {
				reject( e );
			}
		} );
	}

	/* ---------------------------------------------------------------------
	 * Copy buttons
	 * ------------------------------------------------------------------- */
	function initCopy() {
		document.querySelectorAll( '.apg-js-copy' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var value = btn.getAttribute( 'data-clipboard' ) || '';
				copyText( value ).then( function () {
					btn.classList.add( 'is-copied' );
					var label = btn.querySelector( '.apg-coupon__copy-label' );
					var original = label ? label.textContent : null;
					if ( label ) {
						label.textContent = i18n.copied || 'Copied!';
					}
					setTimeout( function () {
						btn.classList.remove( 'is-copied' );
						if ( label && original !== null ) {
							label.textContent = original;
						}
					}, 1800 );
				} ).catch( function () {} );
			} );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Countdown timers
	 * ------------------------------------------------------------------- */
	function pad( n ) {
		return ( n < 10 ? '0' : '' ) + n;
	}

	function initCountdowns() {
		var timers = document.querySelectorAll( '.apg-js-countdown' );

		timers.forEach( function ( el ) {
			var expires = parseInt( el.getAttribute( 'data-expires' ), 10 );
			if ( ! expires ) {
				return;
			}

			var nodes = {
				days: el.querySelector( '[data-unit="days"]' ),
				hours: el.querySelector( '[data-unit="hours"]' ),
				minutes: el.querySelector( '[data-unit="minutes"]' ),
				seconds: el.querySelector( '[data-unit="seconds"]' )
			};

			function tick() {
				var now = Math.floor( Date.now() / 1000 );
				var remaining = expires - now;

				if ( remaining <= 0 ) {
					el.classList.add( 'is-expired' );
					Object.keys( nodes ).forEach( function ( k ) {
						if ( nodes[ k ] ) {
							nodes[ k ].textContent = '00';
						}
					} );
					clearInterval( handle );
					return;
				}

				var d = Math.floor( remaining / 86400 );
				var h = Math.floor( ( remaining % 86400 ) / 3600 );
				var m = Math.floor( ( remaining % 3600 ) / 60 );
				var s = remaining % 60;

				if ( nodes.days ) { nodes.days.textContent = pad( d ); }
				if ( nodes.hours ) { nodes.hours.textContent = pad( h ); }
				if ( nodes.minutes ) { nodes.minutes.textContent = pad( m ); }
				if ( nodes.seconds ) { nodes.seconds.textContent = pad( s ); }
			}

			tick();
			var handle = setInterval( tick, 1000 );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Click tracking (review + social links)
	 * ------------------------------------------------------------------- */
	function initTracking() {
		document.querySelectorAll( '.apg-js-track' ).forEach( function ( el ) {
			el.addEventListener( 'click', function () {
				track( el.getAttribute( 'data-module' ) || 'social_share', 'click' );
			} );
		} );
	}

	/* ---------------------------------------------------------------------
	 * One-click upsell
	 * ------------------------------------------------------------------- */
	function initUpsell() {
		var card = document.querySelector( '.apg-card--upsell' );
		if ( ! card ) {
			return;
		}

		var acceptBtn = card.querySelector( '.apg-js-upsell-accept' );
		var declineBtn = card.querySelector( '.apg-js-upsell-decline' );
		var result = card.querySelector( '.apg-upsell__result' );

		function showResult( message, kind ) {
			if ( ! result ) {
				return;
			}
			result.textContent = message;
			result.className = 'apg-upsell__result is-' + kind;
			result.hidden = false;
		}

		if ( declineBtn ) {
			declineBtn.addEventListener( 'click', function () {
				track( 'upsell', 'click' );
				var slot = card.closest( '.apg-card-slot' ) || card;
				slot.style.transition = 'opacity .3s ease, transform .3s ease';
				slot.style.opacity = '0';
				slot.style.transform = 'translateY(8px)';
				setTimeout( function () { slot.remove(); }, 300 );
			} );
		}

		if ( ! acceptBtn ) {
			return;
		}

		acceptBtn.addEventListener( 'click', function () {
			if ( acceptBtn.classList.contains( 'is-loading' ) ) {
				return;
			}

			track( 'upsell', 'click' );
			acceptBtn.classList.add( 'is-loading' );

			fetch( cfg.restUrl + 'upsell/accept', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.restNonce },
				body: JSON.stringify( {
					order_id: parseInt( card.getAttribute( 'data-order-id' ), 10 ),
					order_key: card.getAttribute( 'data-order-key' ),
					offer_id: parseInt( card.getAttribute( 'data-offer-id' ), 10 )
				} )
			} ).then( function ( res ) {
				return res.json().then( function ( data ) {
					return { ok: res.ok, data: data };
				} );
			} ).then( function ( payload ) {
				if ( ! payload.ok ) {
					var msg = ( payload.data && payload.data.message ) ? payload.data.message : ( i18n.error || 'Error' );
					acceptBtn.classList.remove( 'is-loading' );
					showResult( msg, 'error' );
					return;
				}

				var data = payload.data || {};

				if ( data.status === 'pay' && data.pay_url ) {
					window.location.href = data.pay_url;
					return;
				}

				// Paid (off-session) — confirm inline.
				acceptBtn.classList.remove( 'is-loading' );
				if ( acceptBtn ) { acceptBtn.style.display = 'none'; }
				if ( declineBtn ) { declineBtn.style.display = 'none'; }
				showResult( ( i18n.added || 'Added to your order!' ) + ' ' + ( data.amount_html || '' ), 'success' );
			} ).catch( function () {
				acceptBtn.classList.remove( 'is-loading' );
				showResult( i18n.error || 'Error', 'error' );
			} );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------- */
	function boot() {
		initCopy();
		initCountdowns();
		initTracking();
		initUpsell();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
