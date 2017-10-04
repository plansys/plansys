export const addJS = function(href, id, onload) {
     (function(d, s, id) {
          var js, fjs = d.getElementsByTagName(s)[0];
          if (d.getElementById(id)) {
               onload();
               return;
          }
          js = d.createElement(s);
          js.id = id;
          js.onload = function() {
               // remote script has loaded
               if (typeof onload == 'function') {
                    onload();
               }
          };
          js.src = href;
          fjs.parentNode.insertBefore(js, fjs);
     }(document, 'script', id));
}

export const addCSS = function(href) {
     var head = document.head,
          link = document.createElement('link')

     link.type = 'text/css'
     link.rel = 'stylesheet'
     link.href = href

     head.appendChild(link)
}
