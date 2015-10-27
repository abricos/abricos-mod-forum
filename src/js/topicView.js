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

            this._commentsWidget = new Brick.mod.comment.CommentListWidget({
                srcNode: tp.one('commentList'),
                commentOwner: commentOwner,
                readOnly: !topic.isCommentWriteRole()
            });

            this.renderTopic();
        },
        renderTopic: function(){
            var topic = this.get('topic');
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

            if (topic.isWriteRole()){
                lstButtons += tp.replace('editButton');
            }

            if (topic.isOpenRole()){
                lstButtons += tp.replace('openButton');
            }

            if (topic.isRemoveRole()){
                lstButtons += tp.replace('removeButton');
            }

            if (topic.isCloseRole()){
                lstButtons += tp.replace('closeButton');
            }

            tp.setHTML('buttons', lstButtons);
            if (lstButtons !== ""){
                tp.show('actions');
            }

            this._commentsWidget.set('readOnly', !topic.isCommentWriteRole());
        },
        topicCloseShowDialog: function(){
            this.template.toggleView(true, 'dialogclose', 'buttons');
        },
        topicCloseHideDialog: function(){
            this.template.toggleView(false, 'dialogclose', 'buttons');
        },
        topicClose: function(){
            this.set('waiting', true);
            this.get('appInstance').topicClose(this.get('topicid'), function(err, result){
                this.topicCloseHideDialog();
                this.set('waiting', false);

                this.renderTopic();
                this.fire('topicClosed');
            }, this);
        },
        topicRemoveShowDialog: function(){
            this.template.toggleView(true, 'dialogclose', 'dialogremove');
        },
        topicRemoveHideDialog: function(){
            this.template.toggleView(false, 'dialogremove', 'buttons');
        },
        topicRemove: function(){
            this.set('waiting', true);
            return;
            this.get('appInstance').topicRemove(this.get('topicid'), function(err, result){
                this.set('waiting', false);
                this.go('topic.list');
                this.fire('topicRemoved');
            }, this);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,user,frow,editButton,openButton,closeButton,removeButton,closeDialog,removeDialog'},
            topicid: {value: 0}
        },
        CLICKS: {
            topicCloseShowDialog: 'topicCloseShowDialog',
            topicCloseHideDialog: 'topicCloseHideDialog',
            topicClose: 'topicClose',
            topicRemoveShowDialog: 'topicRemoveShowDialog',
            topicRemoveHideDialog: 'topicRemoveHideDialog',
            topicRemove: 'topicRemove',
        }
    });

    NS.TopicViewWidget.parseURLParam = function(args){
        return {
            topicid: args[0] | 0
        };
    };

};