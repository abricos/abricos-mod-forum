/*
 * @package Abricos
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['container.js']},
        {name: 'filemanager', files: ['lib.js']},
        {name: 'uprofile', files: ['users.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		R = NS.roles;
	
	var LNG = this.language,
		MST = NS.TopicStatus;

	var buildTemplate = this.buildTemplate;
	
	var aTargetBlank = function(el){
		if (el.tagName == 'A'){
			el.target = "_blank";
		}else if (el.tagName == 'IMG'){
			el.style.maxWidth = "100%";
			el.style.height = "auto";
		}
		var chs = el.childNodes;
		for (var i=0;i<chs.length;i++){
			if (chs[i]){ aTargetBlank(chs[i]); }
		}
	};
	
	var TopicViewPanel = function(topicid){
		this.topicid = topicid;
		
		TopicViewPanel.superclass.constructor.call(this, {
			fixedcenter: true, width: '790px', height: '400px',
			overflow: false, 
			controlbox: 1
		});
	};
	YAHOO.extend(TopicViewPanel, Brick.widget.Panel, {
		initTemplate: function(){
			buildTemplate(this, 'panel,user,frow,empttitle');
			
			var topic = this.topic;

			return this._TM.replace('panel', {
				'id': this.topicid
			});
		},
		onLoad: function(){
			var __self = this, TM = this._TM;
			this.gmenu = new NS.GlobalMenuWidget(TM.getEl('panel.gmenu'), 'list');
			
			NS.buildManager(function(){
				__self.onBuildManager();
			});
		},
		onBuildManager: function(){

			this.topic = NS.forumManager.list.get(this.topicid);
			if (L.isNull(this.topic)){ return; }
			// TODO: если this.topic=null необходимо показать "либо нет прав, либо проект удален"

			var topic = this.topic,
				TM = this._TM,
				__self = this;
			
			TM.getEl('panel.title').innerHTML = topic.title.length > 0 ? topic.title : this._TM.replace('empttitle')

			
			this.firstLoad = true;
			
			// запросить дополнительные данные - описание
			NS.forumManager.topicLoad(topic.id, function(){
				__self.renderTopic();
			});
			
			NS.forumManager.topicsChangedEvent.subscribe(this.onTopicsChanged, this, true);
		},
		destroy: function(){
			TopicViewPanel.superclass.destroy.call(this);
		},
		onTopicsChanged: function(){
			this.renderTopic();
		},
		renderTopic: function(){
			var TM = this._TM, topic = this.topic, 
				__self = this, 
				gel = function(nm){ return TM.getEl('panel.'+nm); };
			
			gel('title').innerHTML = topic.title.length > 0 ? topic.title : TM.replace('empttitle');
			gel('topicbody').innerHTML = topic.body;
			
			if (this.firstLoad){ // первичная рендер
				this.firstLoad = false;
				
				// Инициализировать менеджер комментариев
				Brick.ff('comment', 'comment', function(){
					Brick.mod.comment.API.buildCommentTree({
						'container': TM.getEl('panel.comments'),
						'dbContentId': topic.ctid,
						'config': {
							'onLoadComments': function(){
								aTargetBlank(TM.getEl('panel.topicbody'));
								aTargetBlank(TM.getEl('panel.comments'));
							}
							// ,
							// 'readOnly': project.w*1 == 0,
							// 'manBlock': L.isFunction(config['buildManBlock']) ? config.buildManBlock() : null
						},
						'instanceCallback': function(b){ }
					});
				});
			}

			var elColInfo = gel('colinfo');
			for (var i=1;i<=5;i++){
				Dom.removeClass(elColInfo, 'status'+i);
			}
			Dom.addClass(elColInfo, 'status'+topic.status);

			// Статус
			gel('status').innerHTML = LNG['status'][topic.status];
			
			// Автор
			var user = NS.forumManager.users.get(topic.userid);
			gel('author').innerHTML = TM.replace('user', {
				'uid': user.id, 'unm': user.getUserName()
			});
			// Создана
			gel('dl').innerHTML = Brick.dateExt.convert(topic.date, 3, true);
			gel('dlt').innerHTML = Brick.dateExt.convert(topic.date, 4);

			// закрыть все кнопки, открыть по ролям 
			TM.elHide('panel.bopen,bclose,beditor,bremove');
			
			var isMyTopic = user.id*1 == Brick.env.user.id*1;
			if (topic.status == MST.OPENED){
				if (R['isModer']){
					TM.elShow('panel.beditor,bremove,bclose'); 
				}else if (isMyTopic){
					TM.elShow('panel.beditor,bremove'); 
				}
			}
			
			var fs = topic.files;
			// показать прикрепленные файлы
			if (fs.length > 0){
				TM.elShow('panel.files');
				
				var alst = [], lst = "";
				for (var i=0;i<fs.length;i++){
					var f = fs[i];
					var lnk = new Brick.mod.filemanager.Linker({
						'id': f['id'],
						'name': f['nm']
					});
					alst[alst.length] = TM.replace('frow', {
						'fid': f['id'],
						'nm': f['nm'],
						'src': lnk.getSrc()
					});
				}
				lst = alst.join('');
				TM.getEl('panel.ftable').innerHTML = lst;
			}else{
				TM.elHide('panel.files');
			}
		},
		onClick: function(el){
			var tp = this._TId['panel'];
			switch(el.id){
			
			case tp['beditor']: this.topicEditorShow(); return true;
			
			case tp['bclose']: 
			case tp['bclosens']: this.topicClose(); return true;
			case tp['bcloseno']: this.topicCloseCancel(); return true;
			case tp['bcloseyes']: this.topicCloseMethod(); return true;

			case tp['bremove']: this.topicRemove(); return true;
			case tp['bremoveno']: this.topicRemoveCancel(); return true;
			case tp['bremoveyes']: this.topicRemoveMethod(); return true;
			}
			return false;
		},
		_shLoading: function(show){
			var TM = this._TM;
			TM.elShowHide('panel.buttons', !show);
			TM.elShowHide('panel.bloading', show);
		},
		
		
		// закрыть сообщение
		topicClose: function(){ 
			var TM = this._TM;
			TM.elHide('panel.manbuttons');
			TM.elShow('panel.dialogclose');
		},
		topicCloseCancel: function(){
			var TM = this._TM;
			TM.elShow('panel.manbuttons');
			TM.elHide('panel.dialogclose');
		},
		topicCloseMethod: function(){
			this.topicCloseCancel();
			var __self = this;
			this._shLoading(true);
			NS.forumManager.topicClose(this.topic.id, function(){
				__self._shLoading(false);
			});
		},

		topicRemove: function(){
			var TM = this._TM;
			TM.elHide('panel.manbuttons');
			TM.elShow('panel.dialogremove');
		},
		topicRemoveCancel: function(){
			var TM = this._TM;
			TM.elShow('panel.manbuttons');
			TM.elHide('panel.dialogremove');
		},
		topicRemoveMethod: function(){
			this.topicRemoveCancel();
			var __self = this;
			this._shLoading(true);
			NS.forumManager.topicRemove(this.topic.id, function(){
				__self._shLoading(false);
			});
		}
	});
	NS.TopicViewPanel = TopicViewPanel;
	
	var activePanel = null;
	NS.API.showTopicViewPanel = function(topicid, ptopicid){
		if (!L.isNull(activePanel) && !activePanel.isDestroy()){
			activePanel.close();
		}
		if (L.isNull(activePanel) || activePanel.isDestroy()){
			activePanel = new TopicViewPanel(topicid, ptopicid);
		}
		return activePanel;
	};

};