/**
 * After-Purchase Goldmine — landing page interactions.
 * Vanilla JS. Handles nav, reveals, counters, FAQ, WhatsApp widget,
 * the trial download flow and the Razorpay buy flow.
 */
( function () {
	'use strict';

	var API_BASE = 'api/';
	var WHATSAPP_NUMBER = '917654758443';
	var SUPPORT_EMAIL = 'itsdevsarun@gmail.com';

	function $( s, c ) { return ( c || document ).querySelector( s ); }
	function $all( s, c ) { return Array.prototype.slice.call( ( c || document ).querySelectorAll( s ) ); }

	/* ---------------------------------------------------------------- Toast */
	function toast( msg, kind ) {
		var el = $( '#toast' );
		if ( ! el ) { return; }
		el.textContent = msg;
		el.className = 'fixed left-1/2 -translate-x-1/2 bottom-6 z-[70] px-5 py-3 rounded-xl text-white font-semibold text-sm shadow-2xl is-' + ( kind || 'info' );
		el.classList.remove( 'hidden' );
		clearTimeout( el._t );
		el._t = setTimeout( function () { el.classList.add( 'hidden' ); }, 3600 );
	}

	/* ------------------------------------------------------------- Nav state */
	function initNav() {
		var nav = $( '#navbar' );
		function onScroll() {
			if ( ! nav ) { return; }
			nav.classList.toggle( 'scrolled', window.scrollY > 8 );
		}
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		onScroll();

		var burger = $( '#hamburger' );
		var menu = $( '#mobile-menu' );
		var icOpen = $( '#ic-open' );
		var icClose = $( '#ic-close' );
		if ( burger && menu ) {
			burger.addEventListener( 'click', function () {
				var open = menu.classList.toggle( 'hidden' ) === false;
				icOpen.classList.toggle( 'hidden', open );
				icClose.classList.toggle( 'hidden', ! open );
			} );
			$all( 'a', menu ).forEach( function ( a ) {
				a.addEventListener( 'click', function () {
					menu.classList.add( 'hidden' );
					icOpen.classList.remove( 'hidden' );
					icClose.classList.add( 'hidden' );
				} );
			} );
		}
	}

	/* --------------------------------------------------------- Reveal + count */
	function initReveal() {
		var els = $all( '.reveal' );
		if ( ! ( 'IntersectionObserver' in window ) ) {
			els.forEach( function ( el ) { el.classList.add( 'in' ); } );
			return;
		}
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( e ) {
				if ( e.isIntersecting ) {
					var d = parseInt( e.target.getAttribute( 'data-delay' ), 10 ) || 0;
					setTimeout( function () { e.target.classList.add( 'in' ); }, d );
					io.unobserve( e.target );
				}
			} );
		}, { threshold: 0.12 } );
		els.forEach( function ( el ) { io.observe( el ); } );
	}

	function initCounters() {
		var nums = $all( '[data-count]' );
		if ( ! nums.length ) { return; }
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( e ) {
				if ( ! e.isIntersecting ) { return; }
				animate( e.target );
				io.unobserve( e.target );
			} );
		}, { threshold: 0.5 } );
		nums.forEach( function ( n ) { io.observe( n ); } );

		function animate( el ) {
			var target = parseFloat( el.getAttribute( 'data-count' ) ) || 0;
			var prefix = el.getAttribute( 'data-prefix' ) || '';
			var suffix = el.getAttribute( 'data-suffix' ) || '';
			var start = null, dur = 1100;
			function step( ts ) {
				if ( ! start ) { start = ts; }
				var p = Math.min( ( ts - start ) / dur, 1 );
				var eased = 1 - Math.pow( 1 - p, 3 );
				el.textContent = prefix + Math.round( eased * target ).toLocaleString() + suffix;
				if ( p < 1 ) { requestAnimationFrame( step ); }
			}
			requestAnimationFrame( step );
		}
	}

	/* ------------------------------------------------------------------- FAQ */
	function initFaq() {
		$all( '.faq-item' ).forEach( function ( item ) {
			var q = $( '.faq-q', item );
			var a = $( '.faq-a', item );
			if ( ! q || ! a ) { return; }
			q.addEventListener( 'click', function () {
				var open = item.classList.toggle( 'open' );
				a.style.maxHeight = open ? a.scrollHeight + 'px' : '0';
			} );
		} );
	}

	/* ------------------------------------------------------------- WhatsApp */
	function initWhatsApp() {
		var toggle = $( '#wa-toggle' );
		var panel = $( '#wa-panel' );
		var form = $( '#wa-form' );
		if ( toggle && panel ) {
			toggle.addEventListener( 'click', function () { panel.classList.toggle( 'hidden' ); } );
			document.addEventListener( 'click', function ( e ) {
				if ( ! panel.contains( e.target ) && ! toggle.contains( e.target ) ) {
					panel.classList.add( 'hidden' );
				}
			} );
		}
		if ( form ) {
			form.addEventListener( 'submit', function ( e ) {
				e.preventDefault();
				var name = form.name.value.trim();
				var mobile = form.mobile.value.trim();
				var message = form.message.value.trim();
				if ( ! name || ! mobile || ! message ) { return; }
				var text = '*New enquiry — After-Purchase Goldmine*%0A%0A' +
					'*Name:* ' + encodeURIComponent( name ) + '%0A' +
					'*Mobile:* ' + encodeURIComponent( mobile ) + '%0A' +
					'*Message:* ' + encodeURIComponent( message );
				window.open( 'https://wa.me/' + WHATSAPP_NUMBER + '?text=' + text, '_blank', 'noopener' );
				if ( panel ) { panel.classList.add( 'hidden' ); }
				form.reset();
			} );
		}
	}

	/* ---------------------------------------------------------- Lead modal */
	var leadMode = 'trial';

	function openModal( mode ) {
		leadMode = mode;
		var modal = $( '#lead-modal' );
		var title = $( '#lead-title' );
		var sub = $( '#lead-sub' );
		var label = $( '.lead-label' );
		if ( ! modal ) { return; }
		if ( mode === 'buy' ) {
			title.textContent = 'Get Goldmine Pro';
			sub.textContent = 'Enter your details to pay securely and download instantly.';
			label.textContent = 'Pay & download';
		} else {
			title.textContent = 'Start your free trial';
			sub.textContent = 'Get the 24-hour trial. We will send your download link to your email too.';
			label.textContent = 'Download trial';
		}
		modal.classList.remove( 'hidden' );
		document.body.style.overflow = 'hidden';
	}

	function closeModal() {
		var modal = $( '#lead-modal' );
		if ( modal ) { modal.classList.add( 'hidden' ); }
		document.body.style.overflow = '';
		setLoading( false );
	}

	function setLoading( on ) {
		var btn = $( '#lead-submit' );
		if ( ! btn ) { return; }
		btn.disabled = on;
		var spin = $( '.lead-spin', btn );
		var label = $( '.lead-label', btn );
		if ( spin ) { spin.classList.toggle( 'hidden', ! on ); }
		if ( label ) { label.style.opacity = on ? '0.6' : '1'; }
	}

	function initModal() {
		var modal = $( '#lead-modal' );
		var form = $( '#lead-form' );
		if ( ! modal || ! form ) { return; }

		$all( '[data-close]', modal ).forEach( function ( el ) {
			el.addEventListener( 'click', closeModal );
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) { closeModal(); }
		} );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var data = {
				name: form.name.value.trim(),
				email: form.email.value.trim(),
				contact: form.contact.value.trim()
			};
			if ( ! data.name || ! data.email || ! data.contact ) { return; }
			if ( leadMode === 'buy' ) {
				startPayment( data );
			} else {
				startTrial( data );
			}
		} );
	}

	/* ------------------------------------------------------------- Trial flow */
	function startTrial( data ) {
		setLoading( true );
		fetch( API_BASE + 'trial.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( data )
		} ).then( function ( r ) { return r.json(); } ).then( function ( res ) {
			setLoading( false );
			if ( res && res.success && res.download_url ) {
				toast( 'Your trial download is starting…', 'success' );
				triggerDownload( res.download_url );
				closeModal();
			} else {
				toast( ( res && res.message ) || 'Could not start the trial. Email ' + SUPPORT_EMAIL, 'error' );
			}
		} ).catch( function () {
			setLoading( false );
			toast( 'Network error. Please try again or email ' + SUPPORT_EMAIL, 'error' );
		} );
	}

	/* ------------------------------------------------------------- Buy flow */
	function startPayment( data ) {
		if ( typeof Razorpay === 'undefined' ) {
			toast( 'Payment library failed to load. Please refresh.', 'error' );
			return;
		}
		setLoading( true );
		fetch( API_BASE + 'create-order.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( data )
		} ).then( function ( r ) { return r.json(); } ).then( function ( res ) {
			setLoading( false );
			if ( ! res || ! res.success ) {
				toast( ( res && res.message ) || 'Could not start checkout.', 'error' );
				return;
			}
			var options = {
				key: res.key_id,
				amount: res.amount,
				currency: res.currency,
				name: 'After-Purchase Goldmine',
				description: 'Pro License — Lifetime',
				order_id: res.order_id,
				prefill: { name: data.name, email: data.email, contact: data.contact },
				notes: { product: 'goldmine-pro' },
				theme: { color: '#10b981' },
				handler: function ( response ) {
					verifyPayment( response );
				},
				modal: {
					ondismiss: function () { toast( 'Payment cancelled.', 'info' ); }
				}
			};
			var rzp = new Razorpay( options );
			rzp.on( 'payment.failed', function () {
				toast( 'Payment failed. No charge was made.', 'error' );
			} );
			closeModal();
			rzp.open();
		} ).catch( function () {
			setLoading( false );
			toast( 'Network error. Please try again.', 'error' );
		} );
	}

	function verifyPayment( response ) {
		toast( 'Verifying your payment…', 'info' );
		fetch( API_BASE + 'verify-payment.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( response )
		} ).then( function ( r ) { return r.json(); } ).then( function ( res ) {
			if ( res && res.success && res.download_url ) {
				toast( 'Payment successful! Your download is starting…', 'success' );
				triggerDownload( res.download_url );
			} else {
				toast( 'Payment received. If the download did not start, email ' + SUPPORT_EMAIL, 'error' );
			}
		} ).catch( function () {
			toast( 'Could not verify automatically. We have emailed your link. Support: ' + SUPPORT_EMAIL, 'error' );
		} );
	}

	function triggerDownload( url ) {
		var a = document.createElement( 'a' );
		a.href = url;
		a.rel = 'noopener';
		document.body.appendChild( a );
		a.click();
		document.body.removeChild( a );
	}

	/* ---------------------------------------------------------------- Boot */
	function boot() {
		var y = $( '#year' );
		if ( y ) { y.textContent = new Date().getFullYear(); }
		initNav();
		initReveal();
		initCounters();
		initFaq();
		initWhatsApp();
		initModal();
	}

	// Public API used by inline button handlers.
	window.APG = {
		openTrial: function () { openModal( 'trial' ); },
		openBuy: function () { openModal( 'buy' ); }
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
