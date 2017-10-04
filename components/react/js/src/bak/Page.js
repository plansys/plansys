import React from 'react';
import PropTypes from 'prop-types';
import h from 'react-hyperscript';

class Page extends React.Component {
     
     componentWillMount() {
          this.props.bindLoader(this);
     }
     
     render() {
          if (!this.loader || !this.loader.isConfLoaded()) {
               return h('div', 'loading');
          } 
          
          return ::this.loader.render();
     }
}

Page.propTypes = {
     name: PropTypes.string.isRequired,
     bindLoader: PropTypes.func.isRequired
}

export default Page;