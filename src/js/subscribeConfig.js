var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['subscribe.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var NOTIFY = Brick.mod.notify,
        SB = NS.SUBSCRIBE;

    NS.SubscribeConfigWidget = Y.Base.create('subscribeConfigWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            var tp = this.template;

            this.moduleButton = new NOTIFY.SubscribeConfigButtonWidget({
                srcNode: tp.one('moduleSubscribe'),
                ownerDefine: SB.MODULE
            });

            this.moduleButton = new NOTIFY.SubscribeConfigButtonWidget({
                srcNode: tp.one('topicNewSubscribe'),
                ownerDefine: SB.TOPIC_NEW
            });

            this.moduleButton = new NOTIFY.SubscribeConfigButtonWidget({
                srcNode: tp.one('topicCommentSubscribe'),
                ownerDefine: SB.TOPIC_COMMENT
            });
        },
        destructor: function(){
            if (this.moduleButton){
                this.moduleButton.destroy();
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


};