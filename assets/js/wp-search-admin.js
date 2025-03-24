( function ( $ ) {
    'use strict';
    $( document ).ready( function () {

        window.AdminSearchSetting = {

            /**
             * Initialize functions
             */
            init: function () {
                this.addNewSetting();
                this.deleteSetting();
                this.viewSetting();
                this.editSetting();
            },

            /**
             * Add new setting and store it into database
             */
            addNewSetting: function () {

                let modal      = $( "#searchModal" );
                let openModal  = $( "#openModal" );
                let closeModal = $( "#closeModal" );
                let modalForm  = $( "#searchForm" );
                let msgBox     = $( ".message-box" );

                openModal.on( "click", function () {
                    modal.css( "display", "flex" );
                });

                closeModal.on( "click", function ( event ) {
                    event.preventDefault();
                    modal.css( "display", "none" );
                    modalForm.trigger( "reset" );
                    msgBox.html( '' ).hide();
                });

                modalForm.on( 'submit', function ( e ) {
                    e.preventDefault();

                    let formData = {
                        action: 'wp_search_setting',
                        nonce: WP_SEARCH_SETTING.nonce_add,
                        place_holder: $( '#place_holder' ).val(),
                        class: $( '#class' ).val(),
                        type: $( 'input[name="type"]:checked' ).val(),
                        post_type: $( 'input[name="post_type[]"]:checked' ).map( function () {
                            return $( this ).val();
                        } ).get()
                    };

                    $.ajax({
                        type: 'POST',
                        url: WP_SEARCH_SETTING.ajaxurl,
                        data: formData,
                        dataType: 'json',
                        success: function ( response ) {
                            modalForm.trigger( "reset" );
                            modal.css("display", "none" );
                            location.reload();
                            AdminSearchSetting.displayMessage( response.success, response.data.message );
                        },
                        error: function () {
                            modal.css( "display", "none" );
                            AdminSearchSetting.displayMessage( false, "Something went wrong. Please try again.");
                        }
                    });
                });
            },


            /**
             * Edit the specific setting
             */
            editSetting: function( entry ) {

                if ( !entry ) return;
        
                $( ".editModelId" ).text( entry.id );
                $( "#edit-place_holder" ).val( entry.place_holder );
                $( "#edit-class" ).val( entry.class );
            
                $( 'input[name="edit-type"]' ).prop( "checked", false );
                $( 'input[name="edit-type"][value="' + entry.type + '"]' ).prop( "checked", true );
            
                $( 'input[name="edit-post_type[]"]' ).prop( "checked", false );
                if ( entry.post_type ) {
                    let selectedPostTypes = entry.post_type.split( "," );
                    $( 'input[name="edit-post_type[]"]' ).each( function () {
                        if ( selectedPostTypes.includes( $ ( this ).val() ) ) {
                            $( this ).prop( "checked", true );
                        }
                    });
                }

                $( "#editSearchModal" ).css( "display", "flex" );

                $( "#editSearchForm" ).on( "submit", function( e ) {
                    e.preventDefault();
            
                    let formData = {
                        action: "wp_search_setting_edit",
                        nonce: WP_SEARCH_SETTING.nonce_edit,
                        id: entry.id,
                        place_holder: $( "#edit-place_holder" ).val(),
                        class: $( "#edit-class" ).val(),
                        type: $( 'input[name="edit-type"]:checked' ).val(),
                        post_type: $( 'input[name="edit-post_type[]"]:checked' ).map( function() {
                            return $( this ).val();
                        } ).get()
                    };
            
                    $.ajax( {
                        type: "POST",
                        url: WP_SEARCH_SETTING.ajaxurl,
                        data: formData,
                        dataType: "json",
                        success: function( response ) {
                            $( "#editSearchModal" ).css( "display", "none" );
                            location.reload();
                            AdminSearchSetting.displayMessage( response.success, response.data.message );
                        },
                        error: function() {
                            $( "#editSearchModal" ).css( "display", "none" );
                            AdminSearchSetting.displayMessage( false, "Something went wrong. Please try again." );
                        }
                    });

                });
            
                $( "#closeEditModal" ).on( "click", function( e ) {
                    e.preventDefault();
                    $( "#editSearchModal" ).css( "display", "none" );
                    $( "#edit-place_holder, #edit-class" ).val( "" );
                    $( 'input[name="edit-type"]' ).prop( "checked", false );
                    $( 'input[name="edit-post_type[]"]' ).prop( "checked", false );
                });
            },
            
            /**
             * View the specific setting
             */
            viewSetting: function( entry ) {

                if ( !entry ) return;

                $( "#view-model-id" ).text( entry.id );
                $( "#view-model-place_holder" ).val( entry.place_holder );
                $( "#view-model-class" ).val( entry.class );
                $( "#view-model-type" ).val( entry.type );
            
                let postTypeDisplay = $( ".post-type-display" );
                if ( postTypeDisplay.length ) {
                    postTypeDisplay.text( entry.post_type.split( ',' ).join( ', ' ) );
                }
            
                $( "#viewModal" ).css( "display", "flex" );
            
                $( "#closeViewModal" ).on( "click", function(e) {
                    e.preventDefault();
                    $( "#viewModal" ).css( "display", "none" );
                    $( "#view-model-id, #view-model-place_holder, #view-model-class, #view-model-type" ).val( "" );
                });
            },

            /**
             * Delete the specific setting 
             */
            deleteSetting: function () {

                $( '.delete-setting' ).on( 'click', function ( e ) {

                    e.preventDefault();
                    let id = $(this).data( 'id' );

                    if ( confirm( 'Are you sure you want to delete this setting?' ) ) {
                        $.ajax({
                            type: 'POST',
                            url: WP_SEARCH_SETTING.ajaxurl,
                            data: {
                                action: 'wp_search_setting_delete',
                                nonce: WP_SEARCH_SETTING.nonce_delete,
                                id: id
                            },
                            success: function ( response ) {
                                location.reload();
                                AdminSearchSetting.displayMessage( response.success, response.data.message );
                            },
                            error: function () {
                                AdminSearchSetting.displayMessage( false, "Something went wrong. Please try again." );
                            }
                        });
                    }
                });
            },

            /**
             * Display success or error messages
             */
            displayMessage: function ( isSuccess, message ) {

                let msgBox = $( ".message-box" );
                let alertClass = isSuccess ? "alert-success" : "alert-danger";
                msgBox.html( '<div class="alert ' + alertClass + '">' + message + '</div>' ).fadeIn();

                setTimeout( () => {
                    msgBox.fadeOut(500, function () {
                        $( this ).html( '' );
                    });
                }, 5000 );
            }
        };
        AdminSearchSetting.init();
    });

})( jQuery );
