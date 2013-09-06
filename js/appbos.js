/*
 * @package Abricos
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.entryPoint = function(){
	
	if (Brick.Permission.check('forum', '10') != 1){ return; }
	
	var os = Brick.mod.bos;
	
	var app = new os.Application(this.moduleName);
	app.icon = '/modules/forum/images/app_icon.png';
	app.entryComponent = 'board';
	app.entryPoint = 'showBoardPanel';
	
	os.ApplicationManager.register(app);
	
};
