var Component = new Brick.Component();
Component.requires = {};
Component.entryPoint = function(NS){

    if (!Brick.mod.bos && Brick.mod.bos.onlineManager){
        return;
    }

    var Dom = YAHOO.util.Dom,
        E = YAHOO.util.Event,
        L = YAHOO.lang;

    var buildTemplate = this.buildTemplate;

    var OnlineWidget = function(container, rs){
        OnlineWidget.superclass.constructor.call(this, container, rs);
    };
    YAHOO.extend(OnlineWidget, Brick.mod.bos.OnlineWidget, {
        init: function(container, rs){
            var TM = buildTemplate(this, 'widget,item,rss'), lst = "";

            for (var i = 0, di; i < rs.length; i++){
                di = rs[i];
                lst += TM.replace('item', {
                    'id': di['id'],
                    'dt': Brick.dateExt.convert(di.updDate),
                    'tl': di['tl']
                });
            }
            var isRSS = Brick.AppRoles.check('rss', '10'),
                sRSS = !isRSS ? '' : TM.replace('rss');

            container.innerHTML = TM.replace('widget', {
                'rss': sRSS,
                'lst': lst
            });
        }
    });
    NS.OnlineWidget = OnlineWidget;


    Brick.mod.bos.onlineManager.register('{C#MODNAME}', OnlineWidget);
};