var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['application.js', 'form.js', 'item.js', 'date.js']},
        {name: 'widget', files: ['lib.js']},
        {name: 'filemanager', files: ['lib.js']},
        {name: '{C#MODNAME}', files: ['model.js']}
    ]
};
Component.entryPoint = function(NS){

    var COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isModer: 40,
        isWrite: 30,
        isView: 10
    });

    SYS.Application.build(COMPONENT, {}, {
        initializer: function(){
            var instance = this;
            this.appStructure(function(){
                NS.roles.load(function(){
                    Brick.mod.filemanager.roles.load(function(){
                        instance.initCallbackFire();
                    });
                });
            }, this);
        }
    }, [], {
        APPS: {
            uprofile: {},
            comment: {}
        },
        ATTRS: {
            Topic: {value: NS.Topic},
            TopicList: {value: NS.TopicList},
            File: {value: NS.File},
            FileList: {value: NS.FileList},
            Config: {value: NS.Config}
        },
        REQS: {
            topic: {
                args: ['topicid'],
                response: function(d){
                    return new NS.Topic(d);
                }
            },
            topicSave: {args: ['topic']},
            topicClose: {args: ['topicid']},
            topicRemove: {args: ['topicid']},
            topicList: {
                args: ['page'],
                attribute: false,
                type: 'modelList:TopicList',
            },
            config: {
                attribute: true,
                type: 'model:Config'
            }
        },
        URLS: {
            ws: "#app={C#MODNAMEURI}/wspace/ws/",
            topic: {
                list: function(){
                    return this.getURL('ws') + 'topiclist/TopicListWidget/';
                },
                create: function(){
                    return this.getURL('ws') + 'topiceditor/TopicEditorWidget/';
                },
                edit: function(id){
                    return this.getURL('ws') + 'topiceditor/TopicEditorWidget/' + id + '/';
                },
                view: function(id){
                    return this.getURL('ws') + 'topicview/TopicViewWidget/' + id + '/';
                }
            }
        }
    });

};