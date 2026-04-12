( function( $ ) {
    $( document ).on( 'click', 'a[data-cancel-booking="1"]', showConfirmDeleteDialog )

    function showConfirmDeleteDialog( event ) {
        event.preventDefault();
        event.stopPropagation();

        let $this = $( this );

        if ( window.confirm( window.JetABAFActionsData.cancel_confirmation ) ) {
            window.location = $this.attr( 'href' );
        }
    }
}( jQuery ) );