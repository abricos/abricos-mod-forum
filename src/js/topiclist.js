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
                this.set('waiting', false);
                if (!err){
                    var topicList = result.topicList,
                        userIds = topicList.toArray('userid', {distinct: true});

                    appInstance.getApp('uprofile').userListByIds(userIds, function(err, result){
                        this.set('userList', result.userListByIds);
                        this.set('topicList', topicList);
                        this.renderTopicList();
                    }, this);
                }
            }, this);
        },
        renderTopicList: function(){
            var topicList = this.get('topicList');
            if (!topicList){
                return;
            }
            /*
             var arr = [];
             arr = arr.sort(function(m1, m2){
             var t1 = m1.updDate.getTime(),
             t2 = m2.updDate.getTime();

             if (!L.isNull(m1.cmtDate)){
             t1 = Math.max(t1, m1.cmtDate.getTime());
             }
             if (!L.isNull(m2.cmtDate)){
             t2 = Math.max(t2, m2.cmtDate.getTime());
             }
             if (t1 > t2){
             return -1;
             }
             if (t1 < t2){
             return 1;
             }
             return 0;
             });/**/

            var appInstance = this.get('appInstance'),
                tp = this.template,
                userList = this.get('userList'),
                lst = "";

            topicList.each(function(topic){
                var user = userList.getById(topic.get('userid')),
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