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

    NS.TopicListWidget = Y.Base.create('topicListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            var page = 1;
            appInstance.topicList(page, this.onLoadTopicList, this);
        },
        onLoadTopicList: function(err, result){
            this.set('waiting', false);
            if (!err){
                this.set('topicList', result.topicList);
            }
            var tp = this.template;
            this.subscribeWidget = new NS.TopicNewSubscribeButtonWidget({
                srcNode: tp.one('subscribe')
            });
            this.renderTopicList();
        },
        renderTopicList: function(){
            var topicList = this.get('topicList');
            if (!topicList){
                return;
            }

            var appInstance = this.get('appInstance'),
                tp = this.template,
                userList = this.get('userList'),
                lst = "";

            topicList.each(function(topic){
                var user = topic.get('user'),
                    cmtStatistic = topic.get('commentStatistic'),
                    cmtCount = cmtStatistic.get('count'),
                    d = {
                        id: topic.get('id'),
                        title: topic.getTitle(),
                        cmt: cmtCount,
                        viewcount: topic.get('viewcount'),
                        dateline: Brick.dateExt.convert(topic.get('dateline')),
                        user: tp.replace('user', {
                            userid: user.get('id'),
                            username: user.get('viewName')
                        }),
                        cmtuser: tp.replace('user', {
                            userid: user.get('id'),
                            username: user.get('viewName')
                        }),
                        cmtdate: Brick.dateExt.convert(topic.get('upddate')),
                        closed: topic.isClosed() ? 'closed' : '',
                        removed: topic.isRemoved() ? 'removed' : ''
                    };

                user = cmtStatistic.get('lastUser');
                if (user){
                    d['cmtuser'] = tp.replace('user', {
                        userid: user.get('id'),
                        username: user.get('viewName')
                    });
                    d['cmtdate'] = Brick.dateExt.convert(cmtStatistic.get('lastDate'));
                }

                lst += tp.replace('row', d);
            }, this);

            tp.gel('table').innerHTML = tp.replace('table', {'rows': lst});

            this.appURLUpdate();
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'widget,table,row,user'
            },
            topicList: {},
            userList: {}
        }
    });
};