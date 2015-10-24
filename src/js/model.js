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

    NS.Topic = Y.Base.create('topic', SYS.AppModel, [], {
        structureName: 'Topic',
        isRemoved: function(){
            return this.get('status').get('status') === NS.TopicStatus.REMOVED;
        },
        isClosed: function(){
            return this.get('status').get('status') === NS.TopicStatus.CLOSED;
        },
        getTitle: function(){
            var title = this.get('title');
            return title === '' ? LNG.get('model.topic.emptyTitle') : title;
        },
        getUserIds: function(arr){
            var ret = [],
                uid = this.get('userid'),
                cUid,
                cStat = this.get('commentStatistic'),
                status = this.get('status');

            ret[ret.length] = uid;

            cUid = status.get('userid');
            if (cUid !== uid){
                ret[ret.length] = cUid;
            }

            if (cStat){
                cUid = cStat.get('lastUserid');

                if (cUid != uid){
                    ret[ret.length] = cUid;
                }
            }

            if (arr){
                var find, cfind;
                for (var i = 0; i < arr.length; i++){
                    if (arr[i] === uid){
                        find = true;
                    }
                    if (cUid > 0 && arr[i] === cUid){
                        cfind = true;
                    }
                }
                if (!find){
                    arr[arr.length] = uid;
                }
                if (cUid > 0 && !cfind){
                    arr[arr.length] = cUid;
                }
            }

            return ret;
        },
        fillUsers: function(userList){
            var cStat = this.get('commentStatistic'),
                status = this.get('status'),
                user = userList.getById(this.get('userid'));

            if (user){
                this.set('user', user);
            }
            user = userList.getById(status.get('userid'));
            if (user){
                status.set('user', user);
            }

            if (cStat){
                user = userList.getById(cStat.get('lastUserid'));
                if (user){
                    cStat.set('lastUser', user);
                }
            }
        }
    }, {
        ATTRS: {
            user: {}
        }
    });

    NS.TopicList = Y.Base.create('topicList', SYS.AppModelList, [], {
        appItem: NS.Topic,
        comparator: function(topic){
            return topic.get('upddate');
        },
        getUserIds: function(){
            var ret = [];
            this.each(function(topic){
                topic.getUserIds(ret);
            }, this);
            return ret;
        },
        fillUsers: function(userList){
            this.each(function(topic){
                topic.fillUsers(userList);
            }, this);
        }
    }, {
        ATTRS: {
            page: {value: 1}
        }
    });

    NS.TopicStatus = Y.Base.create('topicStatus', SYS.AppModel, [], {
        structureName: 'TopicStatus'
    }, {
        OPENED: 'opened',
        CLOSED: 'closed',
        REMOVED: 'removed',
        ATTRS: {
            user: {}
        }
    });

    NS.TopicStatusList = Y.Base.create('topicStatusList', SYS.AppModelList, [], {
        appItem: NS.TopicStatus
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