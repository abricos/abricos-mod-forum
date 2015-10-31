var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

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
                        this.set('subscribeForum', subscribe);
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
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            subscribeForum: {},
            subscribeList: {},
            topicList: {}
        },
        CLICKS: {}
    });

    NS.SubscribeWidget.parseURLParam = function(args){
        return {};
    };

};