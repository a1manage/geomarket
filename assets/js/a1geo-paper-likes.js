( function () {
	'use strict';

	var controls = Array.prototype.slice.call( document.querySelectorAll( '.a1geo-paper-like' ) );
	var storageKey = 'a1geo-paper-like-email';
	var lookupSequence = 0;
	if ( ! controls.length || typeof a1geoPaperLikes === 'undefined' ) {
		return;
	}

	function normalize( email ) {
		return email.trim().toLowerCase();
	}

	function setStatus( control, liked ) {
		var status = control.querySelector( '.a1geo-paper-like-status' );
		control.classList.toggle( 'is-liked', liked );
		status.textContent = liked ? a1geoPaperLikes.liked : a1geoPaperLikes.notLiked;
	}

	function clearStatuses() {
		controls.forEach( function ( control ) {
			control.classList.remove( 'is-liked' );
			control.querySelector( '.a1geo-paper-like-status' ).textContent = '';
		} );
	}

	function lookup( email ) {
		var sequence = ++lookupSequence;
		if ( ! email ) {
			clearStatuses();
		}
		var body = new URLSearchParams();
		body.append( 'action', 'a1geo_paper_like_status' );
		body.append( 'email', email );
		controls.forEach( function ( control ) {
			body.append( 'paper_ids[]', control.dataset.paperId );
		} );
		fetch( a1geoPaperLikes.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( response ) { return response.json(); } )
			.then( function ( result ) {
				if ( sequence !== lookupSequence ) {
					return;
				}
				if ( ! result.success ) {
					throw new Error( result.data && result.data.message ? result.data.message : a1geoPaperLikes.statusError );
				}
				controls.forEach( function ( control ) {
					var nonce = control.querySelector( '[name="_wpnonce"]' );
					if ( nonce ) {
						nonce.value = result.data.nonces[ control.dataset.paperId ];
						control.querySelector( '.a1geo-paper-like-form [type="submit"]' ).disabled = false;
					}
					if ( email ) {
						setStatus( control, result.data.liked.indexOf( Number( control.dataset.paperId ) ) !== -1 );
					}
				} );
			} )
			.catch( function ( error ) {
				if ( sequence === lookupSequence ) {
					clearStatuses();
					controls.forEach( function ( control ) {
						control.querySelector( '.a1geo-paper-like-status' ).textContent = error.message || a1geoPaperLikes.statusError;
					} );
				}
			} );
	}

	var savedEmail = '';
	try {
		savedEmail = normalize( localStorage.getItem( storageKey ) || '' );
	} catch ( error ) {
		controls.forEach( function ( control ) {
			control.querySelector( '.a1geo-paper-like-status' ).textContent = a1geoPaperLikes.storageError;
		} );
	}
	controls.forEach( function ( control ) {
		var form = control.querySelector( '.a1geo-paper-like-form' );
		if ( ! form ) {
			return;
		}
		var email = form.querySelector( '[name="email"]' );
		var message = form.querySelector( '.a1geo-paper-like-message' );
		var widget = form.querySelector( '.a1geo-paper-turnstile' );
		form.querySelector( '[type="submit"]' ).disabled = true;
		email.value = savedEmail;
		control.addEventListener( 'toggle', function () {
			if ( ! control.open || widget.dataset.rendered ) {
				return;
			}
			if ( ! window.turnstile ) {
				message.textContent = a1geoPaperLikes.captchaError;
				return;
			}
			widget.dataset.rendered = '1';
			window.turnstile.ready( function () {
				widget.dataset.widgetId = window.turnstile.render( widget, {
					sitekey: widget.dataset.sitekey,
					action: 'paper_like'
				} );
			} );
		} );
		form.querySelector( '.a1geo-paper-forget-email' ).addEventListener( 'click', function () {
			try {
				localStorage.removeItem( storageKey );
				savedEmail = '';
				controls.forEach( function ( item ) {
					var input = item.querySelector( '[name="email"]' );
					if ( input ) {
						input.value = '';
					}
				} );
				lookup( '' );
				message.textContent = a1geoPaperLikes.forgotten;
			} catch ( error ) {
				message.textContent = a1geoPaperLikes.storageError;
			}
		} );
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var token = form.querySelector( '[name="cf-turnstile-response"]' );
			if ( ! token || ! token.value ) {
				message.textContent = a1geoPaperLikes.captchaError;
				return;
			}
			var button = form.querySelector( '[type="submit"]' );
			var body = new FormData( form );
			var normalized = normalize( email.value );
			body.set( 'email', normalized );
			body.set( 'response_format', 'json' );
			button.disabled = true;
			message.textContent = '';
			fetch( form.action, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( response ) { return response.json(); } )
				.then( function ( result ) {
					if ( ! result.success ) {
						throw new Error( result.data && result.data.message ? result.data.message : a1geoPaperLikes.submitError );
					}
					message.textContent = result.data.message;
					setStatus( control, result.data.liked );
					if ( result.data.count !== null ) {
						var count = new Intl.NumberFormat().format( result.data.count );
						control.querySelector( 'summary span' ).textContent = count;
						control.querySelector( 'summary' ).setAttribute( 'aria-label', ( result.data.count === 1 ? a1geoPaperLikes.countOne : a1geoPaperLikes.countMany ).replace( '%s', count ) );
					}
					try {
						localStorage.setItem( storageKey, normalized );
						savedEmail = normalized;
						controls.forEach( function ( item ) {
							var input = item.querySelector( '[name="email"]' );
							if ( input ) {
								input.value = savedEmail;
							}
						} );
						lookup( savedEmail );
					} catch ( error ) {
						message.textContent += ' ' + a1geoPaperLikes.storageError;
					}
				} )
				.catch( function ( error ) { message.textContent = error.message || a1geoPaperLikes.submitError; } )
				.finally( function () {
					button.disabled = false;
					if ( window.turnstile && widget.dataset.widgetId ) {
						window.turnstile.reset( widget.dataset.widgetId );
					}
				} );
		} );
	} );
	lookup( savedEmail );
} )();
