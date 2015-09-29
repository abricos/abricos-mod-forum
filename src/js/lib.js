var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['application.js', 'form.js', 'item.js', 'date.js']},
        {name: 'widget', files: ['lib.js']},
        {name: 'uprofile', files: ['users.js']},
        {name: 'filemanager', files: ['lib.js']},
        {name: '{C#MODNAME}', files: ['model.js']}
    ]
};
Component.entryPoint = function(NS){

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isModer: 40,
        isWrite: 30,
        isView: 10
    });

    var Y = Brick.YUI,

        COMPONENT = this,

        SYS = Brick.mod.sys;

    NS.URL = {
        ws: "#app={C#MODNAMEURI}/wspace/ws/",
        topic: {
            list: function(){
                return NS.URL.ws + 'topiclist/TopicListWidget/';
            },
            create: function(){
                return NS.URL.ws + 'topiceditor/TopicEditorWidget/';
            },
            edit: function(id){
                return NS.URL.ws + 'topiceditor/TopicEditorWidget/' + id + '/';
            },
            view: function(id){
                return NS.URL.ws + 'topicview/TopicViewWidget/' + id + '/';
            }
        }
    };

    SYS.Application.build(COMPONENT, {
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
            response: function(d){
                return new NS.TopicList(d.list);
            }
        }
    }, {
        initializer: function(){
            this.forums = new NS.ForumList();
            this.users = Brick.mod.uprofile.viewer.users;

            var instance = this;
            NS.roles.load(function(){
                Brick.mod.filemanager.roles.load(function(){
                    instance.initCallbackFire();
                });
            });
        },
        ajaxParseResponse: function(data, ret){
            if (data.userList){
                this.users.update(data.userList.list);
            }
        }
    });

};