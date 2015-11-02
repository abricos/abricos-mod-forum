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
            var tp = this.template,
                notifyApp = appInstance.getApp('notify'),
                subscribeList = notifyApp.get('subscribeBaseList');

            this.moduleSubscribeWidget = new NOTIFY.SubscribeRowButtonWidget({
                srcNode: tp.one('moduleSubscribe'),
                subscribe: subscribeList.getSubscribe(NS.SUBSCRIBE.module())
            });

            this.topicNewSubscribeWidget = new NOTIFY.SubscribeRowButtonWidget({
                srcNode: tp.one('topicNewSubscribe'),
                subscribe: subscribeList.getSubscribe(NS.SUBSCRIBE.topicNew())
            });

            this.topicCommentSubscribeWidget = new NOTIFY.SubscribeRowButtonWidget({
                srcNode: tp.one('topicCommentSubscribe'),
                subscribe: subscribeList.getSubscribe(NS.SUBSCRIBE.topicComment())
            });
        },
        destructor: function(){
            if (this.moduleSubscribeWidget){
                this.moduleSubscribeWidget.destroy();
                this.topicNewSubscribeWidget.destroy();
                this.topicCommentSubscribeWidget.destroy();
            }
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'}
        },
        CLICKS: {}
    });

    NS.SubscribeWidget.parseURLParam = function(args){
        return {};
    };

};