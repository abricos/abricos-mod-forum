var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    NS.TopicStatus = {
        'OPENED': 0,    // открыта
        'CLOSED': 1,    // закрыта
        'REMOVED': 2    // удалена
    };

    NS.Topic = Y.Base.create('topic', SYS.AppModel, [], {
        structureName: 'Topic',
        isRemoved: function(){
            return this.get('status') === NS.TopicStatus.REMOVED;
        },
        isClosed: function(){
            return this.get('status') === NS.TopicStatus.CLOSED;
        }
    });

    NS.TopicList = Y.Base.create('topicList', SYS.AppModelList, [], {
        appItem: NS.Topic,
        comparator: function(topic){
            return topic.get('upddate');
        }
    }, {
        ATTRS: {
            page: {value: 1}
        }
    });

    NS.File = Y.Base.create('file', SYS.AppModel, [], {
        structureName: 'File'
    });

    NS.FileList = Y.Base.create('fileList', SYS.AppModelList, [], {
        appItem: NS.File
    });

    NS.Config = Y.Base.create('config', SYS.AppModel, [], {
        structureName: 'Config'
    });

};