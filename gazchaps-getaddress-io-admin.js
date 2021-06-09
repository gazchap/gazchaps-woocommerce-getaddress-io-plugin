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
            var postcode_field = document.getElementById( '_' + address_type + '_postcode' );
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
        ajax_data = "action=gazchaps_woocommerce_getaddress_io_wp_admin";
        ajax_data += "&postcode=" + postcode;
        ajax_data += "&address_type=" + address_type;

        if ( XMLHttpRequest ) {
            if ( btn ) {
                btn.value = gazchaps_getaddress_io.searching_text;
                btn.disabled = true;
            }
            var xhr = new XMLHttpRequest();

            xhr.open('POST', gazchaps_getaddress_io.ajax_url );
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if ( btn ) {
                    btn.value = gazchaps_getaddress_io.button_text;
                    btn.disabled = false;
                }

                var response = JSON.parse( xhr.responseText );
                if ( !response.error_code ) {
                    postcode_lookup_cache[ postcode + '_' + address_type ] = response;
                    tidy_postcode_field( response );
                    show_address_selector( response );
                } else {
                    alert( response.error );
                }
            };
            xhr.onerror = function () {
                if ( btn ) {
                    btn.value = gazchaps_getaddress_io.button_text;
                    btn.disabled = false;
                }
            };
            xhr.send( ajax_data );
        }
    }

    function tidy_postcode_field( r ) {
        var postcode_field = document.getElementById( '_' + r.address_type + '_postcode' );
        if ( postcode_field && r.postcode ) {
            postcode_field.value = r.postcode;
        }
    }

    function show_address_selector( r ) {
        var selector_id = '_' + r.address_type + '_gazchaps_getaddress_io_postcode_lookup_addresses';
        var selector = document.getElementById( selector_id );
        var container = document.getElementsByClassName( selector_id + '_field' )[0];
        if ( selector ) {
            var options = selector.querySelectorAll('option');
            for ( var i in options ) {
                if ( options.hasOwnProperty(i) ) {
                    options[i].remove();
                }
            }
        }
        if ( r.address_count > 0 ) {
            var address, option;
            option = document.createElement('option');
            option.value = '';
            option.innerHTML = r.select_placeholder;
            selector.appendChild( option );
            for ( var i in r.addresses ) {
                if ( r.addresses.hasOwnProperty(i) ) {
                    address = r.addresses[i];
                    option = document.createElement('option');
                    option.value = address.option;
                    option.innerHTML = address.label;
                    selector.appendChild( option );
                }
            }

            // jQuery select2 stuff
            if ( jQuery && jQuery.fn.select2 ) {
                jQuery( '#' + selector_id ).select2();
            }
        }
        if ( container ) {
            container.style.display = '';
        }
    }

    function do_address_change( src_id, address ) {
        var address_type = 'billing';
        if ( src_id.indexOf('shipping_') > -1 ) address_type = 'shipping';

        var address_parts = address.split('|');
        var address_field_ids = [ 'address_1', 'address_2', 'city', 'state' ];
        var field_id, field_element;
        for (var i = 0; i < address_field_ids.length; i++) {
            field_id = '_' + address_type + '_' + address_field_ids[ i ];
            field_element = document.getElementById( field_id );
            if ( field_element !== null ) {
                field_element.value = address_parts[ i ];
            }
        }
    }

    var lookup_buttons = document.getElementsByClassName('gazchaps_getaddress_io_postcode_lookup_button');
    if ( lookup_buttons.length > 0 ) {
        var button, container;
        var fieldsContainer, postcodeContainerClass, postcodeContainer, selectorContainerClass, selectorContainer;
        for(var i = 0; i < lookup_buttons.length; i++) {
            button = lookup_buttons[i];
            container = button.closest('p');
            fieldsContainer = container.parentNode;

            container.style.float = 'right';
            container.style.clear = 'right';
            container.querySelector( 'label' ).innerHTML = '&nbsp';

            button.value = gazchaps_getaddress_io.button_text;
            button.addEventListener("click", function(e) {
                lookup_button_clicked( this );
            });

            // hide the select address menu to begin with
            selectorContainerClass = button.id.replace( 'gazchaps_getaddress_io_postcode_lookup_button', 'gazchaps_getaddress_io_postcode_lookup_addresses' ) + '_field';
            selectorContainer = document.getElementsByClassName( selectorContainerClass )[0];
            if ( selectorContainer ) {
                selectorContainer.style.display = 'none';
            }

            // change the styles of the postcode field to put it to the left of the find address button
            postcodeContainerClass = button.id.replace( 'gazchaps_getaddress_io_postcode_lookup_button', 'postcode' ) + '_field';
            postcodeContainer = document.getElementsByClassName( postcodeContainerClass )[0];
            if ( postcodeContainer ) {
                postcodeContainer.style.float = 'left';
                postcodeContainer.style.clear = 'both';
            }
        }
    }

    // add event listeners for the selectors
    jQuery( document ).on( 'change', '.gazchaps_getaddress_io_postcode_lookup_addresses', function (e) {
        var val = e.target.options[e.target.selectedIndex].value;
        if ( '' == val ) {
            val = '||||';
        }
        do_address_change( e.target.id, val );
    } );

    // add event listeners for the country selectors, to hide the button except when dealing with GB
    jQuery( document ).on( 'change', '#_billing_country, #_shipping_country', function (e) {
        var prefix;
        if ( e.target.id.indexOf( '_billing' ) > -1 ) {
            prefix = '_billing_';
        } else {
            prefix = '_shipping_';
        }
        var fieldsContainer = e.target.closest('p').parentNode;
        var findAddressContainer = document.getElementsByClassName( prefix + 'gazchaps_getaddress_io_postcode_lookup_button_field' )[0];
        var addressSelectorContainer = document.getElementsByClassName( prefix + 'gazchaps_getaddress_io_postcode_lookup_addresses_field' )[0];
        var addressSelector = document.getElementsByClassName( prefix + 'gazchaps_getaddress_io_postcode_lookup_addresses' )[0];

        var val = e.target.options[e.target.selectedIndex].value;
        if ( 'GB' == val ) {
            if ( findAddressContainer ) {
                findAddressContainer.style.display = '';
            }
        } else {
            if ( findAddressContainer ) {
                findAddressContainer.style.display = 'none';
            }
            if ( addressSelectorContainer ) {
                addressSelectorContainer.style.display = 'none';
            }
            if ( addressSelector ) {
                addressSelector.disabled = true;
            }
        }
    } );
    jQuery( '#_billing_country, #_shipping_country' ).trigger('change');

})(jQuery);
