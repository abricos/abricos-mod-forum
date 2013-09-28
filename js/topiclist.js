/*
 * @package Abricos
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['container.js']},
        {name: 'uprofile', files: ['users.js']},
        {name: 'forum', files: ['lib.js']}
	]
};
Component.entryPoint = function(){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var NS = this.namespace, 
		TMG = this.template,
		API = NS.API,
		R = NS.roles;
	
	var UP = Brick.mod.uprofile;
	
	var LNG = Brick.util.Language.getc('mod.forum');

	var initCSS = false, buildTemplate = function(w, ts){
		if (!initCSS){
			Brick.util.CSS.update(Brick.util.CSS['forum']['topiclist']);
			delete Brick.util.CSS['forum']['topiclist'];
			initCSS = true;
		}
		w._TM = TMG.build(ts); w._T = w._TM.data; w._TId = w._TM.idManager;
	};
	

	var TST = NS.TopicStatus;
	
	var TopicListWidget = function(container){
		this.init(container);
	};
	TopicListWidget.prototype = {
		init: function(container){
		
			NS.forumManager.topicsChangedEvent.subscribe(this.onMesssagesChangedEvent, this, true);

			this.list = NS.forumManager.list;
			
			buildTemplate(this, 'widget,table,row,user');
			container.innerHTML = this._TM.replace('widget');

			var __self = this;
			E.on(container, 'click', function(e){
                if (__self._onClick(E.getTarget(e))){ E.preventDefault(e); }
			});
			this.render();
		},
		
		onMesssagesChangedEvent: function(e1, e2){
			this.render();
		},
		
		render: function(){
			
			var TM = this._TM, 
				lst = "";
			
			var arr = [];
			this.list.foreach(function(msg){
				arr[arr.length] = msg;
			});
			arr = arr.sort(function(m1, m2){
				var t1 = m1.updDate.getTime(),
					t2 = m2.updDate.getTime();
				
				if (!L.isNull(m1.cmtDate)){
					t1 = Math.max(t1, m1.cmtDate.getTime());
				}
				if (!L.isNull(m2.cmtDate)){
					t2 = Math.max(t2, m2.cmtDate.getTime());
				}
				if (t1 > t2){ return -1; }
				if (t1 < t2){ return 1; }
				return 0;
			});
			for (var i=0; i<arr.length; i++){
				var msg = arr[i];
				
				var user = NS.forumManager.users.get(msg.userid);
				var d = {
					'id': msg.id,
					'tl': msg.title,
					'cmt': msg.cmt,
					'cmtuser': TM.replace('user', {'uid': user.id, 'unm': user.getUserName()}),
					'cmtdate': Brick.dateExt.convert(msg.updDate),
					'closed': msg.isClosed() ? 'closed' : '',
					'removed': msg.isRemoved() ? 'removed' : ''
				};
				if (msg.cmt > 0){
					var user = NS.forumManager.users.get(msg.cmtUserId);
					d['cmtuser'] =  TM.replace('user', {'uid': user.id, 'unm': user.getUserName()});
					d['cmtdate'] = Brick.dateExt.convert(msg.cmtDate);
				}
				
				lst += TM.replace('row', d);
			}
			TM.getEl('widget.table').innerHTML = TM.replace('table', {'rows': lst});
		},
		
		_onClick: function(el){
			return false;
		},

		destroy: function(){
			NS.forumManager.topicsChangedEvent.unsubscribe(this.onMesssagesChangedEvent);
		}
	};
	NS.TopicListWidget = TopicListWidget;
	
	
};