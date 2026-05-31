/**
 * After-Purchase Goldmine — minimal dependency-free canvas charts.
 * Exposes window.APGCharts with `line` and `bar` renderers.
 */
( function () {
	'use strict';

	function cssVar( name, fallback ) {
		var v = getComputedStyle( document.documentElement ).getPropertyValue( name );
		return ( v && v.trim() ) ? v.trim() : fallback;
	}

	function setup( canvas ) {
		var ratio = window.devicePixelRatio || 1;
		var rect = canvas.getBoundingClientRect();
		var width = rect.width || canvas.parentNode.clientWidth || 600;
		var height = canvas.height || 260;

		canvas.width = width * ratio;
		canvas.height = height * ratio;
		canvas.style.width = width + 'px';
		canvas.style.height = height + 'px';

		var ctx = canvas.getContext( '2d' );
		ctx.scale( ratio, ratio );

		return { ctx: ctx, w: width, h: height };
	}

	function niceMax( max ) {
		if ( max <= 0 ) {
			return 10;
		}
		var pow = Math.pow( 10, Math.floor( Math.log10( max ) ) );
		return Math.ceil( max / pow ) * pow;
	}

	function hexToRgba( hex, alpha ) {
		hex = ( hex || '#10b981' ).replace( '#', '' );
		if ( hex.length === 3 ) {
			hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
		}
		var r = parseInt( hex.substring( 0, 2 ), 16 );
		var g = parseInt( hex.substring( 2, 4 ), 16 );
		var b = parseInt( hex.substring( 4, 6 ), 16 );
		return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
	}

	var APGCharts = {
		line: function ( canvas, opts ) {
			if ( ! canvas ) { return; }
			var s = setup( canvas );
			var ctx = s.ctx;
			var data = opts.data || [];
			var labels = opts.labels || [];
			var color = opts.color || cssVar( '--apg-accent', '#10b981' );
			var grid = cssVar( '--apg-grid', 'rgba(120,130,150,0.15)' );
			var ink = cssVar( '--apg-muted', '#7a8699' );

			var padL = 48, padR = 16, padT = 16, padB = 28;
			var plotW = s.w - padL - padR;
			var plotH = s.h - padT - padB;
			var max = niceMax( Math.max.apply( null, data.concat( [ 1 ] ) ) );
			var n = data.length;

			ctx.clearRect( 0, 0, s.w, s.h );

			// Gridlines + y labels.
			ctx.font = '11px -apple-system,Segoe UI,Roboto,sans-serif';
			ctx.fillStyle = ink;
			ctx.textAlign = 'right';
			ctx.textBaseline = 'middle';
			var steps = 4;
			for ( var i = 0; i <= steps; i++ ) {
				var y = padT + ( plotH / steps ) * i;
				var val = max - ( max / steps ) * i;
				ctx.strokeStyle = grid;
				ctx.lineWidth = 1;
				ctx.beginPath();
				ctx.moveTo( padL, y );
				ctx.lineTo( s.w - padR, y );
				ctx.stroke();
				ctx.fillText( Math.round( val ), padL - 8, y );
			}

			if ( n === 0 ) { return; }

			function px( idx ) {
				return n === 1 ? padL + plotW / 2 : padL + ( plotW / ( n - 1 ) ) * idx;
			}
			function py( v ) {
				return padT + plotH - ( v / max ) * plotH;
			}

			// Area fill.
			var grad = ctx.createLinearGradient( 0, padT, 0, padT + plotH );
			grad.addColorStop( 0, hexToRgba( color, 0.28 ) );
			grad.addColorStop( 1, hexToRgba( color, 0 ) );
			ctx.beginPath();
			ctx.moveTo( px( 0 ), py( data[0] ) );
			for ( var a = 1; a < n; a++ ) {
				ctx.lineTo( px( a ), py( data[a] ) );
			}
			ctx.lineTo( px( n - 1 ), padT + plotH );
			ctx.lineTo( px( 0 ), padT + plotH );
			ctx.closePath();
			ctx.fillStyle = grad;
			ctx.fill();

			// Line.
			ctx.beginPath();
			ctx.moveTo( px( 0 ), py( data[0] ) );
			for ( var b = 1; b < n; b++ ) {
				ctx.lineTo( px( b ), py( data[b] ) );
			}
			ctx.strokeStyle = color;
			ctx.lineWidth = 2.5;
			ctx.lineJoin = 'round';
			ctx.stroke();

			// Points.
			for ( var c = 0; c < n; c++ ) {
				ctx.beginPath();
				ctx.arc( px( c ), py( data[c] ), 3, 0, Math.PI * 2 );
				ctx.fillStyle = color;
				ctx.fill();
			}

			// X labels (sparse).
			ctx.fillStyle = ink;
			ctx.textAlign = 'center';
			ctx.textBaseline = 'top';
			var every = Math.ceil( n / 7 );
			for ( var d = 0; d < n; d++ ) {
				if ( d % every === 0 || d === n - 1 ) {
					ctx.fillText( labels[d] || '', px( d ), padT + plotH + 8 );
				}
			}
		},

		bar: function ( canvas, opts ) {
			if ( ! canvas ) { return; }
			var s = setup( canvas );
			var ctx = s.ctx;
			var data = opts.data || [];
			var labels = opts.labels || [];
			var color = opts.color || cssVar( '--apg-accent', '#10b981' );
			var grid = cssVar( '--apg-grid', 'rgba(120,130,150,0.15)' );
			var ink = cssVar( '--apg-muted', '#7a8699' );

			var padL = 40, padR = 16, padT = 16, padB = 40;
			var plotW = s.w - padL - padR;
			var plotH = s.h - padT - padB;
			var max = niceMax( Math.max.apply( null, data.concat( [ 1 ] ) ) );
			var n = data.length;

			ctx.clearRect( 0, 0, s.w, s.h );

			ctx.font = '11px -apple-system,Segoe UI,Roboto,sans-serif';
			ctx.fillStyle = ink;
			ctx.textAlign = 'right';
			ctx.textBaseline = 'middle';
			var steps = 4;
			for ( var i = 0; i <= steps; i++ ) {
				var y = padT + ( plotH / steps ) * i;
				var val = max - ( max / steps ) * i;
				ctx.strokeStyle = grid;
				ctx.beginPath();
				ctx.moveTo( padL, y );
				ctx.lineTo( s.w - padR, y );
				ctx.stroke();
				ctx.fillText( Math.round( val ), padL - 6, y );
			}

			if ( n === 0 ) { return; }

			var slot = plotW / n;
			var bw = Math.min( 48, slot * 0.55 );

			for ( var j = 0; j < n; j++ ) {
				var x = padL + slot * j + ( slot - bw ) / 2;
				var bh = ( data[j] / max ) * plotH;
				var yTop = padT + plotH - bh;
				var grad = ctx.createLinearGradient( 0, yTop, 0, padT + plotH );
				grad.addColorStop( 0, color );
				grad.addColorStop( 1, hexToRgba( color, 0.55 ) );
				ctx.fillStyle = grad;
				roundRect( ctx, x, yTop, bw, bh, 6 );
				ctx.fill();

				ctx.fillStyle = ink;
				ctx.textAlign = 'center';
				ctx.textBaseline = 'top';
				ctx.fillText( labels[j] || '', x + bw / 2, padT + plotH + 8 );
			}
		}
	};

	function roundRect( ctx, x, y, w, h, r ) {
		if ( h < 0 ) { h = 0; }
		r = Math.min( r, h, w / 2 );
		ctx.beginPath();
		ctx.moveTo( x + r, y );
		ctx.arcTo( x + w, y, x + w, y + h, r );
		ctx.arcTo( x + w, y + h, x, y + h, r );
		ctx.arcTo( x, y + h, x, y, r );
		ctx.arcTo( x, y, x + w, y, r );
		ctx.closePath();
	}

	window.APGCharts = APGCharts;
} )();
