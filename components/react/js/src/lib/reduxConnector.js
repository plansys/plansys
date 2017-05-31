import Root from './../Root';

export const mapInput = function(input) {
     var list = input();
     return function(store, props) {
          var result = {};
          for (var p in list) {
               switch (typeof list[p]) {
                    case "string":
                         result[p] = function(store, props) {
                              return eval(list[p]);
                         }(store, props)
                         break;
                    case "function":
                         result[p] = function(store, props) {
                              return list[p];
                         }(store, props)
               }
          }
          
          return result;
     }
}


export const mapAction = function(action) {
     var list = action();
     var result = {};
     for (var p in list) {
          switch (typeof list[p]) {
               case "string":
                    result[p] = function(actions,result) {
                         return function() {
                              return eval(list[p]).bind(this.page)(...arguments)
                         }.bind(result);
                    }.bind(this)(Root.actionCreators,result);
                    break;
               case "function":
                    result[p] = function(actions, result) {
                         return list[p].bind(this.page);
                    }.bind(this)(Root.actionCreators,result);
                    break;
          }
     }
     
     return result;
}
