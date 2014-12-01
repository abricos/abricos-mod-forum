/*!
 * Module for Abricos Platform (http://abricos.org)
 * Copyright 2008-2014 Alexander Kuzmin <roosit@abricos.org>
 * Licensed under the MIT license
 */

var Component = new Brick.Component();
Component.requires = {
    yui: ['model', 'model-list'],
    mod: [
        {name: 'sys', files: ['item.js', 'date.js']},
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        L = Y.Lang;


    var NSys = Brick.mod.sys;
    NS.Item = NSys.Item;
    NS.ItemList = NSys.ItemList;

    var Forum = function(d){
        d = Y.merge({
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
        'OPENED': 0,	// открыта
        'CLOSED': 1,	// закрыта
        'REMOVED': 2		// удалена
    };
    NS.TopicStatus = TopicStatus;

    var Topic = function(d){
        d = Y.merge({
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

            this.cmt = (L.isNull(d['cmt']) ? 0 : d['cmt']) * 1;	// кол-во сообщений
            this.cmtDate = NSys.dateToClient(d['cmtdl']);			// дата последнего сообщения
            this.cmtUserId = L.isNull(d['cmtuid']) ? 0 : d['cmtuid'];	// дата последнего сообщения

            this.status = d['st'] * 1;
            this.stUserId = d['stuid'];
            this.stDate = NSys.dateToClient(d['stdl']);

            if (L.isValue(d['dtl'])){
                this.detail = new NS.TopicDetail(d['dtl']);
            }
        },
        isRemoved: function(){
            return this.status * 1 == TopicStatus.REMOVED;
        },
        isClosed: function(){
            return this.status * 1 == TopicStatus.CLOSED;
        }
    });
    NS.Topic = Topic;

    var TopicDetail = function(d){
        d = Y.merge({
            'bd': '',
            'ctid': 0,
            'files': []
        }, d || {});
        TopicDetail.superclass.constructor.call(this, d);
    };
    YAHOO.extend(TopicDetail, NS.Item, {
        update: function(d){
            this.body = d['bd'];
            this.contentid = d['ctid'];
            this.files = d['files'];
        }
    });
    NS.TopicDetail = TopicDetail;

    var TopicList = function(d){
        TopicList.superclass.constructor.call(this, d, Topic);
    };
    YAHOO.extend(TopicList, NSys.ItemList, {});
    NS.TopicList = TopicList;

};