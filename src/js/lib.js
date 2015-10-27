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
            TopicStatus: {value: NS.TopicStatus},
            TopicStatusList: {value: NS.TopicStatusList},
            File: {value: NS.File},
            FileList: {value: NS.FileList},
            Config: {value: NS.Config}
        },
        REQS: {
            topic: {
                args: ['topicid'],
                attribute: false,
                type: 'model:Topic',
                onResponse: function(topic){
                    var userIds = topic.getUserIds();
                    if (userIds.length === 0){
                        return;
                    }
                    return function(callback, context){
                        this.getApp('uprofile').userListByIds(userIds, function(err, result){
                            topic.fillUsers(result.userListByIds);
                            callback.call(context || null);
                        }, context);
                    };
                }
            },
            topicSave: {args: ['topic']},
            topicClose: {args: ['topicid']},
            topicRemove: {args: ['topicid']},
            topicOpen: {args: ['topicid']},
            topicList: {
                args: ['page'],
                attribute: false,
                type: 'modelList:TopicList',
                onResponse: function(topicList){
                    var userIds = topicList.getUserIds();
                    if (userIds.length === 0){
                        return;
                    }
                    return function(callback, context){
                        this.getApp('uprofile').userListByIds(userIds, function(err, result){
                            topicList.fillUsers(result.userListByIds);
                            callback.call(context || null);
                        }, context);
                    };
                }
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
                    return this.getURL('ws') + 'topicList/TopicListWidget/';
                },
                create: function(){
                    return this.getURL('ws') + 'topicEditor/TopicEditorWidget/';
                },
                edit: function(id){
                    return this.getURL('ws') + 'topicEditor/TopicEditorWidget/' + id + '/';
                },
                view: function(id){
                    return this.getURL('ws') + 'topicView/TopicViewWidget/' + id + '/';
                }
            }
        }
    });

};