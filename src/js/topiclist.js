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

    var L = YAHOO.lang,
        buildTemplate = this.buildTemplate,
        BW = Brick.mod.widget.Widget;

    var LNG = this.language;

    var TopicListWidget = function(container, cfg){
        cfg = L.merge({}, cfg || {});

        TopicListWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget,table,row,user'
        }, cfg);
    };
    YAHOO.extend(TopicListWidget, BW, {
        init: function(cfg){
            this.cfg = cfg;

            this.list = null;
        },

        onLoad: function(cfg){
            var __self = this;
            NS.initManager(function(){
                NS.manager.topicListLoad(function(list){
                    __self.renderList(list);
                });
            });
        },

        renderList: function(list){
            this.list = list;

            var TM = this._TM,
                lst = "";

            var arr = [];
            this.list.foreach(function(topic){
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
            for (var i = 0; i < arr.length; i++){
                var topic = arr[i];

                var user = NS.manager.users.get(topic.userid);
                var d = {
                    'id': topic.id,
                    'tl': topic.title == '' ? LNG.get('topic.emptytitle') : topic.title,
                    'cmt': topic.cmt,
                    'cmtuser': TM.replace('user', {'uid': user.id, 'unm': user.getUserName()}),
                    'cmtdate': Brick.dateExt.convert(topic.updDate),
                    'closed': topic.isClosed() ? 'closed' : '',
                    'removed': topic.isRemoved() ? 'removed' : ''
                };
                if (topic.cmt > 0){
                    var user = NS.manager.users.get(topic.cmtUserId);
                    d['cmtuser'] = TM.replace('user', {'uid': user.id, 'unm': user.getUserName()});
                    d['cmtdate'] = Brick.dateExt.convert(topic.cmtDate);
                }

                lst += TM.replace('row', d);
            }
            TM.getEl('widget.table').innerHTML = TM.replace('table', {'rows': lst});
        }
    });
    NS.TopicListWidget = TopicListWidget;


};