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
                call = this.sendData(base, endpoint, data),
                progress = $('.posts-batch-progress');

            call.done( function( response ){

                $('.page-num').html(response.page);
                $('.page-total').html(' of ' +response.total_pages);

                if (response.page > response.total_pages) {
                    $('.button-migrate-posts')
                        .val('Continue')
                        .removeClass('button-migrate-posts')
                        .addClass('button-next')
                        .prop("disabled", false);
                } else {
                    // call more data if available. Yay recursion!
                    self.getPosts(mmtWizardParams.apiHomeBase, mmtWizardParams.endpoints.batch, response);
                }

                progress.animate({width: response.percentage + '%'}, 50 );
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
            var self = this;
            $('.button-next').on( 'click', this.pageBlock );

            $(document.body).on('click', '.button-migrate-posts', function (e) {
                e.preventDefault();

                $('.mmt-actions .button').prop( "disabled", true );
                $('.button-migrate-posts')
                    .prop("disabled", true)
                    .val('Migrating');

                var base = mmtWizardParams.apiHomeBase,
                    batch = mmtWizardParams.endpoints.batch,
                    perPage = mmtWizardParams.endpoints.batch.per_page;
                self.getPosts( base, batch, { per_page: perPage, page: 1 } );
            });
        }
    };

    $(document).ready(function () {
        mmt.init();
    });

})(jQuery, mmtWizardParams || {});
