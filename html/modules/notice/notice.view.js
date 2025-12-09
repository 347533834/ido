define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/notice/notice.css');
    var template = require('text!./notice.tpl.html'),
        util = require('util');

    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .more': function () {
                this.LoadNotice(this.page);
            }
        },
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            $('header span').text("系统公告");
            this.loadUser();
        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('loading', {trigger: true});
                return;
            }
            this.page = 1;
            this.LoadNotice(this.page);
        },
        LoadNotice: function (p) {
            var _this = this;
            var page = p ? p : 1;
            window.jsonpGet('user/LoadNotice', {
                token: this.user.token,
                page: page,
            }, 'LoadNotice', function (json) {
                if (json.code == 1) {
                    var data = json.data.data, str = '';
                    for (var i = 0; i < data.length; i++) {
                        var dt = new Date(data[i].addtime * 1000);
                        str += ' <li class="details" data-id="' + data[i].id + '">' +
                            '<div><p><span>' + dt.format('yyyy-mm-dd') + '</span></p></div>' +
                            '<div style="white-space: nowrap;"><img src="assets/img/login/img-3.png" style="position: absolute;margin-top: 1.65rem;"><p style="overflow: hidden;text-overflow: ellipsis;white-space: nowrap;width: 100%;margin-left: .5rem;">' + data[i].title + '</p></div>' +
                            '</li>';
                    }
                    $('.notice-list').append(str);
                    if (json.data.current_page >= json.data.last_page) {
                        $('.more').hide();
                    } else {
                        $('.more').show();
                    }
                    _this.page++;
                    if (json.data.total < 1) {
                        $('.no_data').show();
                    }
                }

                $('.notice .details').click(function () {
                    var id = $(this).data('id');
                    Backbone.history.navigate('notice-details?id=' + id, {trigger: true});
                });

            })
        },
    });
    return View;
});

