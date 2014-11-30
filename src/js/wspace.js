/*!
 * Copyright 2014 Alexander Kuzmin <roosit@abricos.org>
 * Licensed under the MIT license
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Dom = YAHOO.util.Dom,
        L = YAHOO.lang;

    var buildTemplate = this.buildTemplate;

    var GMID = {    };
    GMIDI = {};
    var DEFPAGE = {
        'component': 'topiclist',
        'wname': 'TopicListWidget',
        'p1': '', 'p2': '', 'p3': '', 'p4': ''
    };

    var WSWidget = function(container, pgInfo){
        WSWidget.superclass.constructor.call(this, container, {
            'buildTemplate': buildTemplate, 'tnames': 'widget'
        }, pgInfo || []);
    };
    YAHOO.extend(WSWidget, Brick.mod.widget.Widget, {
        init: function(pgInfo){
            this.pgInfo = pgInfo;
            this.widget = null;
        },
        buildTData: function(pgInfo){
            return {
               // 'urluserlist': NS.URL.user.list(),
                // 'urlgrouplist': NS.URL.group.list()
            };
        },
        onLoad: function(pgInfo){
            this.showPage(pgInfo);
        },
        showPage: function(p){
            p = L.merge(DEFPAGE, p || {});

            this.elHide('board');
            this.elShow('loading');

            var __self = this;
            Brick.use('{C#MODNAME}', p['component'], function(){
                __self._showPageMethod(p);
            });
        },
        _showPageMethod: function(p){

            var wName = p['wname'];
            if (!NS[wName]){
                return;
            }

            if (!L.isNull(this.widget)){
                this.widget.destroy();
                this.widget = null;
            }

            this.elShow('board');
            this.elHide('loading');

            this.elSetHTML('board', "");

            var elBoard = Y.one('#' + this.gel('board').id),
                elDiv = Y.Node.create('<div></div>');

            elBoard.appendChild(elDiv);

            var args = {};
            if (Y.Lang.isFunction(NS[wName].parseURLParam)){
                args = NS[wName].parseURLParam(p);
            }

            this.widget = new NS[wName](
                elDiv
                // Y.mix({'boundingBox': elDiv}, args)
            );

        }
    })
    ;
    NS.WSWidget = WSWidget;


    var WSPanel = function(pgInfo){
        this.pgInfo = pgInfo || [];

        WSPanel.superclass.constructor.call(this, {
            fixedcenter: true, width: '790px', height: '400px'
        });
    };
    YAHOO.extend(WSPanel, Brick.widget.Panel, {
        initTemplate: function(){
            return buildTemplate(this, 'panel').replace('panel');
        },
        onLoad: function(){
            this.widget = new NS.WSWidget(this._TM.getEl('panel.widget'), this.pgInfo);
        },
        showPage: function(p){
            this.widget.showPage(p);
        }
    });
    NS.WSPanel = WSPanel;

    var activeWSPanel = null;
    NS.API.ws = function(){
        var args = arguments;
        var pgInfo = {
            'component': args[0] || 'topiclist',
            'wname': args[1] || 'TopicListWidget',
            'p1': args[2], 'p2': args[3], 'p3': args[4], 'p4': args[5]
        };
        if (L.isNull(activeWSPanel) || activeWSPanel.isDestroy()){
            activeWSPanel = new WSPanel(pgInfo);
        } else {
            activeWSPanel.showPage(pgInfo);
        }
        return activeWSPanel;
    };

}
;