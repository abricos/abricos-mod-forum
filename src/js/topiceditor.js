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

    ], {

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
            Y.one(tp.gel('tl' + (topic.id * 1 > 0 ? 'new' : 'edit'))).hide();

            Y.one(tp.gel('tl')).set('value', topic.title.replace(/&gt;/g, '>').replace(/&lt;/g, '<'));
            Y.one(tp.gel('editor')).setHTML(topic.detail.body);

            var Editor = Brick.widget.Editor;
            this.editor = new Editor(tp.gel('editor'), {
                width: '750px', height: '250px', 'mode': Editor.MODE_VISUAL
            });

            if (Brick.AppRoles.check('filemanager', '30')){
                this.filesWidget = new Brick.mod.filemanager.AttachmentWidget(this.gel('files'), topic.detail.files);
            } else {
                this.filesWidget = null;
                Y.one(tp.gel('rfiles')).hide()
            }
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

    var Dom = YAHOO.util.Dom,
        L = YAHOO.lang,
        BW = Brick.mod.widget.Widget;

    var buildTemplate = this.buildTemplate;


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

        destroy: function(){
            this.editor.destroy();
            TopicEditorPanel.active = null;
            TopicEditorPanel.superclass.destroy.call(this);
        },
        onClick: function(el, tp){
            switch (el.id) {
                case tp['bsave']:
                    this.saveTopic();
                    return true;
                case tp['bcancel']:
                    this.cancel();
                    return true;
            }
            return false;
        },
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

    // создать сообщение
    NS.API.showCreateTopicPanel = function(){
        return NS.API.showTopicEditorPanel(0);
    };

    var activePanel = null;
    NS.API.showTopicEditorPanel = function(topicid, ptopicid){
        if (L.isValue(activePanel) && !activePanel.isDestroy()){
            activePanel.close();
            activePanel = null;
        }
        if (L.isNull(activePanel) || activePanel.isDestroy()){
            activePanel = new TopicEditorPanel(topicid, ptopicid);
        }
        return activePanel;
    };
};