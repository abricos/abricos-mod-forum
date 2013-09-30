/*
 * @package Abricos
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['container.js', 'editor.js']},
        {name: 'forum', files: ['lib.js', 'roles.js']},
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
					__self.onTopicLoad(new NS.Topic());
				}else{
					NS.manager.topicLoad(topicid, function(topic){
						__self.onTopicLoad(topic);
					});
				}
			});
		},
		onTopicLoad: function(topic){
			this.topic = topic;
			
			if (L.isNull(topic)){ return; }

			Dom.setStyle(this.gel('tl'+(topic.id*1 > 0 ? 'new' : 'edit')), 'display', 'none');
			
			this.elSetValue({
				'tl': topic.title
			});
			this.elSetHTML({
				'editor': topic.detail.body
			});
			
			var Editor = Brick.widget.Editor;
			this.editor = new Editor(this.gel('editor'), {
				width: '750px', height: '250px', 'mode': Editor.MODE_VISUAL
			});
			
			if (Brick.Permission.check('filemanager', '30') == 1){
				this.filesWidget = new Brick.mod.filemanager.AttachmentWidget(this.gel('files'), topic.detail.files);
			}else{
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
			switch(el.id){
			case tp['bsave']: this.saveTopic(); return true;
			case tp['bcancel']: this.cancel(); return true;
			}
			return false;
		},
		cancel: function(){
			var topicid = this.topicid;
			if (topicid > 0){
				Brick.Page.reload('#app=forum/topicview/showTopicViewPanel/'+topicid+'/');
			}else{
				Brick.Page.reload('#app=forum/board/showBoardPanel');
			}
		},
		saveTopic: function(){
			var topic = this.topic;
			
			this.elHide('bsave,bcancel');
			this.elShow('loading');
			
			var newdata = {
				'title': this.gel('tl').value,
				'body': this.editor.getContent(),
				'files':  L.isNull(this.filesWidget) ? topic.files : this.filesWidget.files
			};

			// var __self = this;
			NS.manager.topicSave(topic, newdata, function(d){
				d = d || {};
				var topicid = (d['id'] || 0)*1;

				// __self.close();
				setTimeout(function(){
					if (topicid > 0){
						Brick.Page.reload('#app=forum/topicview/showTopicViewPanel/'+topicid+'/');
					}else{
						Brick.Page.reload('#app=forum/board/showBoardPanel');
					}
				},100);
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
		if (!L.isNull(activePanel) && !activePanel.isDestroy()){
			activePanel.close();
		}
		if (L.isNull(activePanel) || activePanel.isDestroy()){
			activePanel = new TopicEditorPanel(topicid, ptopicid);
		}
		return activePanel;
	};
};