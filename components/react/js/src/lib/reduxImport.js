import Immutable from 'immutable'
import { combineReducers } from 'redux';
import _ from 'lodash';

export const importReducers = function(rawReducers, additionalReducers) {
    var reducers = {};
    
    _.map(rawReducers, (r, i) => {
        switch (typeof r) {
            case "object":
                if (r.type === Immutable.Record) {
                    reducers[i] = function(state, { payload, type }) {
                        if (typeof state == 'undefined') {
                            var record = new Immutable.Record(r.default);
                            state = new record();
                        }
                        for (var x in r.reducers) {
                            var item = r.reducers[x];
                            
                            if (item.type == type) {
                                return item.reducer(state, payload);
                            }
                        }
                        return state;
                    }
                }
                break;
        }
    })
    
    if (typeof additionalReducers != "undefined") {
        for (var i in additionalReducers) {
            reducers[i] = additionalReducers[i];
        }
    }
    
    
    
    return combineReducers(reducers);
}