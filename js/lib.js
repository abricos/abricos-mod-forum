/*
 * @package Abricos
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = { 
	mod:[
        {name: 'widget', files: ['lib.js']},
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

	NS.lif = function(f){return L.isFunction(f) ? f : function(){}; };
	NS.life = function(f, p1, p2, p3, p4, p5, p6, p7){
		f = NS.lif(f); f(p1, p2, p3, p4, p5, p6, p7);
	};
	
	var buildTemplate = this.buildTemplate;
	buildTemplate({});

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
			'upd': 0,
			'cmt': null,
			'cmtdl': 0,
			'cmtuid': null,
			'st': 0,
			'stuid': 0,
			'stdl': 0,
			'uid': Brick.env.user.id,
			'dtl': null
		}, d || {});
		Topic.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Topic, NSys.Item, {
		init: function(d){
			this.detail = null;
			
			Topic.superclass.init.call(this, d);
		},
		update: function(d){ 
			this.title = d['tl'];								// заголовок
			this.userid = d['uid'];								// идентификатор автора
			this.forumid = d['fmid'];
			this.date = NSys.dateToClient(d['dl']); 				// дата создания 

			this.updDate = NSys.dateToClient(d['upd']); 			// дата создания 
			
			this.cmt = (L.isNull(d['cmt']) ? 0 : d['cmt'])*1;	// кол-во сообщений
			this.cmtDate = NSys.dateToClient(d['cmtdl']);			// дата последнего сообщения
			this.cmtUserId = L.isNull(d['cmtuid']) ? 0 : d['cmtuid'];	// дата последнего сообщения
			
			this.status = d['st']*1;
			this.stUserId = d['stuid'];
			this.stDate = NSys.dateToClient(d['stdl']);
	
			if (L.isValue(d['dtl'])){
				this.detail = new NS.TopicDetail(d['dtl']);
			}
		},
		isRemoved: function(){
			return this.status*1 == TopicStatus.REMOVED;
		},
		isClosed: function(){
			return this.status*1 == TopicStatus.CLOSED;
		}
	});
	NS.Topic = Topic;
	
	var TopicDetail = function(d){
		d = L.merge({
			'bd': '',
			'ctid': 0
		}, d || {});
		TopicDetail.superclass.constructor.call(this, d);
	};
	YAHOO.extend(TopicDetail, NS.Item, {
		update: function(d){
			this.body			= d['bd'];
			this.contentid		= d['ctid'];
			this.files			= d['files'];
		}
	});		
	NS.TopicDetail = TopicDetail;
	
	var TopicList = function(d){
		TopicList.superclass.constructor.call(this, d, Topic);
	};
	YAHOO.extend(TopicList, NSys.ItemList, {});
	NS.TopicList = TopicList;
	
	var Manager = function(callback){
		this.init(callback);
	};
	Manager.prototype = {
		init: function(callback){
			NS.manager = this;

			this.forums = new ForumList(); 
			this.users = Brick.mod.uprofile.viewer.users;
			
			R.load(function(){
				NS.life(callback, NS.manager);
			});
		},
		
		ajax: function(data, callback){
			data = data || {};

			Brick.ajax('{C#MODNAME}', {
				'data': data,
				'event': function(request){
					NS.life(callback, request.data);
				}
			});
		},
		
		_updateUserList: function(d){
			if (!L.isValue(d) || !L.isValue(d['users']) || !L.isValue(d['users']['list'])){
				return null;
			}
			this.users.update(d['users']['list']);
		},
		
		_updateTopicList: function(d){
			if (!L.isValue(d) || !L.isValue(d['topics']) || !L.isValue(d['topics']['list'])){
				return null;
			}
			this._updateUserList(d);
			return new NS.TopicList(d['topics']['list']);
		},
		topicListLoad: function(callback){
			var __self = this;
			this.ajax({
				'do': 'topiclist'
			},function(d){
				var list = __self._updateTopicList(d);
				NS.life(callback, list);
			});
		},

		topicLoad: function(topicid, callback){

			var __self = this;
			this.ajax({
				'do': 'topic',
				'topicid': topicid
			}, function(d){
				var topic = null;
				if (L.isValue(d) && L.isValue(d['topic'])){
					topic = new NS.Topic(d['topic']);
					__self._updateUserList(d);
				}
				NS.life(callback, topic);
			});
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
		}
		
		
	};
	NS.manager = null;
	
	NS.initManager = function(callback){
		if (L.isNull(NS.manager)){
			NS.manager = new Manager(callback);
		}else{
			NS.life(callback, NS.manager);
		}
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