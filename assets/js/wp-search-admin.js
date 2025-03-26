( function ( $ ) {
    'use strict';
    $( document ).ready( function () {

        let AdminSearchSetting = {

            /**
             * Initialize functions
             */
            init: function () {

                this.cacheSelectors();
                this.bindEvents();
            },

            /**
             * Cache DOM selectors
             */
            cacheSelectors: function () {
                
                this.createtable      = $( ".create-table" );

                this.addBtn           = $( "#openModal" );
                this.editBtn          = $( ".edit-setting" );
                this.deleteBtn        = $( ".delete-setting" );

                this.modal            = $( "#searchModal" );
                this.closeModal       = $( "#closeModal" );
                this.modalTitle       = $( ".model-title" );
                this.modelPlaceHolder = $( "#place_holder" );
                this.modelClass       = $( "#class" );
                this.modelType        = $( 'input[name="type"]' );
                this.modelPostType    = $( 'input[name="post_type[]"]' );
                this.modelBtn         = $( ".model-btn" );
                this.modalForm        = $( "#searchForm" );
            },

            /**
             * Bind events
             */
            bindEvents: function () {
                
                this.addBtn.on( "click", this.addNewSetting.bind( this ) );
                this.editBtn.on( "click", this.editSetting.bind( this ) );
                this.deleteBtn.on( "click", this.deleteSetting.bind( this ) );
                this.closeModal.on( "click", this.closeModalHandler.bind( this ) );
                this.createtable.on( "click", this.createDatabaseTable.bind( this ) );
            },

            /**
             * Add new setting and store it into database
             */
            addNewSetting: function () {

                this.modalForm.trigger( "reset" );
                this.modalTitle.text( WP_SEARCH_SETTING.add_title );
                this.modelBtn.val( WP_SEARCH_SETTING.add_btn );

                this.modalForm.off( 'submit' ).on( 'submit', function ( e ) {
                    e.preventDefault();

                    let formData = {
                        action: 'wp_search_setting',
                        nonce: WP_SEARCH_SETTING.nonce_add,
                        place_holder: AdminSearchSetting.modelPlaceHolder.val(),
                        class: AdminSearchSetting.modelClass.val(),
                        type: $( 'input[name="type"]:checked' ).val(),
                        post_type: $( 'input[name="post_type[]"]:checked' ).map( function () {
                            return $( this ).val();
                        } ).get()
                    };

                    $.ajax( {
                        type: 'POST',
                        url: WP_SEARCH_SETTING.ajaxurl,
                        data: formData,
                        dataType: 'json',
                        success: function ( response ) {
                            AdminSearchSetting.modalForm.trigger( "reset" );
                            AdminSearchSetting.modal.css( "display", "none" );
                            location.reload();
                            AdminSearchSetting.displayMessage( response.success, response.data.message );
                        },
                        error: function () {
                            AdminSearchSetting.modal.css( "display", "none" );
                            AdminSearchSetting.displayMessage( false, WP_SEARCH_SETTING.error_msg );
                        }
                    });
                });

                this.openModal();
            },

            /**
             * Edit the specific setting
             */
            editSetting: function ( event ) {

                let entry = JSON.parse( $( event.currentTarget ).attr( "data-entry" ) );

                this.modalTitle.text( WP_SEARCH_SETTING.setting_id + entry.id );
                this.modelPlaceHolder.val( entry.place_holder );
                this.modelClass.val( entry.class );

                $( 'input[name="type"]' ).prop( "checked", false );
                $( 'input[name="type"][value="' + entry.type + '"]' ).prop( "checked", true );

                this.modelPostType.prop( "checked", false );
                if ( entry.post_type ) {
                    let selectedPostTypes = entry.post_type.split( "," );
                    this.modelPostType.each( function () {
                    if ( selectedPostTypes.includes( $( this ).val() ) ) {
                        $( this ).prop( "checked", true );
                    }
                    });
                }

                this.modelBtn.val( WP_SEARCH_SETTING.edit_btn );

                this.modalForm.off( "submit" ).on( "submit", function ( e ) {

                    e.preventDefault();

                    let formData = {
                        action: "wp_search_setting_edit",
                        nonce: WP_SEARCH_SETTING.nonce_edit,
                        id: entry.id,
                        place_holder: AdminSearchSetting.modelPlaceHolder.val(),
                        class: AdminSearchSetting.modelClass.val(),
                        type: $( 'input[name="type"]:checked' ).val(),
                        post_type: $( 'input[name="post_type[]"]:checked' ).map( function () {
                            return $( this ).val();
                        } ).get()
                    };

                    $.ajax( {
                        type: "POST",
                        url: WP_SEARCH_SETTING.ajaxurl,
                        data: formData,
                        dataType: "json",
                        success: function ( response ) {
                            AdminSearchSetting.modal.css( "display", "none" );
                            location.reload();
                            AdminSearchSetting.displayMessage( response.success, response.data.message );
                        },
                        error: function () {
                            AdminSearchSetting.modal.css( "display", "none" );
                            AdminSearchSetting.displayMessage( false, WP_SEARCH_SETTING.error_msg );
                        }
                    });
                });

                this.openModal();
            },

            /**
             * Delete the specific setting
             */
            deleteSetting: function ( event ) {

                event.preventDefault();
                let id = $( event.currentTarget ).data( 'id' );

                if ( confirm( WP_SEARCH_SETTING.confirm_msg ) ) {
                    $.ajax( {
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
                            AdminSearchSetting.displayMessage( false, WP_SEARCH_SETTING.error_msg ); 
                        }
                    });
                }
            },

            /**
             * Display success or error messages
             */
            displayMessage: function ( isSuccess, message ) {

                let msgBox = $( ".message-box" );
                let alertClass = isSuccess ? "alert-success" : "alert-danger";
                msgBox.html( '<div class="alert ' + alertClass + '">' + message + '</div>' ).fadeIn();

                setTimeout( () => {
                    msgBox.fadeOut( 500, function () {
                    $( this ).html( '' );
                    });
                }, 5000 );
            },

            /**
             * Open the modal form
             */
            openModal: function () {
                this.modal.css( "display", "flex" );
            },

            /**
             * Close the modal form
             */
            closeModalHandler: function ( event ) {

                event.preventDefault();
                this.modal.css( "display", "none" );
                this.modalForm.trigger( "reset" );
                this.modelPlaceHolder.prop( 'disabled', false );
                this.modelClass.prop( 'disabled', false );
                this.modelType.prop( 'disabled', false )
                this.modelPostType.prop( 'disabled', false )
                this.modelBtn.show();
            },

            /**
             * Create Database table on click
             */
            createDatabaseTable: function(){

                $.ajax( {
                    url: WP_SEARCH_SETTING.ajaxurl,
                    type: "POST",
                    data: {
                        action: "create_database_table",
                        nonce: WP_SEARCH_SETTING.nonce_table,
                    },
                    success: function ( response ) {
                        location.reload();
                        AdminSearchSetting.displayMessage( response.success, response.data.message );
                    },
                    error: function () {
                        AdminSearchSetting.displayMessage( false, WP_SEARCH_SETTING.error_msg ); 
                    }
                });
            }

        };

        AdminSearchSetting.init();
    });
})( jQuery );