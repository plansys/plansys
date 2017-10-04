import React from 'react';
import h from 'react-hyperscript';

// loader
import Root from './Root';
import { Page } from './Page';
import { addCSS } from './lib/injectTag';
import { componentLoader, loadConf, parseConf } from './lib/componentLoader';
import { mapInput, mapAction } from './lib/reduxConnector';

// redux
import initStore from './lib/initStore';
import { connect } from 'react-redux';
import { importReducers } from './lib/reduxImport';
import Immutable from 'immutable';
import createSagaMiddleware from 'redux-saga';

// router
import createHistory from 'history/createBrowserHistory'
import { Route } from 'react-router'
import { ConnectedRouter, routerReducer, routerMiddleware, push } from 'react-router-redux'

const UIELEMENTS = window.UIELEMENTS; // ui constant generated by webpack
const UIALIAS = window.UIALIAS; // ui constant generated by webpack

class Loader {
    static baseUrl;
    static basePath;
    static page = {
        title: '',
        conf: {},
        confpromise: {},
        css: [],
    };
    static loaders = [];
    static ui = {
        elements: UIELEMENTS,
        alias: UIALIAS,
        promise: {},
        loaded: { Page },
    };
    
    constructor(name, isRoot = false) {
        if (typeof name != 'string') {
            throw new Error('alias must be a string');
        }
        
        this.isRoot = isRoot;
        this.name = name;
        this.conf = null;
        this.page = null;
        this.history = null;
        this.subpage = [];
        this.subpageidx = 0;
        this.pageComponent = Page;
        
        this.init = this
            .initConf(name, root)
            .then(::this.loadDependecies)
            .then(::this.prepareRedux)
            .then(::this.bindRenderer)
            
    }
    
    initConf(name, root) {
        return new Promise(resolve => {
            if (!Loader.page.conf[name]) {
                loadConf(name, this.isRoot).then(rawconf => {
                    var conf = parseConf(rawconf, name);
                    
                    Loader.page.conf[conf.alias] = conf;
                    if (conf.alias != name && conf.dependencies.pages[name]) {
                        Loader.page.conf[name] = conf.dependencies.pages[name];
                    }
                    this.name = conf.alias;
                    
                    addCSS(Loader.baseUrl + '/index.php?r=/page/css:' + conf.alias);
                    
                    resolve(conf);
                })
                
            } else {
                resolve(Loader.page.conf[name]);
            }
        })
    }
    
    loadDependecies(conf) {
        return new Promise(resolve => {
            if (true) {
                
                for (var page in conf.dependencies.pages)  {
                    Loader.page.conf[page] = conf.dependencies.pages[page];
                }
                
                conf.dependencies.elements.map(el => {
                    var is = false;
                    if (Loader.ui.elements.indexOf(el)) {
                        is = Loader.getElement(el);
                    }
                    else if (Loader.ui.alias[el]) {
                        is = Loader.getElement(el);
                    }
            
                    if (!is && el.indexOf('.') > 0) {
                        var etag = el.split(".");
                        var ttag = etag[0];
                        for (var i in etag) {
                            if (i > 0) {
                                ttag = ttag + "." + etag[i];
                            }
                            is =  Loader.getElement(ttag);
                            if (is) {
                                break;
                            }
                        }
                    }
                    if (is) {
                        Loader.ui.promise[el] = (tag) => componentLoader(is.alias);
                    }
                })
                
                const tags = Object.keys(Loader.ui.promise);
                if (tags.length > 0) {
                    Promise
                        .all(tags.map(tag => Loader.ui.promise[tag](tag))) // import all ui dependencies
                        .then((result) => {
                            Loader.ui.promise = {};
                            
                            // mark all page conf as loaded
                            // move all ui element it to Loader.ui.loaded
                            tags.map((tag, idx) => {
                                Loader.ui.loaded[tag] = result[idx];
                                delete Loader.ui.promise[tag];
                    
                                if (tag.indexOf('.') > 0) {
                                    const ftag = Loader.ui.elements.filter(t => tag.indexOf(t) === 0);
                                    if (ftag.length > 0) {
                                        const ttag = tag.substr(ftag[0].length + 1);
                                        const subcomp = eval('result[idx].' + ttag)
                                        if (subcomp) {
                                            Loader.ui.loaded[tag] = subcomp;
                                        }
                                    }
                                    else {
                                        const etag = tag.split('.');
                                        if (Loader.ui.alias[etag[0]]) {
                                            etag.shift();
                                            const ttag = etag.join('.');
                                            const subcomp = eval('result[idx].' + ttag)
                                            if (subcomp) {
                                                Loader.ui.loaded[tag] = subcomp;
                                            }
                                        }
                                    }
                                }
                            })
                    
                            resolve(conf);
                        });
                
                    return;
                }
            } 
            
            resolve(conf);
            
        })
    }
    
    prepareRedux(conf) {
        return new Promise(resolve => {
            
            // init store
            if (this.isRoot != false && conf.redux) {
                if (typeof conf.redux.actions != 'object') {
                    this.actions = conf.redux.actions;
                }
                if (typeof conf.redux.actionCreators == 'function') {
                    this.actionCreators = conf.redux.actionCreators();
                }
                
                this.reducers = function(){};
                if (typeof conf.redux.reducers == 'function') {
                    this.reducers = importReducers(conf.redux.reducers(Immutable), {
                        route: routerReducer
                    });
                }
                
                this.history = createHistory()
                this.store = initStore(this.reducers, [
                    routerMiddleware(this.history)
                ]);
            }
            
            // prepare react-redux connect args (mapStateToProps and mapDispatchToProps)
            if (conf.map) {
                if (conf.map.input) {
                    this.mapStateToProps = mapInput.bind(this)(conf.map.input)
                }
                
                if (conf.map.action) {
                    this.mapDispatchToProps = mapAction.bind(this)(conf.map.action);
                }
            }
            resolve(conf);
        })
    }
    
    bindRenderer(conf) {
        return new Promise(resolve => {
            if (this.mapStateToProps || this.mapDispatchToProps) {
                this.pageComponent = connect(this.mapStateToProps, this.mapDispatchToProps)(this.pageComponent);
            }
            
            this.conf = conf;
            resolve(conf);
        })
    }
    
    static getElement(tag) {
        if (Loader.ui.elements.indexOf(tag) >= 0) {
            if (!Loader.ui.loaded[tag]) {
                if (!Loader.ui.promise[tag]) {
                    return {
                        tag,
                        alias: tag
                    };
                }
            }
        }
        else if (Loader.ui.alias[tag]) {
            if (!Loader.ui.loaded[Loader.ui.alias[tag]]) {
                if (!Loader.ui.promise[Loader.ui.alias[tag]]) {
                    return {
                        tag,
                        alias: Loader.ui.alias[tag]
                    };
                }
            }
        }
        return false;
    }
    
}

export default Loader