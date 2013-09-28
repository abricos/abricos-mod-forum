/*
 * @package Abricos
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: 'sys', files: ['data.js', 'container.js']},
        {name: 'forum', files: ['topiclist.js', 'lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var R = NS.roles;
	var buildTemplate = this.buildTemplate;
	
	var BoardPanel = function(){
		BoardPanel.superclass.constructor.call(this, {
			fixedcenter: true, width: '790px', height: '400px',
			overflow: false, 
			controlbox: 1
		});
	};
	YAHOO.extend(BoardPanel, Brick.widget.Panel, {
		initTemplate: function(){
			return buildTemplate(this, 'panel').replace('panel');
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
			this.list = new NS.TopicListWidget(TM.getEl('panel.list'));
			
			if (R['isAdmin']){
				// TM.elShow('panel.baddfrm');
			}
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
	
	NS.API.showBoardPanelWebos = function(){
		Brick.Page.reload('/bos/#app=forum/board/showBoardPanel');
	};
};