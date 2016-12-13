(function ($, mmt) {
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

        var mmt = {
            api_home_base: 'http://one.merger-multisite.dev/wp-json/mmt/v1/',
            api_call_base: 'http://two.merger-multisite.dev/wp-json/mmt/v1/',
            endpoints: {
                posts: {route: 'posts', method: 'GET'},
                batch: {route: 'posts/batch', method: 'POST'}
            },
            init: function () {
                var self = this;

                $(document.body).on('click', '.button-migrate-posts', function (e) {
                    e.preventDefault();
                    self.getPosts(self.api_call_base, self.endpoints.posts, {per_page: 3}, self);
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
                var self = this,
                    call = this.sendData(base, endpoint, data, self);

                call.then(function (data) {
                    // if we have posts, we need to import them
                    return self.sendData(self.api_home_base, self.endpoints.batch, data, self);
                })
                .done(function (data) {
                    if ( data.page > data.total_pages ) {
                        data.percentage = 100;
                        $('.button-migrate-posts')
                            .val('Continue')
                            .removeClass('button-migrate-posts')
                            .addClass('button-next');
                    } else {
                        // call more data if available. Yay recursion!
                        self.getPosts(self.api_call_base, self.endpoints.posts, data, self);
                    }
                    $('.posts-batch-progress').animate({width: data.percentage + '%'}, 50, "swing");
                });
            }
        };
        mmt.init();

    });

})(jQuery, mmt_wizard_params || {});
