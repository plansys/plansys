import React from 'react';
import PropTypes from 'prop-types';
import h from 'react-hyperscript';
import { Route } from 'react-router'; 
import { ConnectedRouter } from 'react-router-redux';
import { loadConf, parseConf } from 'lib/componentLoader';
import { createPage } from 'Page';
import Loader from 'Loader';


class Placeholder extends React.Component {
    constructor() {
        super(...arguments)
        
        this.state = {
            currentPage: null
        };
        
    }
    
    render() {
        return (
            <ConnectedRouter history={ this.props.history }>
                <Route render={ (route) => {
                        const params = new window.URLSearchParams(route.location.search);
                        const pageName = params.get('r').split('page/').pop().split(":").pop();
                        
                        if (!this._loader) {
                            this._loader = {};
                        }
                        
                        if (!this._loader[pageName]) {
                            this._loader[pageName] = new Loader(pageName)
                            this._loader[pageName].init.then(conf => {
                                this.setState({currentPage: pageName });
                            })
                            return null;
                        }
                        
                        if (this._loader[pageName] && this._loader[pageName].conf) {
                            return createPage(pageName, this._loader[pageName])
                        } else {
                            return null
                        }
                    }
                } />
            </ConnectedRouter>
        )
    }
}

Placeholder.propTypes = {
    history: PropTypes.object.isRequired
}

export default Placeholder;