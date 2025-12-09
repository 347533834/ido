define(function (require, exports, modules) {
    'use strict';

    require('css!assets/css/team/team.css');
    require('assets/plugins/md5.js');
    var template = require('text!./team.tpl.html'),
        util = require('util');

    var View = Backbone.View.extend({
        el: '#page',
        events: {
            'click .more': function () {
                this.LoadFriend(this.page);
            },
        },
        initialize: function () {
            this.template = _.template(template);
            this.render();
        },
        render: function () {
            this.$el.html(this.template());
            $('header span').text("我的直推");
            this.loadUser();
        },
        loadUser: function () {
            this.user = window.user.get();
            if (!this.user) {
                Backbone.history.navigate('loading', {trigger: true});
                return;
            }
            this.page = 1;
            this.LoadFriend(this.page);
        },
        LoadFriend: function (p) {
            var _this = this;
            var page = p ? p : 1;
            window.jsonpGet('Invite/LoadFriend', {
                token: this.user.token,
                page: page,
            }, 'LoadFriend', function (json) {
                // if (json.data.team_total) {
                //     $('#team_total').text(parseFloat(json.data.team_total) + ' USDT');
                // }
                if (json.code == 1) {
                    var data = json.data.data, str = '', status;
                    for (var i = 0; i < data.length; i++) {
                        var dt = new Date(data[i].addtime * 1000);
                        str += '  <dd><span>' + data[i].username + '</span><span>' + data[i].mobile.substr(0, 3) + '****' + data[i].mobile.substr(data[i].mobile.length - 4) + '</span><span>' + (data[i].id_name ? data[i].id_name : '-') + '</span><span>' + (data[i].status == 1 ? '已认证' : '未认证') + '</span><span>' + dt.format('mm-dd') + '</span></dd>';
                    }
                    $('#Friend').append(str);
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
            })
        },
    });
    return View;
});

