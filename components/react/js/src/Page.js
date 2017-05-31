import React from 'react';
import PropTypes from 'prop-types';
import Loader from 'Loader';
import h from 'react-hyperscript';
import { Provider } from 'react-redux';

export class Page extends React.Component {
    constructor() {
        super(...arguments)
        this.state = {
            isLoaded: false
        }
    }
    
    hswap(tag, props, children) {
        switch (tag) {
            case "Page":
                return createPage(props.name, this._loaders[this._loadersIdx++]);
            case "Placeholder":
                return h(Loader.ui.loaded[tag], {
                    history: this.props.loader.history
                });
            default:
                var stag = tag;
                
                if (Loader.ui.loaded[tag]) {
                    stag = Loader.ui.loaded[tag];
                }
                
                return h(stag, props, children);
        }
    }
    
    componentDidMount() {
        this._isMounted = true;
        this.loadSubPage();
    }
    
    componentWillUnmount() {
        this._isMounted = false;
    }
    
    loadSubPage() {
        this.setState({
          isLoaded: false,
        });
        
        this._loaders = [];
        
        if (this.props.loader.conf.loaders) {
            this.props.loader.conf.loaders.map((pageName) => {
                this._loaders.push(new Loader(pageName));
            });
        }
        
        Promise
            .all(this._loaders.map(l => l.init))
            .then(results => {
                if (!this._isMounted) return null;
                
                this._loadersIdx = 0;
                this.setState({isLoaded: true});
            })
    }
    
    render() {
        if (!this.state.isLoaded) return null;
        return this.props.loader.conf.render.bind(this)(::this.hswap)
    }
}

Page.propTypes = {
    loader: PropTypes.object.isRequired
}

export const createPage = function(name, loader) {    
    if (loader.isRoot) {
        return ( 
            <Provider store={ loader.store }>
                <loader.pageComponent loader={loader} />
            </Provider> 
        )
    }
    return <loader.pageComponent loader={loader} />
}
