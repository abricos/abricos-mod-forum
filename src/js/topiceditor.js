/*
 * @package Abricos
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['container.js', 'editor.js']},
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
        onInitAppWidget: function(err, appInstance, options){
            var topicId = this.get('topicId');

            if (topicId | 0 === 0){
                var topic = new NS.Topic({'dtl': {'bd': ''}});
                this.set('topic', topic);
                this.renderTopic();
            } else {
                this.set('waiting', true);
                this.get('appInstance').topic(topicId, function(err, result){
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

            Y.one(tp.gel('title')).set('value', topic.title.replace(/&gt;/g, '>').replace(/&lt;/g, '<'));
            Y.one(tp.gel('editor')).setHTML(topic.detail.body);

            var Editor = Brick.widget.Editor;
            this.editor = new Editor(tp.gel('editor'), {
                width: '750px', height: '250px', 'mode': Editor.MODE_VISUAL
            });

            if (Brick.mod.filemanager.roles.isWrite){
                this.filesWidget = new Brick.mod.filemanager.AttachmentWidget(tp.gel('files'), topic.detail.files);
            } else {
                this.filesWidget = null;
                Y.one(tp.gel('rfiles')).hide()
            }
        },
        topicSave: function(){
            var topic = this.get('topic'), tp = this.template;

            var newdata = {
                'title': tp.gel('title').value,
                'body': this.editor.getContent(),
                'files': Y.Lang.isNull(this.filesWidget) ? topic.files : this.filesWidget.files
            };

            this.set('waiting', true);
            this.get('appInstance').topicSave(newdata, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('topic', result.topic);
                    this.set('topicId', result.topic.id);
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
            Brick.Page.reload(NS.URL.topic.list());
        },
        _defEditorSaved: function(){
            var topicId = this.get('topicId');

            Brick.Page.reload(NS.URL.topic.view(topicId));
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'widget'
            },
            topicId: {
                value: 0
            },
            isEdit: {
                getter: function(){
                    return this.get('topicId') | 0 > 0;
                }
            }
        }
    });

    NS.TopicEditorWidget.parseURLParam = function(args){
        return {
            topicId: args[0] | 0
        };
    };

    return;
    /* * * * * OLD * * * * */

    var TopicEditorWidget = function(container, topicid, cfg){
        cfg = L.merge({
            'onTopicSave': function(topic){
                setTimeout(function(){
                    if (!L.isValue(topic)){
                        Brick.Page.reload(NS.navigator.home());
                    } else {
                        Brick.Page.reload(NS.navigator.topic.view(topic.id));
                    }
                }, 100);
            },
            'onCancelClick': function(){
                if (topicid > 0){
                    Brick.Page.reload(NS.navigator.topic.view(topicid));
                } else {
                    Brick.Page.reload(NS.navigator.home());
                }
            }
        }, cfg || {});

        TopicEditorWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget,frow'
        }, topicid, cfg);
    };
    YAHOO.extend(TopicEditorWidget, BW, {


        cancel: function(){
            NS.life(this.cfg['onCancelClick']);
        },
        saveTopic: function(){
            var topic = this.topic;

            this.elHide('bsave,bcancel');
            this.elShow('loading');

            var newdata = {
                'title': this.gel('tl').value,
                'body': this.editor.getContent(),
                'files': L.isNull(this.filesWidget) ? topic.files : this.filesWidget.files
            };
            var __self = this;
            NS.manager.topicSave(topic, newdata, function(nTopic){
                NS.life(__self.cfg['onTopicSave'], nTopic);
            });
        }
    });
    NS.TopicEditorWidget = TopicEditorWidget;

};