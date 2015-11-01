var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'notify', files: ['button.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var NOTIFY = Brick.mod.notify;

    NS.SubscribeWidget = Y.Base.create('subscribeWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            var notifyApp = this.get('appInstance').getApp('notify');
            notifyApp.subscribeList('forum', this._onLoadSubscribeList, this);
        },
        destructor: function(){
        },
        _onLoadSubscribeList: function(err, result){
            if (err){
                this.set('waiting', false);
                return;
            }
            var subscribeList = result.subscribeList;

            this.set('subscribeList', subscribeList);
            var topicids = [];

            subscribeList.each(function(subscribe){
                var owner = subscribe.get('owner');
                if (owner && owner.get('module') === 'forum'
                    && owner.get('type') === 'topic'
                    && owner.get('method') === 'comment'){

                    topicids[topicids.length] = owner.get('itemid');
                }
            }, this);

            this.get('appInstance').topicListByIds(topicids, this._onLoadTopicList, this);
        },
        _onLoadTopicList: function(err, result){
            this.set('waiting', false);
            if (err){
                return;
            }
            this.set('topicList', result.topicList);

            var tp = this.template,
                appInstance = this.get('appInstance'),
                subscribeList = this.get('subscribeList'),
                ownerBaseList = appInstance.getApp('notify').get('ownerBaseList'),
                ownerForum = ownerBaseList.findOwner({module: 'forum'}),
                ownerTopicNew = ownerBaseList.findOwner({module: 'forum', type: 'topic', method: 'new'}),
                ownerTopicComment = ownerBaseList.findOwner({module: 'forum', type: 'topic', method: 'comment'});

            var globalSubscribe = this.get('globalSubscribe');

            this.globalButtonsWidget = new NOTIFY.SubscribeRowButtonWidget({
                srcNode: tp.one('globalButtons'),
                subscribe: subscribeList.getSubscribe(NS.SUBSCRIBE.module())
            });

            this.newTopicButtonsWidget = new NOTIFY.SubscribeRowButtonWidget({
                srcNode: tp.one('newTopicButtons'),
                subscribe: subscribeList.getSubscribe(NS.SUBSCRIBE.topicNew())
            });
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            globalSubscribe: {},
            subscribeList: {},
            topicList: {}
        },
        CLICKS: {}
    });

    NS.SubscribeWidget.parseURLParam = function(args){
        return {};
    };

};