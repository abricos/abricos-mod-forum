var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'filemanager', files: ['attachment.js']},
        {name: 'comment', files: ['tree.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

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
            this.publish('topicClosed');
            this.publish('topicRemoved');
        },
        buildTData: function(){
            return {
                'id': this.get('topicid') | 0
            };
        },
        destructor: function(){
            if (this._commentsWidget){
                this._commentsWidget.destroy();
                this._filesWidget.destroy();
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
                this.onLoadTopic();
            }, this);
        },
        onLoadTopic: function(){
            var tp = this.template,
                topic = this.get('topic');

            if (!topic){ // TODO: show 404 - topic not found
                return;
            }

            var commentOwner = topic.get('commentOwner');

            this._commentsWidget = new Brick.mod.comment.CommentTreeWidget({
                srcNode: tp.one('commentTree'),
                commentOwner: commentOwner,
                readOnly: !topic.isCommentWriteRole()
            });

            var files = [];
            topic.get('files').each(function(file){
                files[files.length] = {
                    id: file.get('filehash'),
                    nm: file.get('filename'),
                    sz: file.get('filesize')
                };
            }, this);

            this._filesWidget = new Brick.mod.filemanager.AttachmentListWidget(tp.gel('files'), files);
            tp.toggleView(files.length > 0, 'filesPanel');

            this.renderTopic();
        },
        renderTopic: function(topic){
            if (topic){
                this.set('topic', topic);
            }
            topic = topic || this.get('topic');
            if (!topic){
                return;
            }
            // TODO: если this.topic=null необходимо показать "либо нет прав, либо тема удалена"

            var appInstance = this.get('appInstance'),
                tp = this.template;

            var user = topic.get('user'),
                status = topic.get('status');

            tp.setHTML({
                author: tp.replace('user', {
                    uid: user.get('id'),
                    unm: user.get('viewName')
                }),
                title: topic.getTitle(),
                dl: Brick.dateExt.convert(topic.get('dateline'), 3, true),
                dlt: Brick.dateExt.convert(topic.get('dateline'), 4),
                topicbody: topic.get('body'),
                status: status.get('title')
            });

            var lstButtons = "";
            lstButtons += topic.isWriteRole() ? tp.replace('editButton') : "";
            lstButtons += topic.isOpenRole() ? tp.replace('openButton') : "";
            lstButtons += topic.isCloseRole() ? tp.replace('closeButton') : "";
            lstButtons += topic.isRemoveRole() ? tp.replace('removeButton') : "";

            tp.setHTML('buttons', lstButtons);
            if (lstButtons !== ""){
                tp.show('actions');
            }

            this._commentsWidget.set('readOnly', !topic.isCommentWriteRole());
        },
        topicCloseShowDialog: function(){
            var tp = this.template;
            tp.setHTML('dialog', tp.replace('closeDialog'));
            tp.toggleView(true, 'dialog', 'buttons');
        },
        topicCloseHideDialog: function(){
            this.template.toggleView(false, 'dialog', 'buttons');
        },
        topicRemoveShowDialog: function(){
            var tp = this.template;
            tp.setHTML('dialog', tp.replace('removeDialog'));
            tp.toggleView(true, 'dialog', 'dialogremove');
        },
        topicRemoveHideDialog: function(){
            this.template.toggleView(false, 'dialog', 'buttons');
        },
        topicClose: function(){
            this.set('waiting', true);
            this.get('appInstance').topicClose(this.get('topicid'), function(err, result){
                this.topicCloseHideDialog();
                this.set('waiting', false);
                if (!err){
                    this.renderTopic(result.topic);
                    this.fire('topicClosed');
                }
            }, this);
        },
        topicOpen: function(){
            this.set('waiting', true);
            this.get('appInstance').topicOpen(this.get('topicid'), function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.renderTopic(result.topic);
                    this.fire('topicOpened');
                }
            }, this);
        },
        topicRemove: function(){
            this.set('waiting', true);
            this.get('appInstance').topicRemove(this.get('topicid'), function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.fire('topicRemoved');
                    this.go('topic.list');
                }
            }, this);
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,user,frow,editButton,openButton,closeButton,removeButton,closeDialog,removeDialog'},
            topicid: {value: 0}
        },
        CLICKS: {
            topicEdit: {
                event: function(){
                    this.go('topic.edit', this.get('topicid'));
                }
            },
            topicCloseShowDialog: 'topicCloseShowDialog',
            topicCloseHideDialog: 'topicCloseHideDialog',
            topicClose: 'topicClose',
            topicRemoveShowDialog: 'topicRemoveShowDialog',
            topicRemoveHideDialog: 'topicRemoveHideDialog',
            topicRemove: 'topicRemove',
            topicOpen: 'topicOpen'
        },
        parseURLParam: function(args){
            return {
                topicid: args[0] | 0
            };
        }
    });

};