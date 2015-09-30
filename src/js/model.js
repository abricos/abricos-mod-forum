var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    var LNG = this.language;

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
        },
        getTitle: function(){
            var title = this.get('title');
            return title === '' ? LNG.get('model.topic.emptyTitle') : title;
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