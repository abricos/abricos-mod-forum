/*
 * @package Abricos
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['container.js', 'editor.js']},
        {name: 'forum', files: ['lib.js', 'roles.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		R = NS.roles;

	var buildTemplate = this.buildTemplate;
	
	var ForumEditorPanel = function(forumid){
		this.forumid = forumid || 0;
		
		ForumEditorPanel.superclass.constructor.call(this, {fixedcenter: true});
	};
	YAHOO.extend(ForumEditorPanel, Brick.widget.Panel, {
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
				forum = this.forumid == 0 ? new NS.Forum() : NS.manager.forumList.get(this.forumid);
				__self = this;

			if (L.isNull(forum)){ return; }
			
			this.forum = forum;
			
			Dom.setStyle(TM.getEl('panel.tl'+(forum.id*1 > 0 ? 'new' : 'edit')), 'display', 'none');
			
			gel('tl').value = forum.title;
			gel('dsc').innerHTML = forum.descript; 

			var Editor = Brick.widget.Editor;
			this.editor = new Editor(gel('dsc'), {
				width: '750px', height: '250px', 'mode': Editor.MODE_VISUAL
			});
		},
		destroy: function(){
			ForumEditorPanel.superclass.destroy.call(this);
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
				forum = this.forum;
			
			Dom.setStyle(TM.getEl('panel.bsave'), 'display', 'none');
			Dom.setStyle(TM.getEl('panel.bcancel'), 'display', 'none');
			Dom.setStyle(TM.getEl('panel.loading'), 'display', '');
			
			var newdata = {
				'title': TM.getEl('panel.tl').value,
				'body': this.editor.getContent()
			};

			var __self = this;
			NS.manager.forumSave(forum, newdata, function(d){
				d = d || {};
				var forumid = (d['id'] || 0)*1;

				__self.close();
				setTimeout(function(){
					if (forumid > 0){
						Brick.Page.reload('#app=forum/topicview/showTopicViewPanel/'+forumid+'/');
					}else{
						Brick.Page.reload('#app=forum/board/showBoardPanel');
					}
				},100);
			});
		}
	});
	NS.ForumEditorPanel = ForumEditorPanel;

	// создать сообщение
	NS.API.showCreateForumPanel = function(){
		return NS.API.showForumEditorPanel(0);
	};

	var activePanel = null;
	NS.API.showForumEditorPanel = function(forumid, pforumid){
		if (!L.isNull(activePanel) && !activePanel.isDestroy()){
			activePanel.close();
		}
		if (L.isNull(activePanel) || activePanel.isDestroy()){
			activePanel = new ForumEditorPanel(forumid, pforumid);
		}
		return activePanel;
	};
};