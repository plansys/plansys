import {
     applyMiddleware,
     compose,
     createStore
}
from 'redux';
import createSagaMiddleware from 'redux-saga';

export default function initStore(reducers, middlewares) {
     let middleware = applyMiddleware(...middlewares);

     if (process.env.NODE_ENV !== 'production') {
          const devToolsExtension = window.devToolsExtension;
          if (typeof devToolsExtension === 'function') {
               middleware = compose(middleware, devToolsExtension());
          }
     }

     const store = createStore(reducers, middleware);
     
     return store;
}