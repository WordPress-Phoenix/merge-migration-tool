(function ($, mmtWizardParams) {
    'use strict';

    var mmt = {
        sendData: function (base, endpoint, data) {
            return $.ajax({
                url: base + endpoint.route,
                method: endpoint.method,
                data: data
            })
        },
        getPosts: function (base, endpoint, data) {
            var self = this,
                call = this.sendData(base, endpoint, data, self),
                button = $('.posts-batch-progress');

            call.done( function( response ){
                // console.log( response );
                button.animate({width: response.percentage + '%'}, 50, "swing");

                // todo: Maybe add conflicted to screen or tab in admin
                //     if ( data.conflicted ) {
                //         for (var i = data.conflicted.length - 1; i >= 0; i--) {
                //             $('.media-migrate-conflicts').append('<li>('+ data.conflicted[i].ID +') - ' + data.conflicted[i].guid + '</li>');
                //         }
                //     }

                if (data.page > data.total_pages) {
                    $('.button-migrate-posts')
                        .val('Continue')
                        .removeClass('button-migrate-posts')
                        .addClass('button-next');
                } else {
                    // call more data if available. Yay recursion!
                    self.getPosts(mmtWizardParams.apiHomeBase, mmtWizardParams.endpoints.batch, response);
                }

                button.animate({width: data.percentage + '%'}, 50, "swing");
            });

        },
        pageBlock: function () {
            $('.mmt-content').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
            return true;
        },
        init: function () {
            var self = this,
                p = mmtWizardParams.endpoints.posts.per_page;

            $('.button-next').on( 'click', this.pageBlock );

            $(document.body).on('click', '.button-migrate-posts', function (e) {
                e.preventDefault();
                console.log(mmtWizardParams );
                self.getPosts( mmtWizardParams.apiHomeBase, mmtWizardParams.endpoints.batch, { per_page: 1, page: 1 } );
            });
        }
    };

    $(document).ready(function () {
        mmt.init();
    });

})(jQuery, mmtWizardParams || {});
