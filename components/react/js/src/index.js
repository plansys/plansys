import React from 'react';
import ReactDOM from 'react-dom';
import { AppContainer } from 'react-hot-loader';

import Root from './Root';
import Loader from './Loader';

const render = (pageName) => {
    ReactDOM.render(
        <AppContainer>
           <Root name={pageName} />
        </AppContainer>
    , document.getElementById('root'));
};

if (module.hot) {
    module.hot.accept('./Root', () => {
        render(Root.name);
    });
}

window.Root = Root;
window.Loader = Loader;
window.render = render;

// if (!PRODUCTION) {
//     window.Perf = require('react-addons-perf');
//     window.Perf.start();
// }