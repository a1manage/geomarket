/**
 * WebMCP: expose A1 GeoMarket navigation to AI agents.
 * The search form is exposed declaratively (see custom-navbar-search.php).
 */
( function () {
	'use strict';

	if ( ! window.isSecureContext || ! navigator.modelContext || typeof navigator.modelContext.registerTool !== 'function' ) {
		return;
	}

	var data = window.a1geoWebMcp || {};
	var categories = Array.isArray( data.categories ) ? data.categories : [];

	function result( payload ) {
		return { content: [ { type: 'text', text: JSON.stringify( payload ) } ] };
	}

	function register( tool ) {
		try {
			navigator.modelContext.registerTool( tool );
		} catch ( e ) {
			// Already registered or unsupported shape; leave the page working normally.
		}
	}

	register( {
		name: 'list_categories',
		description: 'List the product, service and application categories on A1 GeoMarket, a global directory of geophysical instruments and services. Each item has a slug, name, parent group and page URL.',
		inputSchema: {
			type: 'object',
			properties: {
				group: {
					type: 'string',
					description: 'Optional top-level group to filter by, for example "Instruments", "Services", "Applications" or "Others".'
				}
			}
		},
		annotations: { readOnlyHint: true },
		execute: function ( args ) {
			var group = args && args.group ? String( args.group ).toLowerCase() : '';
			var items = categories.filter( function ( c ) {
				return ! group || c.group.toLowerCase() === group;
			} );
			return Promise.resolve( result( items ) );
		}
	} );

	register( {
		name: 'open_category',
		description: 'Open the listing page for a category on A1 GeoMarket. Use list_categories first to get valid slugs.',
		inputSchema: {
			type: 'object',
			properties: {
				slug: {
					type: 'string',
					description: 'Category slug, for example "seismic-instruments".'
				}
			},
			required: [ 'slug' ]
		},
		execute: function ( args ) {
			var slug = args && args.slug ? String( args.slug ) : '';
			var match = categories.filter( function ( c ) {
				return c.slug === slug;
			} )[ 0 ];

			if ( ! match ) {
				return Promise.reject( new Error( 'Unknown category slug: ' + slug ) );
			}

			window.location.assign( match.url );
			return Promise.resolve( result( { opened: match.url } ) );
		}
	} );
}() );
