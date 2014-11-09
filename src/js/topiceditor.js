/*
 * @package Abricos
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['container.js', 'editor.js']},
        {name: 'forum', files: ['lib.js']},
        {name: 'filemanager', files: ['attachment.js']}
    ]
};
Component.entryPoint = function(NS){

    var Dom = YAHOO.util.Dom,
        E = YAHOO.util.Event,
        L = YAHOO.lang,
        R = NS.roles,
        BW = Brick.mod.widget.Widget;

    var buildTemplate = this.buildTemplate;

    var TopicEditorPanel = function(topicid){

        this.topicid = topicid || 0;

        TopicEditorPanel.superclass.constructor.call(this, {fixedcenter: true});
    };
    YAHOO.extend(TopicEditorPanel, Brick.widget.Panel, {
        initTemplate: function(){
            buildTemplate(this, 'panel');

            return this._TM.replace('panel');
        },
        onLoad: function(){
            var TM = this._TM;
            this.gmenu = new NS.GlobalMenuWidget(TM.getEl('panel.gmenu'), 'list');

            this.editorWidget = new NS.TopicEditorWidget(TM.getEl('panel.widget'), this.topicid);
        }
    });
    NS.TopicEditorPanel = TopicEditorPanel;

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
        init: function(topicid, cfg){
            TopicEditorWidget.active = this;

            this.topicid = topicid;
            this.cfg = cfg;
        },
        onLoad: function(topicid, cfg){
            var __self = this;
            NS.initManager(function(){
                if (topicid == 0){
                    __self.onTopicLoad(new NS.Topic({'dtl': {'bd': ''}}));
                } else {
                    NS.manager.topicLoad(topicid, function(topic){
                        __self.onTopicLoad(topic);
                    });
                }
            });
        },
        onTopicLoad: function(topic){
            this.topic = topic;

            if (L.isNull(topic)){
                return;
            }

            Dom.setStyle(this.gel('tl' + (topic.id * 1 > 0 ? 'new' : 'edit')), 'display', 'none');

            this.elSetValue({
                'tl': topic.title.replace(/&gt;/g, '>').replace(/&lt;/g, '<')
            });
            this.elSetHTML({
                'editor': topic.detail.body
            });

            var Editor = Brick.widget.Editor;
            this.editor = new Editor(this.gel('editor'), {
                width: '750px', height: '250px', 'mode': Editor.MODE_VISUAL
            });

            if (Brick.AppRoles.check('filemanager', '30')){
                this.filesWidget = new Brick.mod.filemanager.AttachmentWidget(this.gel('files'), topic.detail.files);
            } else {
                this.filesWidget = null;
                this.elHide('rfiles');
            }
        },
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