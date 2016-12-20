(function ($, mmtWizardParams) {
    'use strict';

    $(document).ready(function () {

        $('.button-next').on('click', function () {
            $('.mmt-content').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
            return true;
        });

        /**
         * Feels a little strange creating a new object here, but this is working for now.
         * todo: combine into mmtWizardParams object
         */
        var mmt = {
            init: function () {
                var self = this,
                    p = mmtWizardParams.endpoints.posts.per_page;

                $(document.body).on('click', '.button-migrate-posts', function (e) {
                    e.preventDefault();
                    self.getPosts(mmtWizardParams.apiCallBase, mmtWizardParams.endpoints.posts, { per_page: p } );
                });
            },
            sendData: function (base, endpoint, data) {
                return $.ajax({
                    xhrFields: {
                        withCredentials: true
                    },
                    beforeSend: function (xhr) {
                        // todo: use a nonce
                        // xhr.setRequestHeader('X-WP-Nonce', mmt_wizard_params.nonce);
                        // xhr.setRequestHeader("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, X-WP-Nonce");
                        xhr.setRequestHeader('Authorization', 'Basic ' + btoa('admin:password'));
                    },
                    url: base + endpoint.route,
                    method: endpoint.method,
                    data: data
                })
            },
            getPosts: function (base, endpoint, data) {

                // todo: check/disable heartbeat
                // todo: run xdebug on initial plugin constructor
                // todo: bump memory and batch limit

                var self = this,
                    call = this.sendData(base, endpoint, data, self);

                call.then(function (data) {
                    console.log( data );
                    // if we have posts, we need to import them
                    return self.sendData( mmtWizardParams.apiHomeBase, mmtWizardParams.endpoints.batch, data );
                })
                .done(function (data) {

                    if ( data.conflicted ) {
                        for (var i = data.conflicted.length - 1; i >= 0; i--) {
                            $('.media-migrate-conflicts').append('<li>('+ data.conflicted[i].ID +') - ' + data.conflicted[i].guid + '</li>');
                        }
                    }

                    console.log('batch processed');
                    // console.log( data );

                    if ( data.page > data.total_pages ) {
                        $('.button-migrate-posts')
                            .val('Continue')
                            .removeClass('button-migrate-posts')
                            .addClass('button-next');
                    } else {
                        // call more data if available. Yay recursion!

                        self.getPosts( mmtWizardParams.apiCallBase, mmtWizardParams.endpoints.posts, data );
                    }

                    $('.posts-batch-progress').animate({width: data.percentage + '%'}, 50, "swing");
                });
            }
        };
        mmt.init();

    });

})(jQuery, mmtWizardParams || {});
