import React from 'react';
import PropTypes from 'prop-types';
import h from 'react-hyperscript';
import { Link as RLink } from 'react-router-dom';
import Loader from 'Loader';

export default function Link(props, context) {
    
    const location = {
        search: '?r=page/' + props.to
    }
    
    if (!context.router) {
        return <div><b>&lt;Link&gt; must be placed inside &lt;Placeholder&gt;</b></div>
    }
    
    return (
       <RLink { ...props } to={ location }>
       </RLink>
    );
}


Link.contextTypes = {router: PropTypes.object};