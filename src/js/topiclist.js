/*
 * @package Abricos
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['container.js']},
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
        onInitAppWidget: function(err, appInstance, options){
            this.set('waiting', true);

            this.get('appInstance').topicList(function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('topicList', result.topicList);
                }
                this.renderTopicList();
            }, this);
        },
        renderTopicList: function(){
            var topicList = this.get('topicList');
            if (!topicList){
                return;
            }
            var arr = [];
            topicList.foreach(function(topic){
                arr[arr.length] = topic;
            });
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
            });

            var appInstance = this.get('appInstance'),
                tp = this.template, lst = "",
                topic, user, d;

            for (var i = 0; i < arr.length; i++){
                topic = arr[i];
                user = appInstance.users.get(topic.userid);

                d = {
                    'id': topic.id,
                    'tl': topic.title == '' ? LNG.get('topic.emptytitle') : topic.title,
                    'cmt': topic.cmt,
                    'cmtuser': tp.replace('user', {'uid': user.id, 'unm': user.getUserName()}),
                    'cmtdate': Brick.dateExt.convert(topic.updDate),
                    'closed': topic.isClosed() ? 'closed' : '',
                    'removed': topic.isRemoved() ? 'removed' : '',
                    'urltopicview': NS.URL.topic.view(topic.id)
                };
                if (topic.cmt > 0){
                    user = appInstance.users.get(topic.cmtUserId);
                    d['cmtuser'] = tp.replace('user', {'uid': user.id, 'unm': user.getUserName()});
                    d['cmtdate'] = Brick.dateExt.convert(topic.cmtDate);
                }

                lst += tp.replace('row', d);
            }
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
            topicList: {
                value: null
            }
        }
    });
};