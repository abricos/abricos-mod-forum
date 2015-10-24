var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: 'filemanager', files: ['attachment.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.TopicEditorWidget = Y.Base.create('topicEditorWidget', SYS.AppWidget, [
        SYS.WidgetEditorStatus
    ], {
        initializer: function(){
            this.publish('editorSaved', {
                defaultFn: this._defEditorSaved
            });
            this.publish('editorCancel', {
                defaultFn: this._defEditorCancel
            });
        },
        onInitAppWidget: function(err, appInstance){
            var topicid = this.get('topicid') | 0;

            if (topicid === 0){
                var topic = new NS.Topic({
                    appInstance: appInstance
                });
                this.set('topic', topic);
                this.renderTopic();
            } else {
                this.set('waiting', true);
                this.get('appInstance').topic(topicid, function(err, result){
                    this.set('waiting', false);
                    if (!err){
                        this.set('topic', result.topic);
                    }
                    this.renderTopic();
                }, this);
            }
        },
        renderTopic: function(){
            var topic = this.get('topic');

            if (!topic){
                return;
            }

            var tp = this.template;
            tp.setValue({
                title: topic.get('title').replace(/&gt;/g, '>').replace(/&lt;/g, '<')
            });

            this._bodyEditor = new SYS.Editor({
                appInstance: this.get('appInstance'),
                srcNode: tp.gel('bodyEditor'),
                content: topic.get('body'),
                toolbar: SYS.Editor.TOOLBAR_STANDART
            });

            if (Brick.mod.filemanager.roles.isWrite){
                this.filesWidget = new Brick.mod.filemanager.AttachmentWidget(tp.gel('files'), topic.get('files'));
            } else {
                this.filesWidget = null;
                Y.one(tp.gel('rfiles')).hide()
            }
        },
        topicSave: function(){
            var tp = this.template,
                data = {
                    id: this.get('topicid'),
                    title: tp.getValue('title'),
                    body: this._bodyEditor.get('content'),
                    isprivate: 0,
                    files: this.filesWidget ? [] : this.filesWidget.files
                };

            this.set('waiting', true);
            this.get('appInstance').topicSave(data, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('topic', result.topic);
                    this.set('topicid', result.topic.get('id'));
                    this.fire('editorSaved');
                }
            }, this);
        },
        onClick: function(e){
            switch (e.dataClick) {
                case 'save':
                    this.topicSave();
                    return true;
                case 'cancel':
                    this.fire('editorCancel');
                    return true;
            }
        },
        _defEditorCancel: function(){
            this.go('topic.list');
        },
        _defEditorSaved: function(){
            this.go('topic.view', this.get('topicid'));
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'widget'
            },
            topicid: {
                value: 0
            },
            isEdit: {
                getter: function(){
                    return this.get('topicid') | 0 > 0;
                }
            }
        }
    });

    NS.TopicEditorWidget.parseURLParam = function(args){
        return {
            topicid: args[0] | 0
        };
    };


};