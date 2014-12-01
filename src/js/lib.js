/*
 * @package Abricos
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'widget', files: ['lib.js']},
        {name: 'uprofile', files: ['users.js']},
        {name: 'sys', files: ['application.js', 'form.js', 'item.js', 'date.js']},
        {name: '{C#MODNAME}', files: ['model.js']}
    ]
};
Component.entryPoint = function(NS){

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isModer: 40,
        isWrite: 30,
        isView: 10
    });

    var Y = Brick.YUI,

        L = Y.Lang,

        COMPONENT = this,

        SYS = Brick.mod.sys;

    NS.navigator = {
        'ws': "#app={C#MODNAMEURI}/",
        'home': function(){
            return NS.navigator.ws + 'board/showBoardPanel';
        },
        'topic': {
            'view': function(id){
                return NS.navigator.ws + 'topicview/showTopicViewPanel/' + id + '/';
            }
        },
        /**
         * @deprecated
         */
        'go': function(url){
            Brick.Page.reload(url);
        }
    };

    var L = YAHOO.lang,
        R = NS.roles;

    NS.lif = function(f){
        return L.isFunction(f) ? f : function(){
        };
    };
    NS.life = function(f, p1, p2, p3, p4, p5, p6, p7){
        f = NS.lif(f);
        f(p1, p2, p3, p4, p5, p6, p7);
    };

    var buildTemplate = this.buildTemplate;
    buildTemplate({});


    SYS.Application.build(COMPONENT, {
        topicList: {
            response: function(d){
                return new NS.TopicList(d.list);
            }
        }
    }, {
        initializer: function(){
            this.forums = new NS.ForumList();
            this.users = Brick.mod.uprofile.viewer.users;

            this.initCallbackFire();
        },
        ajaxParseResponse: function(data, ret){
            if (data.userList){
                this.users.update(data.userList.list);
            }
        }
    });

    /*
    var Manager = function(callback){
        this.init(callback);
    };
    Manager.prototype = {
        init: function(callback){
            NS.manager = this;


            R.load(function(){
                NS.life(callback, NS.manager);
            });
        },

        _updateTopic: function(d){
            var topic = null;
            if (L.isValue(d) && L.isValue(d['topic'])){
                topic = new NS.Topic(d['topic']);
                this._updateUserList(d);
            }
            return topic;
        },

        topicLoad: function(topicid, callback){
            var __self = this;
            this.ajax({
                'do': 'topic',
                'topicid': topicid
            }, function(d){
                var topic = __self._updateTopic(d);
                NS.life(callback, topic);
            });
        },
        topicSave: function(topic, d, callback){
            callback = callback || function(){
            };
            var __self = this;

            d = Y.merge({
                'id': 0, 'title': '',
                'body': '',
                'files': {}
            }, d || {});

            var dtopic = {
                'id': topic.id,
                'tl': d['title'],
                'bd': d['body'],
                'files': d['files']
            };
            this.ajax({
                'do': 'topicsave',
                'savedata': dtopic
            }, function(d){
                var topic = __self._updateTopic(d);
                callback(topic);
            });
        },
        topicClose: function(topicid, callback){ // закрыть сообщение
            var __self = this;
            this.ajax({
                'do': 'topicclose',
                'topicid': topicid
            }, function(d){
                var topic = __self._updateTopic(d);
                NS.life(callback, topic);
            });
        },
        topicRemove: function(topicid, callback){ // удалить сообщение
            var __self = this;
            this.ajax({
                'do': 'topicremove',
                'topicid': topicid
            }, function(d){
                var topic = __self._updateTopic(d);
                NS.life(callback, topic);
            });
        },

        forumSave: function(forum, d, callback){
            callback = callback || function(){
            };
            var __self = this;

            d = Y.merge({'id': 0, 'title': '', 'body': ''}, d || {});

            var dforum = {
                'id': forum.id,
                'tl': d['title'],
                'bd': d['body']
            };
            this.ajax({
                'do': 'forumsave',
                'forum': dforum
            }, function(r){
                __self._setLoadedTopicData(r);
                callback(r);
            });
        }


    };
    NS.manager = null;

    NS.initManager = function(callback){
        if (L.isNull(NS.manager)){
            NS.manager = new Manager(callback);
        } else {
            NS.life(callback, NS.manager);
        }
    };
    /**/
};