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
                var files = topic.get('files');
                this.filesWidget = new Brick.mod.filemanager.AttachmentWidget(tp.gel('files'), files.toArray());
            } else {
                this.filesWidget = null;
                tp.hide('rfiles');
            }
        },
        save: function(){
            var tp = this.template,
                data = {
                    topicid: this.get('topicid'),
                    title: tp.getValue('title'),
                    body: this._bodyEditor.get('content'),
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
        cancel: function(){
            this.fire('editorCancel');
        },
        _defEditorCancel: function(){
            this.go('topic.list');
        },
        _defEditorSaved: function(){
            this.go('topic.view', this.get('topicid'));
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            topicid: {value: 0},
            topic: {},
            isEdit: {
                getter: function(){
                    return this.get('topicid') | 0 > 0;
                }
            }
        },
        CLICKS: {
            save: 'save', cancel: 'cancel'
        }
    });

    NS.TopicEditorWidget.parseURLParam = function(args){
        return {
            topicid: args[0] | 0
        };
    };


};