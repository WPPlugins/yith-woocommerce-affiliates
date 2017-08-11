jQuery( document ).ready( function($){
    // panel dependencies handling
    var general_referral_cod = $( '#yith_wcaf_general_referral_cod' ),
        referral_var_name = $( '#yith_wcaf_referral_var_name' ),
        history_cookie_enabled = $( '#yith_wcaf_history_cookie_enable' ),
        history_cookie_name = $( '#yith_wcaf_history_cookie_name'),
        history_make_cookie_expire = $( '#yith_wcaf_history_make_cookie_expire'),
        history_cookie_expire = $( '#yith_wcaf_history_cookie_expire'),
        commission_persistent_calculation = $( '#yith_wcaf_commission_persistent_calculation'),
        persistent_rate = $( '#yith_wcaf_persistent_rate'),
        avoid_referral_change = $( '#yith_wcaf_avoid_referral_change'),
        referral_make_cookie_expire = $( '#yith_wcaf_referral_make_cookie_expire'),
        referral_cookie_expire = $( '#yith_wcaf_referral_cookie_expire'),
        payment_type = $('#yith_wcaf_payment_type'),
        payment_date = $('#yith_wcaf_payment_date'),
        payment_default_gateway = $('#yith_wcaf_payment_default_gateway'),
        payment_threshold = $('#yith_wcaf_payment_threshold'),
        click_auto_delete = $('#yith_wcaf_click_auto_delete'),
        click_auto_delete_expiration = $('#yith_wcaf_click_auto_delete_expiration');

    general_referral_cod.on( 'change', function(){
        var t = $(this);

        if( t.val() == 'query_string' ){
            referral_var_name.parents( 'tr' ).show();
        }
        else{
            referral_var_name.parents( 'tr' ).hide();
        }
    }).change();

    history_cookie_enabled.on( 'change', function(){
        var t = $(this);

        if( t.is( ':checked' ) ){
            history_cookie_name.parents( 'tr' ).show();
            history_make_cookie_expire.parents( 'tr' ).show();

            if( history_make_cookie_expire.is( ':checked' ) ) {
                history_cookie_expire.parents('tr').show();
            }
        }
        else{
            history_cookie_name.parents( 'tr' ).hide();
            history_cookie_expire.parents( 'tr' ).hide();
            history_make_cookie_expire.parents( 'tr' ).hide();
        }
    }).change();

    history_make_cookie_expire.on( 'change', function(){
        var t = $(this);

        if( t.is( ':checked' ) ){
            history_cookie_expire.parents( 'tr' ).show();
        }
        else{
            history_cookie_expire.parents( 'tr' ).hide();
        }
    }).change();

    commission_persistent_calculation.on( 'change', function(){
        var t = $(this);

        if( t.is( ':checked' ) ){
            persistent_rate.parents( 'tr' ).show();
            avoid_referral_change.parents( 'tr' ).show();
        }
        else{
            persistent_rate.parents( 'tr' ).hide();
            avoid_referral_change.parents( 'tr' ).hide();
        }
    }).change();

    referral_make_cookie_expire.on( 'change', function(){
        var t = $(this);

        if( t.is( ':checked' ) ){
            referral_cookie_expire.parents( 'tr' ).show();
        }
        else{
            referral_cookie_expire.parents( 'tr' ).hide();
        }
    }).change();

    payment_type.on( 'change', function(){
        var t = $(this),
            val = t.val();

        if( val == 'manually' ){
            payment_default_gateway.parents('tr').hide();
            payment_date.parents('tr').hide();
            payment_threshold.parents('tr').hide();
        }
        else if( val == 'automatically_on_threshold' ){
            payment_default_gateway.parents('tr').show();
            payment_date.parents('tr').hide();
            payment_threshold.parents('tr').show();
        }
        else if( val == 'automatically_on_date' ){
            payment_default_gateway.parents('tr').show();
            payment_date.parents('tr').show();
            payment_threshold.parents('tr').hide();
        }
        else if( val == 'automatically_on_both' ){
            payment_default_gateway.parents('tr').show();
            payment_date.parents('tr').show();
            payment_threshold.parents('tr').show();
        }
    }).change();

    click_auto_delete.on( 'change', function(){
        var t = $(this);

        if( t.is( ':checked' ) ){
            click_auto_delete_expiration.parents( 'tr').show();
        }
        else{
            click_auto_delete_expiration.parents( 'tr').hide();
        }
    }).change();

    // rates actions
    $('.yith-affiliates-update-commission').on( 'click', function(ev){
        var t = $(this),
            row = t.parents( 'tr' ),
            affiliate_id = t.data('affiliate_id'),
            rate = row.find( '.column-rate input').val();

        ev.preventDefault();

        $.ajax( {
            beforeSend: function(){
                t.block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
            },
            data: {
                action: 'yith_wcaf_update_affiliate_commission',
                affiliate_id: affiliate_id,
                rate: rate
            },
            dataType: 'json',
            method: 'POST',
            complete: function(){
                t.unblock();
            },
            success: function( data ){

            },
            url: ajaxurl
        } );
    } );

    $('.yith-affiliates-delete-commission').on( 'click', function(ev){
        var t = $(this),
            row = t.parents( 'tr' ),
            table = row.parents('tbody'),
            affiliate_id = t.data('affiliate_id');

        ev.preventDefault();

        $.ajax( {
            beforeSend: function(){
                t.block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
            },
            data: {
                action: 'yith_wcaf_delete_affiliate_commission',
                affiliate_id: affiliate_id
            },
            dataType: 'json',
            method: 'POST',
            complete: function(){
                t.unblock();
            },
            success: function( data ){
                row.remove();

                if( table.find('tr').length == 0 ){
                    table.html( yith_wcaf.empty_row )
                }
            },
            url: ajaxurl
        } );
    } );

    $('.yith-products-update-commission').on( 'click', function(ev){
        var t = $(this),
            row = t.parents( 'tr' ),
            product_id = t.data('product_id'),
            rate = row.find( '.column-rate input').val();

        ev.preventDefault();

        $.ajax( {
            beforeSend: function(){
                t.block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
            },
            data: {
                action: 'yith_wcaf_update_product_commission',
                product_id: product_id,
                rate: rate
            },
            dataType: 'json',
            method: 'POST',
            complete: function(){
                t.unblock();
            },
            success: function( data ){

            },
            url: ajaxurl
        } );
    } );

    $('.yith-products-delete-commission').on( 'click', function(ev){
        var t = $(this),
            row = t.parents( 'tr' ),
            table = row.parents('tbody'),
            product_id = t.data('product_id');

        ev.preventDefault();

        $.ajax( {
            beforeSend: function(){
                t.block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
            },
            data: {
                action: 'yith_wcaf_delete_product_commission',
                product_id: product_id
            },
            dataType: 'json',
            method: 'POST',
            complete: function(){
                t.unblock();
            },
            success: function( data ){
                row.remove();

                if( table.find('tr').length == 0 ){
                    table.html( yith_wcaf.empty_row )
                }
            },
            url: ajaxurl
        } );
    } );

    // commissions actions
    $('#yith_wcaf_commission_notes')
        .on( 'click', 'a.add_note', function(ev){
            var t = $(this),
                sidebar = t.parents( '#yith_wcaf_commission_notes'),
                list = sidebar.find( 'ul'),
                textarea = sidebar.find( 'textarea'),
                note_content = textarea.val(),
                commission_id = $( '#commission_id' ).val();

            ev.preventDefault();

            if( ! note_content ){
                return;
            }

            $.ajax( {
                beforeSend: function(){
                    t.block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                },
                complete: function(){
                    t.unblock();
                },
                data: {
                    commission_id: commission_id,
                    note_content: note_content,
                    action: 'yith_wcaf_add_commission_note'
                },
                method: 'POST',
                success: function(response){
                    if( response.template ) {
                        list.prepend(response.template);
                    }

                    if( list.find('li').length > 0 ){
                        list.find('li.no_notes').hide();
                    }

                    textarea.val( '' );
                },
                url: ajaxurl
            } );
        } )
        .on( 'click', 'a.delete_note', function(ev){
            var t = $(this),
                sidebar = t.parents( '#yith_wcaf_commission_notes'),
                list = sidebar.find( 'ul'),
                li = t.parents( 'li'),
                note_id = li.attr( 'rel' );

            ev.preventDefault();

            if( ! note_id ){
                return;
            }

            $.ajax( {
                beforeSend: function(){
                    li.block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                },
                complete: function(){
                    li.unblock();
                },
                data: {
                    note_id: note_id,
                    action: 'yith_wcaf_delete_commission_note'
                },
                method: 'POST',
                success: function(response){
                    li.remove();

                    if( list.find('li').not('.no_notes').length == 0 ){
                        list.find('li.no_notes').show();
                    }
                },
                url: ajaxurl
            } );
        } );

    // payments actions
    $('#yith_wcaf_payment_notes')
        .on( 'click', 'a.add_note', function(ev){
            var t = $(this),
                sidebar = t.parents( '#yith_wcaf_payment_notes'),
                list = sidebar.find( 'ul'),
                textarea = sidebar.find( 'textarea'),
                note_content = textarea.val(),
                commission_id = $( '#payment_id' ).val();

            ev.preventDefault();

            if( ! note_content ){
                return;
            }

            $.ajax( {
                beforeSend: function(){
                    t.block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                },
                complete: function(){
                    t.unblock();
                },
                data: {
                    payment_id: commission_id,
                    note_content: note_content,
                    action: 'yith_wcaf_add_payment_note'
                },
                method: 'POST',
                success: function(response){
                    if( response.template ) {
                        list.prepend(response.template);
                    }

                    if( list.find('li').length > 0 ){
                        list.find('li.no_notes').hide();
                    }

                    textarea.val( '' );
                },
                url: ajaxurl
            } );
        } )
        .on( 'click', 'a.delete_note', function(ev){
            var t = $(this),
                sidebar = t.parents( '#yith_wcaf_payment_notes'),
                list = sidebar.find( 'ul'),
                li = t.parents( 'li'),
                note_id = li.attr( 'rel' );

            ev.preventDefault();

            if( ! note_id ){
                return;
            }

            $.ajax( {
                beforeSend: function(){
                    li.block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                },
                complete: function(){
                    li.unblock();
                },
                data: {
                    note_id: note_id,
                    action: 'yith_wcaf_delete_payment_note'
                },
                method: 'POST',
                success: function(response){
                    li.remove();

                    if( list.find('li').not('.no_notes').length == 0 ){
                        list.find('li.no_notes').show();
                    }
                },
                url: ajaxurl
            } );
        } );

    // add payment detail behaviour
    $( '.edit_address_button').on( 'click', function(ev){
        var t = $(this);

        ev.preventDefault();

        t.parent().nextAll( '.address').toggle();
        t.parent().nextAll( '.edit_address').toggle();
    } );

    // commissions filter
    $( ".date-picker-field, .date-picker" ).datepicker({
        dateFormat: "yy-mm-dd",
        numberOfMonths: 1,
        showButtonPanel: true
    });

    // add badges to tab headings
    if( yith_wcaf.tabs_badges.commissions != 0 ){
        $( '.nav-tab-wrapper').find( '[href*="commissions"]' ).append( ' <span class="pending-count">' + yith_wcaf.tabs_badges.commissions + '</span>' );
    }
    if( yith_wcaf.tabs_badges.affiliates != 0 ){
        $( '.nav-tab-wrapper').find( '[href*="affiliates"]' ).append( ' <span class="pending-count">' + yith_wcaf.tabs_badges.affiliates + '</span>' );
    }
    if( yith_wcaf.tabs_badges.payments != 0 ){
        $( '.nav-tab-wrapper').find( '[href*="payments"]' ).append( ' <span class="pending-count">' + yith_wcaf.tabs_badges.payments + '</span>' );
    }
} );