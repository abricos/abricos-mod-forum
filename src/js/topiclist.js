var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'uprofile', files: ['users.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.TopicListWidget = Y.Base.create('topicListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            var appInstance = this.get('appInstance'),
                page = 1;

            appInstance.topicList(page, function(err, result){
                if (!err){
                    this.set('topicList', result.topicList);
                }
                this.renderTopicList();
            }, this);
        },
        renderTopicList: function(){
            this.set('waiting', false);
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
                    stat = topic.get('commentStatistic'),
                    cmtCount = stat.get('count'),
                    d = {
                        id: topic.get('id'),
                        title: topic.getTitle(),
                        cmt: cmtCount,
                        cmtuser: tp.replace('user', {
                            userid: user.get('id'),
                            username: user.get('viewName')
                        }),
                        cmtdate: Brick.dateExt.convert(topic.get('upddate')),
                        closed: topic.isClosed() ? 'closed' : '',
                        removed: topic.isRemoved() ? 'removed' : ''
                    };

                if (topic.cmt > 0){
                    user = appInstance.users.get(topic.cmtUserId);
                    d['cmtuser'] = tp.replace('user', {'uid': user.id, 'unm': user.getUserName()});
                    d['cmtdate'] = Brick.dateExt.convert(topic.cmtDate);
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