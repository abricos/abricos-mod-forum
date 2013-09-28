/*
 * @package Abricos
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = { 
	mod:[
        {name: 'uprofile', files: ['users.js']},
        {name: 'sys', files: ['item.js','date.js']},
        {name: 'forum', files: ['roles.js']}
	]		
};
Component.entryPoint = function(NS){

	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang,
		R = NS.roles; 

	var NSys = Brick.mod.sys;
	NS.Item = NSys.Item;
	NS.ItemList = NSys.ItemList;

	var UP = Brick.mod.uprofile;

	var buildTemplate = this.buildTemplate;
	buildTemplate({});
	
	// дополнить эксперементальными функциями менеджер шаблонов
	var TMP = Brick.Template.Manager.prototype;
	TMP.elHide = function(els){ this.elShowHide(els, false); };
	TMP.elShow = function(els){ this.elShowHide(els, true); };
	TMP.elShowHide = function(els, show){
		if (L.isString(els)){
			var arr = els.split(','), tname = '';
			els = [];
			for (var i=0;i<arr.length;i++){
				var arr1 = arr[i].split('.');
				if (arr1.length == 2){
					tname = L.trim(arr1[0]);
					els[els.length] = L.trim(arr[i]);
				}
				els[els.length] = tname+'.'+L.trim(arr[i]);
			}
		}
		if (!L.isArray(els)){ return; }
		for (var i=0;i<els.length;i++){
			var el = this.getEl(els[i]);
			Dom.setStyle(el, 'display', show ? '' : 'none');
		}
	};
	
	var Forum = function(d){
		d = L.merge({
			'tl': '',
			'dsc': ''
		}, d || {});
		Forum.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Forum, NSys.Item, {
		update: function(d){ 
			this.title = d['tl'];
			this.descript = d['dsc'];
		}
	});
	NS.Forum = Forum;

	var ForumList = function(d){
		ForumList.superclass.constructor.call(this, d, Forum);
	};
	YAHOO.extend(ForumList, NSys.ItemList, {});
	NS.ForumList = ForumList;
	
	var TopicStatus = {
		'OPENED'		: 0,	// открыта
		'CLOSED'		: 1,	// закрыта
		'REMOVED'		: 2		// удалена
	};
	NS.TopicStatus = TopicStatus;
	
	var Topic = function(d){
		d = L.merge({
			'fmid': 0,
			'tl': '',
			'dl': 0,
			'udl': 0,
			'cmt': null,
			'cmtdl': 0,
			'cmtuid': null,
			'st': 0,
			'stuid': 0,
			'stdl': 0,
			'dl': 0,
			'uid': Brick.env.user.id
		}, d || {});
		Topic.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Topic, NSys.Item, {
		init: function(d){
			Topic.superclass.init.call(this, d);
			
			// была ли загрузка оставшихся данных?
			this.isLoad = false;

			// описание задачи
			this.body = '';
			this.files = [];
		},
		update: function(d){ 
			this.title = d['tl'];								// заголовок
			this.userid = d['uid'];								// идентификатор автора
			this.forumid = d['fmid'];
			this.date = NSys.dateToClient(d['dl']); 				// дата создания 

			this.updDate = NSys.dateToClient(d['udl']); 			// дата создания 
			
			this.cmt = (L.isNull(d['cmt']) ? 0 : d['cmt'])*1;	// кол-во сообщений
			this.cmtDate = NSys.dateToClient(d['cmtdl']);			// дата последнего сообщения
			this.cmtUserId = L.isNull(d['cmtuid']) ? 0 : d['cmtuid'];	// дата последнего сообщения
			
			this.status = d['st']*1;
			this.stUserId = d['stuid'];
			this.stDate = NSys.dateToClient(d['stdl']);
		},
		setData: function(d){
			this.isLoad = true;
			this.body = d['bd'];
			this.ctid = d['ctid'];
			this.files = d['files'];
			this.update(d);
		},
		isRemoved: function(){
			return this.status*1 == TopicStatus.REMOVED;
		},
		isClosed: function(){
			return this.status*1 == TopicStatus.CLOSED;
		}
	});
	NS.Topic = Topic;
	
	var TopicList = function(d){
		TopicList.superclass.constructor.call(this, d, Topic);
	};
	YAHOO.extend(TopicList, NSys.ItemList, {});
	NS.TopicList = TopicList;
	
	var Manager = function(inda){
		this.init(inda);
	};
	Manager.prototype = {
		init: function(inda){

			this._hlid = 0;
			this.topicsChangedEvent = new YAHOO.util.CustomEvent("topicsChangedEvent");

			this.forums = new ForumList(); 
			this.list = new TopicList();
			this.listUpdate(inda['board']);

			this.users = UP.viewer.users;
			this.users.update(inda['users']);

			this.lastUpdateTime = new Date();
			
			E.on(document.body, 'mousemove', this.onMouseMove, this, true);
		},
		
		onMouseMove: function(evt){
			var ctime = (new Date()).getTime(), ltime = this.lastUpdateTime.getTime();
			
			if ((ctime-ltime)/(1000*60) < 3){ return; }
			// if ((ctime-ltime)/(1000) < 5){ return; }
			
			this.lastUpdateTime = new Date();
			
			// получения времени сервера необходимое для синхронизации
			// и проверка обновлений в задачах
			this.ajax({'do': 'sync'}, function(r){});
		},

		listUpdate: function(data){
			// обновить данные по сообщениям: новые - создать, существующие - обновить
			var objs = {},
				n = [], // новые 
				u = [], // обновленые
				d = []; // удаленные
			
			var hlid = this._hlid*1;
			
			for (var id in data){
				var di = data[id];
				hlid = Math.max(di['udl']*1, hlid);
				var topic = this.list.get(id); 
				if (L.isNull(topic)){ // новая задача
					topic = new Topic(di);
					this.list.add(topic);
					n[n.length] = topic;
				}else{
					topic.update(di);
					u[u.length] = topic;
				}
				objs[id] = topic;
			}
			this._hlid = hlid;
			return {
				'n': n,
				'u': u,
				'd': d
			};
		},
		
		_ajaxBeforeResult: function(r){
			if (L.isNull(r)){ return null; }
			if (r.u*1 != Brick.env.user.id){ // пользователь разлогинился
				Brick.Page.reload();
				return null;
			}
			
			var chgs = r['changes'];
			
			if (L.isNull(chgs)){ return null; } // изменения не зафиксированы
			
			this.users.update(chgs['users']);
			return this.listUpdate(chgs['board']);
		},
		
		_ajaxResult: function(upd){
			if (L.isNull(upd)){ return null; }
			if (upd['n'].length == 0 && upd['u'].length == 0 && upd['d'].length == 0){ return null; }
			this.topicsChangedEvent.fire(upd);
		},
		
		ajax: function(d, callback){
			// d['hlid'] = this.history.lastId();
			d['hlid'] = this._hlid;
			
			// все запросы по модулю проходят через этот менеджер.
			// ко всем запросам добавляется идентификатор последнего обновления
			// если на сервере произошли изменения, то они будут 
			// зафиксированны у этого пользователя
			var __self = this;
			Brick.ajax('forum', {
				'data': d,
				'event': function(request){
					if (L.isNull(request.data)){ return; }

					var upd = __self._ajaxBeforeResult(request.data);

					// применить результат запроса
					callback(request.data.r);
					
					__self._ajaxResult(upd);
				}
			});
		},
		_topicAJAX: function(topicid, cmd, callback){
			callback = callback || function(){};
			var __self = this;
			this.ajax({'do': cmd, 'topicid': topicid }, function(r){
				__self._setLoadedTopicData(r);
				callback(r);
			});
		},
		_setLoadedTopicData: function(d){
			if (L.isNull(d)){ return; }
			var topic = this.list.get(d['id']);
			if (L.isNull(topic)){ return; }
			
			topic.setData(d);
		},
		topicLoad: function(topicid, callback){
			callback = callback || function(){};
			var topic = this.list.get(topicid);
	
			if (L.isNull(topic) || topic.isLoad){
				callback();
				return true;
			}
			this._topicAJAX(topicid, 'topic', callback);
		},
		topicSave: function(topic, d, callback){
			callback = callback || function(){};
			var __self = this;
			
			d = L.merge({
				'id': 0, 'title': '',
				'body': '',
				'files': {}
			}, d || {});
			
			var dtopic = {
				'id': topic.id,
				'tl': d['title'],
				'bd': d['body'],
				'files': d['files']
			};
			this.ajax({
				'do': 'topicsave',
				'topic': dtopic
			}, function(r){
				__self._setLoadedTopicData(r);
				callback(r);
			});
		},
		topicClose: function(topicid, callback){ // закрыть сообщение
			this._topicAJAX(topicid, 'topicclose', callback);
		},
		topicRemove: function(topicid, callback){ // удалить сообщение
			this._topicAJAX(topicid, 'topicremove', callback);
		},
		
		forumSave: function(forum, d, callback){
			callback = callback || function(){};
			var __self = this;
			
			d = L.merge({'id': 0, 'title': '', 'body': ''}, d || {});
			
			var dforum = {
				'id': forum.id,
				'tl': d['title'],
				'bd': d['body']
			};
			this.ajax({
				'do': 'forumsave',
				'forum': dforum
			}, function(r){
				__self._setLoadedTopicData(r);
				callback(r);
			});
		},
		
		
	};
	NS.forumManager = NS.manager = null;
	
	NS.buildManager = function(callback){
		if (!L.isNull(NS.manager)){
			callback(NS.manager);
			return;
		}
		R.load(function(){
			Brick.ajax('forum', {
				'data': {'do': 'init'},
				'event': function(request){
					NS.forumManager = NS.manager = new Manager(request.data);
					callback(NS.manager);
				}
			});
		});
	};
	
	var GlobalMenuWidget = function(container, page){
		this.init(container, page);
	};
	GlobalMenuWidget.prototype = {
		init: function(container, page){
			buildTemplate(this, 'gbmenu');
			
			container.innerHTML = this._TM.replace('gbmenu', {
				'list': page == 'list' ? 'current' : '',
				'config': page == 'config' ? 'current' : ''
			});
		}
	};
	NS.GlobalMenuWidget = GlobalMenuWidget;
	
};