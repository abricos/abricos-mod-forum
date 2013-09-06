/*
@version $Id$
@package Abricos
@copyright Copyright (C) 2011 Abricos All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
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
	
	var MessageEditorPanel = function(messageid){
		
		this.messageid = messageid || 0;
		
		MessageEditorPanel.active = this;

		MessageEditorPanel.superclass.constructor.call(this, {fixedcenter: true});
	};
	YAHOO.extend(MessageEditorPanel, Brick.widget.Panel, {
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
				message = this.messageid == 0 ? new NS.Message() : NS.forumManager.list.get(this.messageid);
				__self = this;
				
			if (L.isNull(message)){ return; }
			
			this.message = message;
			
			Dom.setStyle(TM.getEl('panel.tl'+(message.id*1 > 0 ? 'new' : 'edit')), 'display', 'none');
			
			gel('tl').value = message.title;
			gel('editor').innerHTML = message.body; 
			
			var Editor = Brick.widget.Editor;
			this.editor = new Editor(this._TId['panel']['editor'], {
				width: '750px', height: '250px', 'mode': Editor.MODE_VISUAL
			});
			
			if (Brick.Permission.check('filemanager', '30') == 1){
				this.filesWidget = new Brick.mod.filemanager.AttachmentWidget(gel('files'), message.files);
			}else{
				this.filesWidget = null;
				Dom.setStyle(gel('rfiles'), 'display', 'none');
			}
		},
		destroy: function(){
			this.editor.destroy();
			MessageEditorPanel.active = null;
			MessageEditorPanel.superclass.destroy.call(this);
		},
		onClick: function(el){
			var TId = this._TId, tp = TId['panel'];
			switch(el.id){
			case tp['bsave']: this.saveMessage(); return true;
			case tp['bcancel']: this.close(); return true;
			}
			return false;
		},
		saveMessage: function(){
			var TM = this._TM,
				message = this.message;
			
			Dom.setStyle(TM.getEl('panel.bsave'), 'display', 'none');
			Dom.setStyle(TM.getEl('panel.bcancel'), 'display', 'none');
			Dom.setStyle(TM.getEl('panel.loading'), 'display', '');
			
			var newdata = {
				'title': TM.getEl('panel.tl').value,
				'body': this.editor.getContent(),
				'files':  L.isNull(this.filesWidget) ? message.files : this.filesWidget.files
			};

			var __self = this;
			NS.forumManager.messageSave(message, newdata, function(d){
				d = d || {};
				var messageid = (d['id'] || 0)*1;

				__self.close();
				setTimeout(function(){
					if (messageid > 0){
						Brick.Page.reload('#app=forum/msgview/showMessageViewPanel/'+messageid+'/');
					}else{
						Brick.Page.reload('#app=forum/board/showBoardPanel');
					}
				},100);
			});
		}
	});
	NS.MessageEditorPanel = MessageEditorPanel;

	// создать сообщение
	NS.API.showCreateMessagePanel = function(){
		return NS.API.showMessageEditorPanel(0);
	};

	var activePanel = null;
	NS.API.showMessageEditorPanel = function(messageid, pmessageid){
		if (!L.isNull(activePanel) && !activePanel.isDestroy()){
			activePanel.close();
		}
		if (L.isNull(activePanel) || activePanel.isDestroy()){
			activePanel = new MessageEditorPanel(messageid, pmessageid);
		}
		return activePanel;
	};
};