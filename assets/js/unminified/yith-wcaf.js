jQuery(document).ready( function( $ ){

    /*
     * Get enhanced select labels
     *
     * @use yith_wcaf
     * @return mixed
     */
    function getEnhancedSelectFormatString() {
        var formatString = {
            formatMatches: function( matches ) {
                if ( 1 === matches ) {
                    return yith_wcaf.labels.select2_i18n_matches_1;
                }

                return yith_wcaf.labels.select2_i18n_matches_n.replace( '%qty%', matches );
            },
            formatNoMatches: function() {
                return yith_wcaf.labels.select2_i18n_no_matches;
            },
            formatAjaxError: function( jqXHR, textStatus, errorThrown ) {
                return yith_wcaf.labels.select2_i18n_ajax_error;
            },
            formatInputTooShort: function( input, min ) {
                var number = min - input.length;

                if ( 1 === number ) {
                    return yith_wcaf.labels.select2_i18n_input_too_short_1;
                }

                return yith_wcaf.labels.select2_i18n_input_too_short_n.replace( '%qty%', number );
            },
            formatInputTooLong: function( input, max ) {
                var number = input.length - max;

                if ( 1 === number ) {
                    return yith_wcaf.labels.select2_i18n_input_too_long_1;
                }

                return yith_wcaf.labels.select2_i18n_input_too_long_n.replace( '%qty%', number );
            },
            formatSelectionTooBig: function( limit ) {
                if ( 1 === limit ) {
                    return yith_wcaf.labels.select2_i18n_selection_too_long_1;
                }

                return yith_wcaf.labels.select2_i18n_selection_too_long_n.replace( '%qty%', limit );
            },
            formatLoadMore: function( pageNumber ) {
                return yith_wcaf.labels.select2_i18n_load_more;
            },
            formatSearching: function() {
                return yith_wcaf.labels.select2_i18n_searching;
            }
        };

        return formatString;
    }

    /*
     * Extends jQuery object to implement commissions dashboard view js functions
     *
     * @use yith_wcaf
     */
    $.fn.yith_wcaf_dashboard_commissions = function(){
        var t = $(this);

        // Ajax product search box
        t.find( ':input.wc-product-search' ).filter( ':not(.enhanced)' ).each( function() {
            var select2_args = {
                allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
                placeholder: $( this ).data( 'placeholder' ),
                minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '3',
                escapeMarkup: function( m ) {
                    return m;
                },
                ajax: {
                    url:         yith_wcaf.ajax_url,
                    dataType:    'json',
                    quietMillis: 250,
                    data: function( term, page ) {
                        return {
                            term:     term,
                            action:   'yith_wcaf_json_search_products_and_variations',
                            security: yith_wcaf.search_products_nonce
                        };
                    },
                    results: function( data, page ) {
                        var terms = [];
                        if ( data ) {
                            $.each( data, function( id, text ) {
                                terms.push( { id: id, text: text } );
                            });
                        }
                        return { results: terms };
                    },
                    cache: true
                }
            };

            select2_args.multiple = false;
            select2_args.initSelection = function( element, callback ) {
                var data = {id: element.val(), text: element.attr( 'data-selected' )};
                return callback( data );
            };

            select2_args = $.extend( select2_args, getEnhancedSelectFormatString() );

            $( this ).select2( select2_args ).addClass( 'enhanced' );
        });

        // datepicker
        t.find( '.datepicker').datepicker({
            dateFormat: "yy-mm-dd",
            numberOfMonths: 1,
            showButtonPanel: true,
            beforeShow: function(input, inst) {
                $('#ui-datepicker-div')
                    .removeClass(function() {
                        return $('input').get(0).id;
                    })
                    .addClass( 'yith-wcaf-datepicker' );
            }
        });
    };

    /*
     * Extends jQuery object to implement clicks dashboard view js functions
     *
     * @use yith_wcaf
     */
    $.fn.yith_wcaf_dashboard_clicks = function(){
        var t = $(this);

        // datepicker
        t.find( '.datepicker').datepicker({
            dateFormat: "yy-mm-dd",
            numberOfMonths: 1,
            showButtonPanel: true,
            beforeShow: function(input, inst) {
                $('#ui-datepicker-div')
                    .removeClass(function() {
                        return $('input').get(0).id;
                    })
                    .addClass( 'yith-wcaf-datepicker' );
            }
        });
    };

    /*
     * Extends jQuery object to implement payments dashboard view js functions
     *
     * @use yith_wcaf
     */
    $.fn.yith_wcaf_dashboard_payments = function(){
        var t = $(this);

        // datepicker
        t.find( '.datepicker').datepicker({
            dateFormat: "yy-mm-dd",
            numberOfMonths: 1,
            showButtonPanel: true,
            beforeShow: function(input, inst) {
                $('#ui-datepicker-div')
                    .removeClass(function() {
                        return $('input').get(0).id;
                    })
                    .addClass( 'yith-wcaf-datepicker' );
            }
        });
    };

    /*
     * Extends jQuery object to implement set referrer form behaviour
     *
     * @use yith_wcaf
     */
    $.fn.yith_wcaf_set_referrer = function(){
        var t = $(this);

        t.on( 'click', 'a.show-referrer-form', function(ev){
            ev.preventDefault();

            t.find('form').slideToggle();
        } );

        t.on( 'submit', 'form.referrer-form', function(ev){
            var form = $(this),
                data = {
                    action:		    'yith_wcaf_set_referrer',
                    referrer_token:	form.find( 'input[name="referrer_code"]' ).val(),
                    security:       yith_wcaf.set_referrer_nonce
                };

            ev.preventDefault();

            form.addClass( 'processing' ).block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            $.ajax({
                type:		'POST',
                url:		yith_wcaf.ajax_url,
                data:		data,
                success:	function( code ) {
                    $( '.woocommerce-error, .woocommerce-message' ).remove();
                    form.removeClass( 'processing' ).unblock();

                    if ( code ) {
                        form
                            .before( code )
                            .find( 'input[name="referrer_code"]' ).prop( 'disabled' );
                        form.slideUp();
                    }
                },
                dataType: 'html'
            });

            return false;
        } );
    };

    $( '.yith-wcaf-commissions').yith_wcaf_dashboard_commissions();
    $( '.yith-wcaf-clicks').yith_wcaf_dashboard_clicks();
    $( '.yith-wcaf-payments').yith_wcaf_dashboard_payments();
    $( '.yith-wcaf-set-referrer').yith_wcaf_set_referrer();
} );