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
            notifyApp.subscribeList(this._onLoadSubscribeList, this);
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
                if (subscribe.get('module') === 'forum'){
                    if (subscribe.get('type') === '' && subscribe.get('ownerid') === 0){
                        this.set('globalSubscribe', subscribe);
                    } else {
                        topicids[topicids.length] = subscribe.get('ownerid');
                    }
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
                globalSubscribe = this.get('globalSubscribe');

            this.globalButtonsWidget = new NOTIFY.SubscribeRowButtonWidget({
                srcNode: tp.one('globalButtons'),
                subscribe: globalSubscribe
            });

            this.newTopicButtonsWidget = new NOTIFY.SubscribeRowButtonWidget({
                srcNode: tp.one('newTopicButtons'),
                subscribe: this.get('newTopicSubscribe'),
                changeDisable: !globalSubscribe
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