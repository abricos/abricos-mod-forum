/*
@version $Id$
@package Abricos
@copyright Copyright (C) 2008 Abricos All rights reserved.
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['data.js', 'container.js']},
        {name: 'forum', files: ['msglist.js', 'lib.js']}
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

	Brick.util.CSS.update(Brick.util.CSS['forum']['board']);
	
	var buildTemplate = function(w, ts){w._TM = TMG.build(ts); w._T = w._TM.data; w._TId = w._TM.idManager;};
	
	var BoardPanel = function(){
		BoardPanel.superclass.constructor.call(this, {
			fixedcenter: true, width: '790px', height: '400px',
			overflow: false, 
			controlbox: 1
		});
	};
	YAHOO.extend(BoardPanel, Brick.widget.Panel, {
		initTemplate: function(){
			buildTemplate(this, 'panel');
			return this._TM.replace('panel');
		},
		onLoad: function(){
			var TM = this._TM, __self = this;
			
			this.gmenu = new NS.GlobalMenuWidget(TM.getEl('panel.gmenu'), 'list');
			
			NS.buildManager(function(){
				__self.onBuildManager();
			});
		},
		onBuildManager: function(){
			var TM = this._TM;
			this.list = new NS.MessageListWidget(TM.getEl('panel.list'));
			
			if (R['isWrite']){
				TM.elShow('panel.baddmsg');
			}
		},
		destroy: function(){
			this.navigate.destroy();
			this.list.destroy();
			BoardPanel.superclass.destroy.call(this);
		}
	});
	NS.BoardPanel = BoardPanel;
	
	var activePanel = null;
	NS.API.showBoardPanel = function(){
		if (L.isNull(activePanel) || activePanel.isDestroy()){
			activePanel = new BoardPanel();
		}
		return activePanel;
	};
	
	API.showBoardPanelWebos = function(){
		Brick.Page.reload('/bos/#app=forum/board/showBoardPanel');
	};
};