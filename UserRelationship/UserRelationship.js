/**
 * JavaScript for UserRelationship
 * Used on Special:ViewRelationshipRequests
 */
function requestResponse( response, id ) {
	document.getElementById( 'request_action_' + id ).style.display = 'none';
	document.getElementById( 'request_action_' + id ).style.visibility = 'hidden';

	jQuery.post(
		mediaWiki.util.wikiScript( 'api' ), {
			action: 'socialprofile-request-response',
			format: 'json',
			response: response,
			id: id
		},
		function( data ) {
			document.getElementById( 'request_action_' + id ).innerHTML = data.html;
			jQuery( '#request_action_' + id ).fadeIn( 2000 );
			document.getElementById( 'request_action_' + id ).style.display = 'block';
			document.getElementById( 'request_action_' + id ).style.visibility = 'visible';
		}
	);
}

jQuery( function() {
	jQuery( 'div.relationship-buttons input[type="button"]' ).on( 'click', function() {
		requestResponse(
			jQuery( this ).data( 'response' ),
			jQuery( this ).parent().parent().attr( 'id' ).replace( /request_action_/, '' )
		);
	} );
} );
