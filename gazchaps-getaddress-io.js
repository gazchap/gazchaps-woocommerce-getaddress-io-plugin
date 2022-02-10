(function(jQuery) {

    var postcode_lookup_cache = [];

    function show_or_hide_address_fields( type, show_or_hide ) {
        if ( gazchaps_getaddress_io.hide_address_fields && gazchaps_getaddress_io.fields_to_hide && gazchaps_getaddress_io.field_prefixes ) {
            var address_fields = gazchaps_getaddress_io.fields_to_hide || [];
            for ( var i in gazchaps_getaddress_io.field_prefixes ) {
                var prefix = gazchaps_getaddress_io.field_prefixes[i];
                if ( !type || prefix === type ) {
                    var is_gb = false;

                    // only modify if they're all empty and country is set to GB
                    var country_field = prefix + '_country';
                    var countryElement = document.getElementById( country_field );
                    if ( countryElement ) {
                        if ( ( countryElement.value && 'GB' == countryElement.value ) || ( countryElement.options && 'GB' == countryElement.options[ countryElement.selectedIndex ].value ) ) {
                            is_gb = true;
                        }
                    }

                    var all_empty = true;
                    for ( var j in gazchaps_getaddress_io.fields_to_hide ) {
                        var field = prefix + '_' + gazchaps_getaddress_io.fields_to_hide[j];
                        var fieldElement = document.getElementById( field );
                        if ( fieldElement && '' != fieldElement.value ) {
                            all_empty = false;
                            break;
                        }
                    }

                    if ( ( all_empty && is_gb ) || 'show' === show_or_hide ) {
                        for ( var j in gazchaps_getaddress_io.fields_to_hide ) {
                            var field = prefix + '_' + gazchaps_getaddress_io.fields_to_hide[j];
                            var fieldContainer = document.getElementById( field + '_field' );
                            if ( fieldContainer ) {
                                fieldContainer.style.display = ( 'hide' == show_or_hide ) ? 'none' : '';
                            }
                        }
                        if ( 'hide' === show_or_hide ) {
                            show_or_hide_enter_manually_button( prefix, 'show' );
                        }
                    } else if ( 'hide' === show_or_hide ) {
                        show_or_hide_enter_manually_button( prefix, 'hide' );
                    }
                }
            }
        }
    }

    function show_or_hide_enter_manually_button( type, show_or_hide ) {
        var enter_manually_row_id = type + '_gazchaps_getaddress_io_enter_address_manually_button_field';
        var enter_manually_row = document.getElementById( enter_manually_row_id );
        if ( enter_manually_row ) {
            enter_manually_row.style.display = ( 'hide' == show_or_hide ) ? 'none' : '';
        }
    }

    function hide_address_fields( type ) {
        show_or_hide_address_fields( type, 'hide' );
    }

    function show_address_fields( type ) {
        show_or_hide_address_fields( type, 'show' );
        show_or_hide_enter_manually_button( type, 'hide' );
    }

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

    function enter_manually_button_clicked( btn ) {
        var address_type = '';
        if ( btn.id.indexOf('billing_') > -1 ) {
            address_type = 'billing';
        } else if( btn.id.indexOf('shipping_') > -1 ) {
            address_type = 'shipping';
        }

        if ( address_type ) {
            show_address_fields( address_type );
            btn.closest('.form-row').style.display = 'none';
        }
    }

    function do_postcode_lookup( postcode, address_type, btn ) {
        if ( typeof postcode_lookup_cache[ postcode + '_' + address_type ] != 'undefined' ) {
            tidy_postcode_field( postcode_lookup_cache[ postcode + '_' + address_type ] );
            show_address_selector( postcode_lookup_cache[ postcode + '_' + address_type ] );
            return;
        }

        var ajax_data;
        ajax_data = "action=gazchaps_woocommerce_getaddress_io";
        ajax_data += "&postcode=" + postcode;
        ajax_data += "&address_type=" + address_type;

        if ( XMLHttpRequest ) {
            if ( btn ) {
                btn.innerText = gazchaps_getaddress_io.searching_text;
                btn.disabled = true;
            }
            var xhr = new XMLHttpRequest();

            xhr.open('POST', gazchaps_getaddress_io.ajax_url );
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if ( btn ) {
                    btn.innerText = gazchaps_getaddress_io.button_text;
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
                    btn.innerText = gazchaps_getaddress_io.button_text;
                    btn.disabled = false;
                }
            };
            xhr.send( ajax_data );
        }
    }

    function tidy_postcode_field( r ) {
        var postcode_field = document.getElementById( r.address_type + '_postcode' );
        if ( postcode_field && r.postcode ) {
            postcode_field.value = r.postcode;
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
        if ( !address ) address = '||||';

        var address_parts = address.split('|');
        var address_field_ids = [ 'address_1', 'address_2', 'city', 'state' ];
        var field_id, field_element;
        for (var i = 0; i < address_field_ids.length; i++) {
            field_id = address_type + '_' + address_field_ids[ i ];
            field_element = document.getElementById( field_id );
            if ( field_element !== null ) {
                field_element.value = address_parts[ i ];
            }
        }
        if ( '||||' !== address ) {
            show_address_fields( address_type );
        }

        // trigger WooCommerce's Ajax order update so we get shipping methods updated etc.
        jQuery( document.body ).trigger( 'update_checkout' );
    }

    jQuery( document ).on( 'click', '.gazchaps-getaddress-io-lookup-button', function () {
        lookup_button_clicked( this );
    } );

    jQuery( document ).on( 'click', '.gazchaps-getaddress-io-enter-address-manually-button', function () {
        enter_manually_button_clicked( this );
    } );

    // add event listeners for the selectors
    jQuery( document ).on( 'change', '#billing_gazchaps-woocommerce-getaddress-io-address-selector-select, #shipping_gazchaps-woocommerce-getaddress-io-address-selector-select', function (e) {
        var val = e.target.options[e.target.selectedIndex].value;
        if ( '' == val ) {
            val = '||||';
        }
        do_address_change( e.target.id, val );
    } );

    // add event listener to disable enter key on the postcode fields
    jQuery( document ).on( 'keydown', '.billing_gazchaps_getaddress_io_postcode_field input, .shipping_gazchaps_getaddress_io_postcode_field input', function (e) {
        if ( e.defaultPrevented ) return;
        var activateFindAddressButton = false;
        if ( e.key !== undefined ) {
            if ( 'Enter' === e.key ) {
                activateFindAddressButton = true;
            }
        } else if ( e.keyCode !== undefined ) {
            if ( 13 === e.keyCode ) {
                activateFindAddressButton = true;
            }
        }
        if ( activateFindAddressButton ) {
            var $btn;
            if ( jQuery( this ).attr('name').indexOf('billing_') > -1 ) {
                $btn = jQuery( '#billing_gazchaps_getaddress_io_postcode_lookup_button_field_button' );
            } else {
                $btn = jQuery( '#shipping_gazchaps_getaddress_io_postcode_lookup_button_field_button' );
            }
            if ( $btn ) {
                $btn.trigger( 'click' );
            }
            e.preventDefault();
            return false;
        }
    } );

    // if we're on the WC checkout, add a clearfix to the additional fields wrapper
    if ( gazchaps_getaddress_io.clear_additional_fields ) {
        var additional_fields_wrappers = document.getElementsByClassName('woocommerce-additional-fields__field-wrapper');
        if ( additional_fields_wrappers.length > 0 ) {
            for(var i = 0; i < additional_fields_wrappers.length; i++) {
                additional_fields_wrappers[i].style.clear = 'both';
            }
        }
    }

    hide_address_fields();
    jQuery( document ).on( 'updated_checkout', function () {
        hide_address_fields();
    } );

})(jQuery);
