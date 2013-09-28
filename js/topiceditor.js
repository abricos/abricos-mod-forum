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
		R = NS.roles;

	var buildTemplate = this.buildTemplate;
	
	var TopicEditorPanel = function(topicid){
		
		this.topicid = topicid || 0;
		
		TopicEditorPanel.active = this;

		TopicEditorPanel.superclass.constructor.call(this, {fixedcenter: true});
	};
	YAHOO.extend(TopicEditorPanel, Brick.widget.Panel, {
		initTemplate: function(){
			buildTemplate(this, 'panel,frow');

			return this._TM.replace('panel');
		},
		onLoad: function(){
			var __self = this, TM = this._TM;
			this.gmenu = new NS.GlobalMenuWidget(TM.getEl('panel.gmenu'), 'list');
			NS.buildManager(function(man){
				__self.onBuildManager();
			});
		},
		onBuildManager: function(){
			var TM = this._TM,
				gel = function(n){ return TM.getEl('panel.'+n); },
				topic = this.topicid == 0 ? new NS.Topic() : NS.forumManager.list.get(this.topicid);
				__self = this;
				
			if (L.isNull(topic)){ return; }
			
			this.topic = topic;
			
			Dom.setStyle(TM.getEl('panel.tl'+(topic.id*1 > 0 ? 'new' : 'edit')), 'display', 'none');
			
			gel('tl').value = topic.title;
			gel('editor').innerHTML = topic.body; 
			
			var Editor = Brick.widget.Editor;
			this.editor = new Editor(this._TId['panel']['editor'], {
				width: '750px', height: '250px', 'mode': Editor.MODE_VISUAL
			});
			
			if (Brick.Permission.check('filemanager', '30') == 1){
				this.filesWidget = new Brick.mod.filemanager.AttachmentWidget(gel('files'), topic.files);
			}else{
				this.filesWidget = null;
				Dom.setStyle(gel('rfiles'), 'display', 'none');
			}
		},
		destroy: function(){
			this.editor.destroy();
			TopicEditorPanel.active = null;
			TopicEditorPanel.superclass.destroy.call(this);
		},
		onClick: function(el){
			var TId = this._TId, tp = TId['panel'];
			switch(el.id){
			case tp['bsave']: this.saveTopic(); return true;
			case tp['bcancel']: this.close(); return true;
			}
			return false;
		},
		saveTopic: function(){
			var TM = this._TM,
				topic = this.topic;
			
			Dom.setStyle(TM.getEl('panel.bsave'), 'display', 'none');
			Dom.setStyle(TM.getEl('panel.bcancel'), 'display', 'none');
			Dom.setStyle(TM.getEl('panel.loading'), 'display', '');
			
			var newdata = {
				'title': TM.getEl('panel.tl').value,
				'body': this.editor.getContent(),
				'files':  L.isNull(this.filesWidget) ? topic.files : this.filesWidget.files
			};

			var __self = this;
			NS.forumManager.topicSave(topic, newdata, function(d){
				d = d || {};
				var topicid = (d['id'] || 0)*1;

				__self.close();
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
	NS.TopicEditorPanel = TopicEditorPanel;

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