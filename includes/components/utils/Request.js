/**
 * Utilities
 */
NodeList.prototype.forEach = Array.prototype.forEach;

String.prototype.html_entity_decode = function()
{
    var d = M4.createElement("div", {htmlText:this.toString()});
    return d.firstChild.nodeValue;
};

Function.prototype.proxy = function(pInstance)
{
    var ref = this;
    return function(){ref.apply(pInstance, arguments);};
};

Object.clone = function(pData)
{
    var obj = {};
    for(var i in pData)
    {
        if(!pData.hasOwnProperty(i))
            continue;
        obj[i] = pData[i];
    }
    return obj;
};


/**
 * Base Class
 * Overriding - toString - whatever
 */
function Class(){}

Class.prototype = {
    super:function(pMethodName)
    {
        pMethodName = pMethodName||"constructor";
        if(!this.__SUPER__||!this.__SUPER__[pMethodName])
            throw new Error("Method '"+pMethodName+"' undefined");
        var args = [];
        for(var i = 1, max = arguments.length;i<max;i++)
            args.push(arguments[i]);
        var func;
        if(this[pMethodName]&&this[pMethodName]==this.__SUPER__[pMethodName])
            func = this.__SUPER__.__SUPER__[pMethodName].proxy(this);
        else
            func = this.__SUPER__[pMethodName].proxy(this);
        return func.apply(this, args);
    },
    toString : function()
    {
        return this.formatToString();
    },
    formatToString : function()
    {
        var t = /^function ([a-z][a-z0-9_]*)\(/i.exec(this.constructor.toString());
        var s = "[Object "+t[1];
        for(var i=0, max = arguments.length;i<max;i++)
            s+= " "+arguments[i]+"=\""+this[arguments[i]]+"\"";
        return s+"]";
    }
};

Class.extend = function(pTarget, pClassParent)
{
    for(var i in pClassParent.prototype)
    {
        pTarget.prototype[i] = pClassParent.prototype[i];
    }
    pTarget.prototype.__SUPER__ = pClassParent.prototype;
};
Class.define = function(pTarget, pExtends, pPrototype)
{
    if(pExtends.length>0)
    {
        for(var i = 0, max=pExtends.length; i<max; i++)
            Class.extend(pTarget, pExtends[i]);
    }
    for(var k in pPrototype)
        pTarget.prototype[k] = pPrototype[k];
};
function Event(pType, pBubbles)
{
    this.type = pType;
    this.bubbles = pBubbles||false;
    this.eventPhase = Event.AT_TARGET;
}

Class.define(Event, [Class], {
    target:null,
    currentTarget:null,
    eventPhase:null,
    type:null,
    bubbles:false,
    clone:function(){var e = new Event(this.type, this.bubbles);e.target = this.target;return e;},
    toString:function(){return this.formatToString("type", "eventPhase", "target", "currentTarget", "bubbles");}
});

Event.CAPTURING_PHASE = 1;
Event.AT_TARGET = 2;
Event.BUBBLING_PHASE = 3;

Event.INIT = "init";
Event.COMPLETE = "complete";


function MouseEvent(pType, pBubbles, pMouseX, pMouseY, pButton)
{
    this.type = pType;
    this.localX = pMouseX||0;
    this.localY = pMouseY||0;
    this.button = pButton||0;
    this.super("constructor", pType, pBubbles);
}
Class.define(MouseEvent, [Event], {
    localX:0,
    localY:0,
    button:0
});
MouseEvent.MOUSE_OVER = "mouse_over";
MouseEvent.MOUSE_OUT = "mouse_out";
MouseEvent.MOUSE_DOWN = "mouse_down";
MouseEvent.MOUSE_UP = "mouse_up";
MouseEvent.CLICK = "click";
MouseEvent.LEFT_BUTTON = 0;
MouseEvent.RIGHT_BUTTON = 2;
function EventDispatcher() {
    this.removeAllEventListener();
}

Class.define(EventDispatcher, [Class], {
    __listeners: {},
    __listenersCapture: {},
    addEventListener: function (pType, pHandler, pCapture) {
        if (typeof(pCapture) != "boolean")
            pCapture = false;
        if (pCapture) {
            if (!this.__listenersCapture[pType])
                this.__listenersCapture[pType] = [];
            this.__listenersCapture[pType].push(pHandler);
        }
        else {
            if (!this.__listeners[pType])
                this.__listeners[pType] = [];
            this.__listeners[pType].push(pHandler);
        }
    },
    removeEventListener: function (pType, pHandler, pCapture) {
        if (typeof(pCapture) != "boolean")
            pCapture = false;
        var t = (pCapture ? this.__listenersCapture : this.__listeners)[pType];
        if (typeof(t) == "undefined" || !t.length)
            return;
        var handlers = [];
        for (var i = 0, max = t.length; i < max; i++) {
            if (t[i] === pHandler)
                continue;
            handlers.push(t[i]);
        }
        if (pCapture)
            this.__listenersCapture[pType] = handlers;
        else
            this.__listeners[pType] = handlers;
    },
    removeAllEventListener: function (pType) {
        pType = pType || false;
        if (pType === false) {
            this.__listeners = {};
            this.__listenersCapture = {};
            return;
        }
        this.__listeners[pType] = [];
        this.__listenersCapture[pType] = [];
    },
    dispatchEvent: function (pEvent) {
        if (!pEvent.target)
            pEvent.target = this;
        pEvent.currentTarget = this;
        var a = [], p = this.parent, i, max, e;
        switch (pEvent.eventPhase) {
            case Event.CAPTURING_PHASE:
                if (typeof(this.__listenersCapture[pEvent.type]) == "undefined")
                    return;
                for (i = 0, max = this.__listenersCapture[pEvent.type].length; i < max; i++)
                    this.__listenersCapture[pEvent.type][i](pEvent);
                break;
            case Event.AT_TARGET:
                while (p) {
                    a.push(p);
                    p = p.parent;
                }
                e = Object.clone(pEvent);
                e.eventPhase = Event.CAPTURING_PHASE;
                for (i = a.length - 1; i >= 0; i--)
                    a[i].dispatchEvent(e);
                if (typeof(this.__listeners[pEvent.type]) == "object" && this.__listeners[pEvent.type].length > 0) {
                    for (i = 0, max = this.__listeners[pEvent.type].length; i < max; i++) {
                        if (this.__listeners[pEvent.type] && this.__listeners[pEvent.type][i])
                            this.__listeners[pEvent.type][i](pEvent);
                    }
                }
                if (pEvent.bubbles) {
                    e = Object.clone(pEvent);
                    e.eventPhase = Event.BUBBLING_PHASE;
                    for (i = 0, max = a.length; i < max; i++)
                        a[i].dispatchEvent(e);
                }
                break;
            case Event.BUBBLING_PHASE:
                if (typeof(this.__listeners[pEvent.type]) == "undefined")
                    return;
                for (i = 0, max = this.__listeners[pEvent.type].length; i < max; i++)
                    this.__listeners[pEvent.type][i](pEvent);
                break;
        }
    }
});
function Request(pTarget, pParams, pMethod)
{
    this.removeAllEventListener();
    pMethod = (pMethod||"get").toUpperCase();
    this.xhr_object = null;
    if (window.XMLHttpRequest)
        this.xhr_object = new XMLHttpRequest();
    else if (window.ActiveXObject)
    {
        var t = ['Msxml2.XMLHTTP','Microsoft.XMLHTTP'],i = 0;
        while(!this.xhr_object&&t[i++])
            try {this.xhr_object = new ActiveXObject(t[i]);}catch(e){}
    }
    if(!this.xhr_object)
        return;
    var ref = this, v = "", j = 0;
    for(i in pParams)
        v += (j++>0?"&":"")+i+"="+pParams[i];
    this.xhr_object.open(pMethod, pTarget, true);
    this.xhr_object.onprogress = this.dispatchEvent.proxy(this);
    this.xhr_object.onreadystatechange=function()
    {
        if(ref.xhr_object.readyState==4)
        {
            var ct = ref.xhr_object.getResponseHeader("Content-type");
            if(ct.indexOf("json")>-1)
                ref.xhr_object.responseJSON = JSON.parse(ref.xhr_object.responseText);
            switch(ref.xhr_object.status)
            {
                case 200:
                case 201:
                case 304:
                    ref.dispatchEvent(new RequestEvent(Event.COMPLETE, ref.xhr_object.responseText, ref.xhr_object.responseJSON));
                    break;
                case 400:
                case 401:
                case 402:
                case 403:
                case 404:
                case 405:
                case 500:
                case 503:
                    ref.dispatchEvent(new RequestEvent(RequestEvent.ERROR, ref.xhr_object.responseText, ref.xhr_object.responseJSON));
                    break;
            }
        }
    };
    this.xhr_object.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;charset:'+Request.CHARSET);
    this.xhr_object.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    try
    {
        this.xhr_object.send(v);
    }
    catch(e)
    {
        console.log(e);
    }
}
Class.define(Request, [EventDispatcher],
    {
        onComplete:function(pFunction)
        {
            this.addEventListener(Event.COMPLETE, pFunction, false);
            return this;
        },
        onProgress:function(pFunction)
        {
            this.addEventListener(RequestEvent.PROGRESS, pFunction, false);
            return this;
        },
        onError:function(pFunction)
        {
            this.addEventListener(RequestEvent.ERROR, pFunction, false);
            return this;
        },
        cancel:function()
        {
            this.dispatchEvent(new RequestEvent(RequestEvent.CANCEL));
            this.xhr_object.abort();
        }
    });
Request.CHARSET = "UTF-8";
Request.load = function (pUrl, pParams, pMethod){return new Request(pUrl, pParams, pMethod);};
Request.update = function(pId, pUrl, pParams){return Request.load(pUrl, pParams).onComplete(function(pResponse){document.getElementById(pId).innerHTML = pResponse.responseText;});};

function RequestEvent(pType, pResponseText, pResponseJSON, pBubble)
{
    this.super("constructor", pType, pBubble);
    this.responseText = pResponseText||"";
    this.responseJSON = pResponseJSON||{};
}

Class.define(RequestEvent, [Event], {});
RequestEvent.ERROR = "error";
RequestEvent.CANCEL = "cancel";
RequestEvent.PROGRESS = "progress";