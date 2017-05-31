import React from 'react';
import PropTypes from 'prop-types';
import { createPage } from 'Page';
import Loader from 'Loader';

class Root extends React.Component {
    constructor() {
        super(...arguments)
        
        this.state = {
            isLoaded: false
        };
        
        this._loader = new Loader(this.props.name, true)
        this._loader.init.then(conf => {
            this.setState({isLoaded: true });
        })
    }
    
    render() {
        if (!this.state.isLoaded) return null;
        return createPage(this.props.name, this._loader);
    }
}

Root.propTypes = {
    name: PropTypes.string.isRequired
}

export default Root;
