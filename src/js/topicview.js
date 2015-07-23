var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['container.js']},
        {name: 'filemanager', files: ['lib.js']},
        {name: 'uprofile', files: ['users.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        L = Y.Lang,
        COMPONENT = this,
        R = NS.roles,
        SYS = Brick.mod.sys;

    var LNG = this.language;


    var aTargetBlank = function(el){
        if (el.tagName == 'A'){
            el.target = "_blank";
        } else if (el.tagName == 'IMG'){
            el.style.maxWidth = "100%";
            el.style.height = "auto";
        }
        var chs = el.childNodes;
        for (var i = 0; i < chs.length; i++){
            if (chs[i]){
                aTargetBlank(chs[i]);
            }
        }
    };

    NS.TopicViewWidget = Y.Base.create('topicViewWidget', SYS.AppWidget, [], {

        initializer: function(){
            this.publish('topicClosed', {
                defaultFn: this._defTopicClosed
            });
            this.publish('topicRemoved', {
                defaultFn: this._defTopicRemoved
            });
        },

        buildTData: function(){
            return {
                'id': this.get('topicId') | 0
            };
        },
        onInitAppWidget: function(err, appInstance, options){
            this.set('waiting', true);
            var topicId = this.get('topicId');

            this.get('appInstance').topic(topicId, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('topic', result.topic);
                }
                this.renderTopic();
            }, this);
        },
        renderTopic: function(){
            var topic = this.get('topic');
            if (!topic){
                return;
            }
            // TODO: если this.topic=null необходимо показать "либо нет прав, либо проект удален"

            var appInstance = this.get('appInstance'),
                tp = this.template;

            if (!this.flagFirstTopicRender){ // первичная рендер
                this.flagFirstTopicRender = true;

                // Инициализировать менеджер комментариев
                Brick.use('comment', 'comment', function(err, ns){
                    ns.API.buildCommentTree({
                        'container': tp.gel('comments'),
                        'dbContentId': topic.detail.contentid,
                        'config': {
                            'onLoadComments': function(){
                                aTargetBlank(tp.gel('topicbody'));
                                aTargetBlank(tp.gel('comments'));
                            },
                            'readOnly': (topic.isRemoved() || topic.isClosed())
                            // ,'manBlock': L.isFunction(config['buildManBlock']) ? config.buildManBlock() : null
                        },
                        'instanceCallback': function(b){
                        }
                    });
                });
            }

            // Автор
            var user = appInstance.users.get(topic.userid);

            var elSetHTML = function(d){
                for (var n in d){
                    tp.gel(n).innerHTML = d[n];
                }
            };

            elSetHTML({
                'author': tp.replace('user', {
                    'uid': user.id, 'unm': user.getUserName()
                }),
                'dl': Brick.dateExt.convert(topic.date, 3, true),
                'dlt': Brick.dateExt.convert(topic.date, 4),
                'status': LNG['status'][topic.status],
                'title': topic.title == '' ? LNG.get('topic.emptytitle') : topic.title,
                'topicbody': topic.detail.body
            });

            var elHide = function(els){
                var a = els.split(',');
                for (var i = 0; i < a.length; i++){
                    Y.one(tp.gel(a[i])).addClass('hide');
                }
            };
            var elShow = function(els){
                var a = els.split(',');
                for (var i = 0; i < a.length; i++){
                    Y.one(tp.gel(a[i])).removeClass('hide');
                }
            };

            // закрыть все кнопки, открыть по ролям
            elHide('bopen,bclose,beditor,bremove');

            var isMyTopic = user.id * 1 == Brick.env.user.id * 1;
            if (topic.status == NS.TopicStatus.OPENED){
                if (R['isModer']){
                    elShow('beditor,bremove,bclose');
                } else if (isMyTopic){
                    elHide('beditor,bremove');
                }
            }

            // показать прикрепленные файлы
            var fs = topic.detail.files;
            if (fs.length > 0){
                elShow('files');

                var alst = [], lst = "";
                for (var i = 0; i < fs.length; i++){
                    var f = fs[i];
                    var lnk = new Brick.mod.filemanager.Linker({
                        'id': f['id'],
                        'name': f['nm']
                    });
                    alst[alst.length] = tp.replace('frow', {
                        'fid': f['id'],
                        'nm': f['nm'],
                        'src': lnk.getSrc()
                    });
                }
                lst = alst.join('');
                tp.gel('ftable').innerHTML = lst;
                console.log(tp.gel('ftable'));
            } else {
                elHide('files');
            }
        },
        onClick: function(e){
            switch (e.dataClick) {
                case 'topic-close':
                    this.topicClose();
                    return true;
                case 'topic-close-no':
                    this.topicCloseCancel();
                    return true;
                case 'topic-close-yes':
                    this.topicCloseMethod();
                    return true;
                case 'topic-remove':
                    this.topicRemove();
                    return true;
                case 'topic-remove-no':
                    this.topicRemoveCancel();
                    return true;
                case 'topic-remove-yes':
                    this.topicRemoveMethod();
                    return true;
            }
        },
        topicClose: function(){
            var tp = this.template;
            Y.one(tp.gel('manbuttons')).addClass('hide');
            Y.one(tp.gel('dialogclose')).removeClass('hide');
        },
        topicCloseCancel: function(){
            var tp = this.template;
            Y.one(tp.gel('manbuttons')).removeClass('hide');
            Y.one(tp.gel('dialogclose')).addClass('hide');
        },
        topicCloseMethod: function(){
            this.topicCloseCancel();
            this.set('waiting', true);
            this.get('appInstance').topicClose(this.get('topicId'), function(err, result){
                this.set('waiting', false);
                this.fire('topicClosed');
            }, this);
        },

        topicRemove: function(){
            var tp = this.template;
            Y.one(tp.gel('manbuttons')).addClass('hide');
            Y.one(tp.gel('dialogremove')).removeClass('hide');
        },
        topicRemoveCancel: function(){
            var tp = this.template;
            Y.one(tp.gel('manbuttons')).removeClass('hide');
            Y.one(tp.gel('dialogremove')).addClass('hide');
        },
        topicRemoveMethod: function(){
            this.topicRemoveCancel();

            this.set('waiting', true);
            this.get('appInstance').topicRemove(this.get('topicId'), function(err, result){
                this.set('waiting', false);
                this.fire('topicRemoved');
            }, this);
        },
        _defTopicClosed: function(){
            Brick.Page.reload(NS.URL.topic.list());
        },
        _defTopicRemoved: function(){
            Brick.Page.reload(NS.URL.topic.list());
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'widget,user,frow,empttitle'
            },
            topicId: {
                value: 0
            }
        }
    });

    NS.TopicViewWidget.parseURLParam = function(args){
        return {
            topicId: args[0] | 0
        };
    };

};