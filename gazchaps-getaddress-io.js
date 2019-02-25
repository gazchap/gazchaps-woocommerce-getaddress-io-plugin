(function(jQuery) {

    var postcode_lookup_cache = [];

    function lookup_button_clicked( btn ) {
        var address_type = '';
        if ( btn.id.indexOf('billing_') > -1 ) {
            address_type = 'billing';
        } else if( btn.id.indexOf('shipping_') > -1 ) {
            address_type = 'shipping';
        }

        if ( address_type ) {
            var postcode_field = document.getElementById( address_type + '_postcode' );
            var postcode = postcode_field.value.replace(/[^A-Za-z0-9]/g, "").toUpperCase();

            if ( postcode.length > 0 ) {
                do_postcode_lookup( postcode, address_type, btn );
            }
        }
    }

    function do_postcode_lookup( postcode, address_type, btn ) {
        if ( typeof postcode_lookup_cache[ postcode + '_' + address_type ] != 'undefined' ) {
            show_address_selector( postcode_lookup_cache[ postcode + '_' + address_type ] );
            return;
        }

        var ajax_data;
        ajax_data = "action=gazchaps_woocommerce_getaddress_io";
        ajax_data += "&postcode=" + postcode;
        ajax_data += "&address_type=" + address_type;

        if ( XMLHttpRequest ) {
            var xhr = new XMLHttpRequest();

            xhr.open('POST', gazchaps_getaddress_io.ajax_url );
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                var response = JSON.parse( xhr.responseText );
                if ( !response.error_code ) {
                    postcode_lookup_cache[ postcode + '_' + address_type ] = response;
                    show_address_selector( response );
                } else {
                    alert( response.error );
                }
            };
            xhr.send( ajax_data );
        }
    }

    function show_address_selector( r ) {
        var selector_form_row_id = r.address_type + '_gazchaps-woocommerce-getaddress-io-address-selector';
        var selector_form_row = document.getElementById( selector_form_row_id );

        if ( selector_form_row ) {
            selector_form_row.parentNode.removeChild(selector_form_row);
        }
        if ( r.address_count > 0 ) {
            var button_form_row = document.getElementById( r.address_type + '_gazchaps_getaddress_io_postcode_lookup_button_field' );
            button_form_row.insertAdjacentHTML( 'afterend', r.fragment.trim() );

            // jQuery select2 stuff
            if ( jQuery && jQuery.fn.select2 ) {
                jQuery( '#' + selector_form_row_id ).find('select').select2();
            }
        }
    }

    function do_address_change( src_id, address ) {
        var address_type = 'billing';
        if ( src_id.indexOf('shipping_') > -1 ) address_type = 'shipping';

        var address_parts = address.split('|');
        var address_field_ids = [ 'address_1', 'address_2', 'city', 'state' ];
        var field_id;
        for (var i = 0; i < address_field_ids.length; i++) {
            field_id = address_type + '_' + address_field_ids[ i ];
            document.getElementById( field_id ).value = address_parts[ i ];
        }
    }

    var lookup_buttons = document.getElementsByClassName('gazchaps-getaddress-io-lookup-button');
    if ( lookup_buttons.length > 0 ) {
        for(var i = 0; i < lookup_buttons.length; i++) {
            lookup_buttons[i].addEventListener("click", function(e) {
                lookup_button_clicked( this );
            });
        }
    }

    // add event listeners for the selectors
    jQuery( 'form.woocommerce-checkout' ).on( 'change', 'select', function (e) {
        if ( 'billing_gazchaps-woocommerce-getaddress-io-address-selector-select' == e.target.id || 'shipping_gazchaps-woocommerce-getaddress-io-address-selector-select' == e.target.id ) {
            var val = e.target.options[e.target.selectedIndex].value;
            if ( '' == val ) {
                val = '||||';
            }
            do_address_change( e.target.id, val );
        }
    } );

    // if we're on the WC checkout, add a clearfix to the additional fields wrappter
    if ( gazchaps_getaddress_io.clear_additional_fields ) {
        var additional_fields_wrappers = document.getElementsByClassName('woocommerce-additional-fields__field-wrapper');
        if ( additional_fields_wrappers.length > 0 ) {
            for(var i = 0; i < additional_fields_wrappers.length; i++) {
                additional_fields_wrappers[i].style.clear = 'both';
            }
        }
    }

})(jQuery);
