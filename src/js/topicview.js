var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'filemanager', files: ['lib.js']},
        {name: 'comment', files: ['commentList.js']},
        {name: 'uprofile', files: ['users.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
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
                'id': this.get('topicid') | 0
            };
        },
        destructor: function(){
            if (this._commentsWidget){
                this._commentsWidget.destroy();
            }
        },
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);
            var topicid = this.get('topicid');

            this.get('appInstance').topic(topicid, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('topic', result.topic);
                }
                this.renderTopic();
            }, this);

            var tp = this.template;

            this._commentsWidget = new Brick.mod.comment.CommentListWidget({
                srcNode: tp.one('commentList'),
                ownerModule: 'forum',
                ownerType: 'topic',
                ownerid: topicid
            });
        },
        renderTopic: function(){
            var topic = this.get('topic');
            if (!topic){
                return;
            }
            // TODO: если this.topic=null необходимо показать "либо нет прав, либо тема удалена"

            var appInstance = this.get('appInstance'),
                tp = this.template;

            /*
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
            }/**/

            // Автор
            var user = topic.get('user');
console.log(topic.toJSON(true));
            tp.setHTML({
                author: tp.replace('user', {
                    uid: user.get('id'),
                    unm: user.get('viewName')
                }),
                title: topic.getTitle(),
                dl: Brick.dateExt.convert(topic.get('dateline'), 3, true),
                dlt: Brick.dateExt.convert(topic.get('dateline'), 4),
                topicbody: topic.get('body')
                /*
                'status': LNG['status'][topic.status],
                /**/
            });


            // закрыть все кнопки, открыть по ролям
            tp.hide('bopen,bclose,beditor,bremove');

            var isMyTopic = user.id * 1 == Brick.env.user.id * 1;
            if (topic.status == NS.TopicStatus.OPENED){
                if (R['isModer']){
                    elShow('beditor,bremove,bclose');
                } else if (isMyTopic){
                    elHide('beditor,bremove');
                }
            }

            /*
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
            /**/
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
            this.get('appInstance').topicClose(this.get('topicid'), function(err, result){
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
            this.get('appInstance').topicRemove(this.get('topicid'), function(err, result){
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
            topicid: {
                value: 0
            }
        }
    });

    NS.TopicViewWidget.parseURLParam = function(args){
        return {
            topicid: args[0] | 0
        };
    };

};