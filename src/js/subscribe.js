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