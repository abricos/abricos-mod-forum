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


    NS.SubscribeConfigWidget = Y.Base.create('subscribeWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            var tp = this.template,
                notifyApp = appInstance.getApp('notify'),
                subscribeList = notifyApp.get('subscribeBaseList');

            this.moduleSubscribeConfigWidget = new NOTIFY.SubscribeRowButtonWidget({
                srcNode: tp.one('moduleSubscribe'),
                subscribe: subscribeList.getSubscribe(NS.SUBSCRIBE.module())
            });

            this.topicNewSubscribeConfigWidget = new NOTIFY.SubscribeRowButtonWidget({
                srcNode: tp.one('topicNewSubscribe'),
                subscribe: subscribeList.getSubscribe(NS.SUBSCRIBE.topicNew())
            });

            this.topicCommentSubscribeConfigWidget = new NOTIFY.SubscribeRowButtonWidget({
                srcNode: tp.one('topicCommentSubscribe'),
                subscribe: subscribeList.getSubscribe(NS.SUBSCRIBE.topicComment())
            });
        },
        destructor: function(){
            if (this.moduleSubscribeConfigWidget){
                this.moduleSubscribeConfigWidget.destroy();
                this.topicNewSubscribeConfigWidget.destroy();
                this.topicCommentSubscribeConfigWidget.destroy();
            }
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'}
        },
        CLICKS: {},
        parseURLParam: function(){
            return {};
        }
    });

    NS.TopicNewSubscribeButtonWidget = Y.Base.create('topicNewSubscribeButtonWidget', SYS.AppWidget, [
        NOTIFY.SwitcherStatusExt
    ], {

    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'topicNewButton'},
            ownerDefine: {
                value: NS.SUBSCRIBE.TOPIC_NEW
            }
        }
    });

    NS.TopicCommentSubscribeButtonWidget = Y.Base.create('topicCommentSubscribeButtonWidget', SYS.AppWidget, [
        NOTIFY.SwitcherStatusExt
    ], {
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'topicNewButton'},
            ownerDefine: {
                value: NS.SUBSCRIBE.TOPIC_COMMENT_ITEM
            }
        }
    });


};