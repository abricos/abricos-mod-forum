var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    var LNG = this.language,
        UID = Brick.env.user.id | 0;

    NS.Topic = Y.Base.create('topic', SYS.AppModel, [], {
        structureName: 'Topic',
        isOpened: function(){
            return this.get('status').get('status') === NS.TopicStatus.OPENED;
        },
        isClosed: function(){
            return this.get('status').get('status') === NS.TopicStatus.CLOSED;
        },
        isRemoved: function(){
            return this.get('status').get('status') === NS.TopicStatus.REMOVED;
        },
        isWriteRole: function(){
            if ((NS.roles.isModer || this.get('userid') === UID) && this.isOpened()){
                return true;
            }
            return false;
        },
        isCloseRole: function(){
            return this.isWriteRole();
        },
        isRemoveRole: function(){
            return NS.roles.isModer || this.isWriteRole();
        },
        isOpenRole: function(){
            return this.isClosed() && NS.roles.isModer;
        },
        isCommentWriteRole: function(){
            return NS.roles.isWrite && this.isOpened();
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
            user: {},
            commentOwner: {
                readOnly: true,
                getter: function(){
                    if (this._commentOwner){
                        return this._commentOwner;
                    }
                    this._commentOwner = this.appInstance.getApp('comment').ownerCreate(
                        'forum', 'topic', this.get('id')
                    );
                    return this._commentOwner;
                }
            }
        }
    });

    NS.TopicList = Y.Base.create('topicList', SYS.AppModelList, [], {
        appItem: NS.Topic,
        comparator: function(topic){
            return topic.get('upddate') * (-1);
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
            user: {},
            title: {
                readOnly: true,
                getter: function(){
                    var status = this.get('status');
                    return LNG.get('model.topic.status.' + status);
                }
            }
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

    NS.SUBSCRIBE = {
        MODULE: 'forum',

        TOPIC: 'forum:topic',
        TOPIC_NEW: 'forum:topic:new',
        TOPIC_COMMENT: 'forum:topic:comment',
        TOPIC_CHANGE: 'forum:topic:change',

        TOPIC_ITEM: 'forum:topic::{v#item}',
        TOPIC_COMMENT_ITEM: 'forum:topic:comment:{v#itemid}'
    };

};