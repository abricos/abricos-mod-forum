var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'uprofile', files: ['users.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        L = Y.Lang,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var LNG = this.language;

    NS.TopicListWidget = Y.Base.create('topicListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);

            var appInstance = this.get('appInstance'),
                page = 1;

            appInstance.topicList(page, function(err, result){
                this.set('waiting', false);
                if (!err){
                    var topicList = result.topicList,
                        userIds = topicList.toArray('userid');

                    appInstance.get('uprofile').userListByIds(userIds, function(err, result){
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
                var user = userList.getById(topic.get('userid'));
console.log(user);
                var d = {
                    'id': topic.id,
                    'tl': topic.title == '' ? LNG.get('topic.emptytitle') : topic.title,
                    'cmt': topic.cmt,
                    // 'cmtuser': tp.replace('user', {'uid': user.id, 'unm': user.getUserName()}),
                    cmtdate: Brick.dateExt.convert(topic.updDate),
                    closed: topic.isClosed() ? 'closed' : '',
                    removed: topic.isRemoved() ? 'removed' : '',
                    // urltopicview: this.getURL('topic.view', topic.get('id'))
                };
                if (topic.cmt > 0){
                    user = appInstance.users.get(topic.cmtUserId);
                    d['cmtuser'] = tp.replace('user', {'uid': user.id, 'unm': user.getUserName()});
                    d['cmtdate'] = Brick.dateExt.convert(topic.cmtDate);
                }

                lst += tp.replace('row', d);
            }, this);

            tp.gel('table').innerHTML = tp.replace('table', {'rows': lst});
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