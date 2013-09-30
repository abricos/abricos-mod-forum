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
		R = NS.roles,
		BW = Brick.mod.widget.Widget;

	var LNG = this.language,
		TST = NS.TopicStatus;

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
			var TM = buildTemplate(this, 'panel');
			
			return TM.replace('panel', {
				'id': this.topicid
			});
		},
		onLoad: function(){
			this.widget = new NS.TopicViewWidget(this._TM.getEl('panel.widget'), this.topicid);
		}
	});
	NS.TopicViewPanel = TopicViewPanel;
	
	var TopicViewWidget = function(container, topicid, cfg){
		cfg = L.merge({
		}, cfg || {});
		
		TopicViewWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget,user,frow,empttitle' 
		}, topicid, cfg);
	};
	YAHOO.extend(TopicViewWidget, BW, {
		init: function(topicid, cfg){
			this.topicid = topicid;
			this.cfg = cfg;
			this.firstLoad = true;
		},
		buildTData: function(topicid, cfg){
			return {
				'id': topicid
			};
		},
		onLoad: function(topicid, cfg){
			this.gmenu = new NS.GlobalMenuWidget(this.gel('gmenu'), 'list');
			
			var __self = this;
			NS.initManager(function(){
				NS.manager.topicLoad(topicid, function(topic){
					__self.onTopicLoad(topic);
				});
			});
		},
		onTopicLoad: function(topic){
			this.topic = topic;
			if (L.isNull(this.topic)){ return; }
			// TODO: если this.topic=null необходимо показать "либо нет прав, либо проект удален"

			var TM = this._TM;
			
			if (this.firstLoad){ // первичная рендер
				this.firstLoad = false;
				
				// Инициализировать менеджер комментариев
				Brick.ff('comment', 'comment', function(){
					Brick.mod.comment.API.buildCommentTree({
						'container': TM.getEl('widget.comments'),
						'dbContentId': topic.detail.contentid,
						'config': {
							'onLoadComments': function(){
								aTargetBlank(TM.getEl('widget.topicbody'));
								aTargetBlank(TM.getEl('widget.comments'));
							}
							// ,
							// 'readOnly': project.w*1 == 0,
							// 'manBlock': L.isFunction(config['buildManBlock']) ? config.buildManBlock() : null
						},
						'instanceCallback': function(b){ }
					});
				});
			}

			var elColInfo = this.gel('colinfo');
			for (var i=1;i<=5;i++){
				Dom.removeClass(elColInfo, 'status'+i);
			}
			Dom.addClass(elColInfo, 'status'+topic.status);

			// Автор
			var user = NS.manager.users.get(topic.userid);

			this.elSetHTML({
				'author': TM.replace('user', {
					'uid': user.id, 'unm': user.getUserName()
				}),
				'dl': Brick.dateExt.convert(topic.date, 3, true),
				'dlt': Brick.dateExt.convert(topic.date, 4),
				'status': LNG['status'][topic.status],
				'title': topic.title.length > 0 ? topic.title : this._TM.replace('empttitle'),
				'topicbody': topic.detail.body
			});

			// закрыть все кнопки, открыть по ролям
			this.elHide('bopen,bclose,beditor,bremove');
			
			var isMyTopic = user.id*1 == Brick.env.user.id*1;
			if (topic.status == TST.OPENED){
				if (R['isModer']){
					this.elShow('beditor,bremove,bclose'); 
				}else if (isMyTopic){
					this.elHide('panel.beditor,bremove'); 
				}
			}
			
			var fs = topic.detail.files;
			// показать прикрепленные файлы
			if (fs.length > 0){
				this.elShow('files');
				
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
				this.gel('ftable').innerHTML = lst;
			}else{
				this.elHide('files');
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
	NS.TopicViewWidget = TopicViewWidget;
	
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