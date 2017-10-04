<?php

class Page {
    public $alias;
    public $path;
    public $store = '';
    public $masterpage = false;
    public $isRoot = true;
    public $showDeps = true;
    
    private $placeholder = null;
    
    public function mapInput() { return []; }
    public function mapAction() { return []; }
    public function js() {}
    public function css() {}
    public function render() {}
    
    public function renderPage($dev = true) {
        $baseUrl = Yii::app()->request->getBaseUrl(true);
        $head = [];
        $head[] = '<title>'.Setting::get('app.name').'</title>';
        
        if ($dev) {
            $body = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'template/html_dev.php');
        } else {
            $dir = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR;
            $body = file_get_contents($dir . 'index.html');
            $body = explode("\n", $body);
            $body = "\t" . implode("\n\t", $body);
        }
        
        $body .= "\n\t<script>" . $this->renderInitJs() . "\n\t</script>\n";
        
        # process body array
        $head = "\t" . implode("\n\t", $head);
        include ("template/html.php");
    }
    
    public function renderInitJS() {
        $baseUrl = Yii::app()->request->getBaseUrl(true);
        $basePath = Yii::app()->request->getBaseUrl();
        ob_start();
        include("template/initjs.php");
        return ob_get_clean();
    }
    
    public function __construct($alias, $path, $isRoot = true, $placeholder = null) {
        $this->alias = $alias;
        $this->path = $path;
        $this->isRoot = $isRoot;
        $this->placeholder = $placeholder;
    }
    
    public static function load($alias, $isRoot = true, $showDeps = true) {
        $path = Page::resolve($alias);
        $class = str_replace(".php", "", basename($path)) . "Page";
        
        if (!class_exists($class, false)) {
            include ($path);
        } 
        
        if (!class_exists($class, false)) {
            throw new Exception("Class `{$class}` not found in: " . $path);
        }
        
        $new = new $class($alias, $path, $isRoot);
        $new->showDeps = $showDeps;
        
        if ($isRoot && is_string($new->masterpage)) {
            $master = null;
            $new->isRoot = false;
            
            $masterPath = Page::resolve($new->masterpage);
            $masterClass =  str_replace(".php", "", basename($masterPath)) . "Page";
            
            if (!class_exists($masterClass, false)) {
                include ($masterPath);
            } 
            
            if (!class_exists($masterClass, false)) {
                throw new Exception("Class `{$masterClass}` not found in: " . $masterPath);
            }
            
            $master = new $masterClass($new->masterpage, $masterPath, true, $new);
            
            return $master;
        }
        return $new;
    }
    
    private function getIsPlansys() {
        return strpos($this->path, Yii::getPathOfAlias('application')) === 0;
    }
    
    public function renderConf($indent = 0, $indentAdder = 2) {
        
        ## declare vars that will be attached to conf
        $component = $this->renderComponent($indent + $indentAdder);
        $css = trim($this->css()) == "" ? "false" : "true";
        $js = $this->renderInternalJS($indent + $indentAdder - 1);
        $baseUrl = Yii::app()->request->getBaseUrl(true);
        
        $mapInput = $this->renderMapInput();
        $mapAction = $this->renderMapAction();
        
        $page = Page::load($this->alias, false, false);
        $loaders = $this->loadLoaders($page);
        if (count($loaders) > 0) {
        $loaders = $this->toJs($loaders);
        } else {
            $loaders = '[]';
        }
        
        if ($this->isRoot || $this->showDeps) {
            $dependencies = $this->loadDeps();
            $actionCreators = $this->renderReduxActions();
            $reducers = $this->renderReduxReducers();
            $sagas = "";
            
            $placeholder = '';
            if (!is_null($this->placeholder)) {
                $pdeps = $this->placeholder->loadDeps();
                foreach ($pdeps['pages'] as $k=>$p) {
                    if (!isset($dependencies['pages'][$k])) {
                        $dependencies['pages'][$k] = $p;
                    }
                } 
                foreach ($pdeps['elements'] as $k=>$p) {
                    if (!in_array($p, $dependencies['elements'])) {
                        $dependencies['elements'][] = $p;
                    }
                } 
                
                $dependencies['pages']['"' . $this->placeholder->alias . '"'] = 'js:' . $this->placeholder->renderConf();
                $placeholder = json_encode($this->placeholder->alias);
            }
            
            $dependencies = $this->toJs($dependencies);
        }
        
        ## load conf template
        ob_start();
        include("template/conf.php");
        $conf = ob_get_clean();
        
        ## prettify conf
        $pad = "";
        if ($indent > 0) {
            $pad = str_pad("    ", ($indent) * 4);
        }
        $conf = explode("\n", $conf);
        $conf = implode("\n" . $pad, $conf);
        return trim($conf);
    }
    
    public function renderDeps() {
        if ($this->placeholder) {
            echo $this->toJs($this->placeholder->loadDeps($this->placeholder->alias)); 
        } else {
            echo $this->toJs($this->loadDeps($this->alias));
        }
    }
    
    public function loadDeps($alias = "", $flatten = true, $dependencies = false) {
        $page = $this;
        if ($alias != "") {
            $page = Page::load($alias, false); 
        }
        
        $tags = array_keys($this->loadTags($page));
        
        $isRoot = false;
        if ($dependencies === false) {
            $isRoot = true;
            $dependencies = [
                'pages' => [],
                'elements' => []
            ];
        }
        
        foreach ($tags as $tag) {
            if (strpos($tag, 'Page:') === 0) {
                $p = substr($tag, 5);
                
                if (!array_key_exists($p, $dependencies['pages'])) {
                    if ($flatten) {
                        $dependencies['pages']['"'. $p . '"'] = true;
                        $dependencies = $this->loadDeps($p, $flatten, $dependencies);
                    } else {
                        $dependencies['pages']['"'. $p . '"'] = $this->loadDeps($p, $flatten, $dependencies);
                    }
                }
            } else {
                if (!in_array($tag, $dependencies['elements'])) {
                    $dependencies['elements'][] = $tag;
                }
            }
        }
        if ($isRoot && $flatten) { 
            foreach ($dependencies['pages'] as $p=>$v) {
                $sp = Page::load(trim($p, '"'), false);
                $dependencies['pages'][$p] = "\n\t\t\tjs:" . $sp->renderConf(3, -1) . "\n\t\t";
            }
            
        }
        
        return $dependencies;
    }
    
    public function loadLoaders($page, $tags = false) {
        if ($tags === false) {
            $tag = $page->render();
            $tags = [];
            
            if ($tag[0] == 'Page') {
                $tags[] = $tag[1]['name']; 
            } 
            
            if (count($tag) == 2 && is_array($tag[1]) && !Helper::is_assoc($tag[1])) {
                $tags = $this->loadLoaders($tag[1], $tags);
            }
            else if (count($tag) == 3 && is_array($tag[2]) && !Helper::is_assoc($tag[2])) {
                $tags = $this->loadLoaders($tag[2], $tags);
            }
        } else {
            foreach ($page as $tag) {
                
                if ($tag[0] == 'Page') {
                    $tags[] = $tag[1]['name']; 
                } 
                
                if (count($tag) == 2 && is_array($tag[1]) && !Helper::is_assoc($tag[1])) {
                    $tags = $this->loadLoaders($tag[1], $tags);
                }
                else if (count($tag) == 3 && is_array($tag[2]) && !Helper::is_assoc($tag[2])) {
                    $tags = $this->loadLoaders($tag[2], $tags);
                }
            }
        }
        
        return $tags;
    }
    public function loadTags($page, $tags = false) {
        if ($tags === false) {
            $tag = $page->render();
            $tags = [];
            $tags[$tag[0]] = true;
            
            if (count($tag) == 2 && is_array($tag[1]) && !Helper::is_assoc($tag[1])) {
                $tags = $this->loadTags($tag[1], $tags);
            }
            else if (count($tag) == 3 && is_array($tag[2]) && !Helper::is_assoc($tag[2])) {
                $tags = $this->loadTags($tag[2], $tags);
            }
        } else {
            foreach ($page as $tag) {
                
                if ($tag[0] == 'Page') {
                    $tags[$tag[0] . ":" . $tag[1]['name']] = true; 
                } else {
                    $tags[$tag[0]] = true;
                }
                
                if (count($tag) == 2 && is_array($tag[1]) && !Helper::is_assoc($tag[1])) {
                    $tags = $this->loadTags($tag[1], $tags);
                }
                else if (count($tag) == 3 && is_array($tag[2]) && !Helper::is_assoc($tag[2])) {
                    $tags = $this->loadTags($tag[2], $tags);
                }
            }
        }
        
        return $tags;
    }
    
    public function renderInternalJS($indent = 0) {
        $pad = "";
        if ($indent > 0) {
            $pad = str_pad("    ", ($indent) * 4);
        }
        
        $js = $this->js();
        $js = explode("\n", $js);
        $js = implode("\n" . $pad, $js);
        return trim($js) . "\n";
    }
    
    private function getReduxPath() {
        $base = 'app';
        if ($this->getIsPlansys()) {
            $base = 'application';
        } 
        return Yii::getPathOfAlias($base . '.redux.' .  $this->store);
    }
    
    public function renderReduxActions() {
        # loop all ReduxAction files and then combine it
        $list = [];
        $suffix = 'Action';
        $listFiles = glob($this->getReduxPath() . DIRECTORY_SEPARATOR . '*'.$suffix.'.php');
        
        foreach ($listFiles as $file) {
            $class = str_replace(".php", "", basename($file));
            if (!class_exists($class, false)) {
                require($file);
            }
            $obj = new $class;
            $store = lcfirst(substr($class, 0, strlen($class) - strlen($suffix)));
            $list[$store] = $obj->actionCreators();
        }
        
        return  "return ". $this->toJs($list) . ";";
    }
    
    public function renderReduxReducers() {
        # loop all ReduxReducer files and then combine it
        $list = [];
        $suffix = 'Reducer';
        $listFiles = glob($this->getReduxPath() . DIRECTORY_SEPARATOR . '*'.$suffix.'.php');
        foreach ($listFiles as $file) {
            $class = str_replace(".php", "", basename($file));
            if (!class_exists($class, false)) {
                require($file);
            }
            $obj = new $class;
            $store = lcfirst(substr($class, 0, strlen($class) - strlen($suffix)));
            $list[strtolower(substr($class,0, strlen($class) - 7))] = $obj->list();
        }
        
        return "return ". $this->toJs($list) . ";";
    }
    
    public function renderMapInput() {
        return "return ". $this->toJs($this->mapInput());
    }
    
    public function renderMapAction() {
        return "return ". $this->toJs($this->mapAction());
    }
    
    public function renderCSS($indent = 0) {
        $css = $this->css();
        
        if (!$css) {
            return "";
        }
        
        $pad = "";
        if ($indent > 0) {
            $pad = str_pad("    ", ($indent) * 4);
        }
        $css = explode("\n", $css);
        $css = implode("\n" . $pad, $css);
        return $pad . $css;
    }
    
    public function renderComponent($indent = 0) {
        return $this->renderComponentInternal($this->render(), $indent);
    }
    
    private function renderComponentInternal($content, $level = 0) {
        $attr = '';
        $child = '';
        $tag = $content[0];
        $renderSub = function ($ct, $level)
        {
            $els = [];
            foreach($ct as $el) {
                $els[] = $this->renderComponentInternal($el, $level + 1);
            }
            $bc = "";
            if ($level > 0) {
                $bc = str_pad("    ", ($level) * 4);
            }
            return ", [\n" . str_pad("    ", ($level + 1) * 4) . implode(",", $els) . "\n" . $bc . "]";
        };
        
        $count = count($content);
        if ($count > 1) {
            if ($count >= 2) {
                if (is_array($content[1])) {
                    if (Helper::is_assoc($content[1])) {
                        $attr = ", " . $this->toJS($content[1]);
                    }
                    else if ($count == 2) {
                        $attr = $renderSub($content[1], $level);
                    }
                } else {
                    $attr = ',' . json_encode($content[1]);
                }
                
                if ($count == 3) {
                    if (is_array($content[2]) && !Helper::is_assoc($content[2])) {
                        $child = $renderSub($content[2], $level);
                    } else {
                        $child = "," . json_encode($content[2]);
                    }
                }
            }
        }
        return "h('{$tag}'{$attr}{$child})";
    }
    
    private function toJs(array $arr, $sequential_keys = false, $quotes = false, $beautiful_json = true) {
        $object = Helper::is_assoc($arr);
        $output = $object ? "{" : "[";
        $count = 0;
        foreach($arr as $key => $value) {
            if (Helper::is_assoc($arr) || (!Helper::is_assoc($arr) && $sequential_keys == true)) {
                $output.= ($quotes ? '"' : '') . $key . ($quotes ? '"' : '') . ': ';
            }
            if (is_array($value)) {
                $output.= $this->toJs($value, $sequential_keys, $quotes, $beautiful_json);
            }
            else if (is_bool($value)) {
                $output.= ($value ? 'true' : 'false');
            }
            else if (is_numeric($value)) {
                $output.= $value;
            }
            else {
                if (strpos(trim($value), "js:") === 0) {
                    $output .= trim(substr(trim($value), 3));
                } else if (strpos(trim($value), "php:") === 0) {
                    $value = eval('return print_r(' . trim(substr(trim($value), 4)) . ', true);');
                    $output .= ($quotes || $beautiful_json ? '"' : '') . $value . ($quotes || $beautiful_json ? '"' : '');
                } else {
                    $output.= ($quotes || $beautiful_json ? '"' : '') . $value . ($quotes || $beautiful_json ? '"' : '');
                }
            }
            if (++$count < count($arr)) {
                $output.= ', ';
            }
        }
        $output .= $object ? "}" : "]";
        return $output;
    }
    
    public static function resolve($alias) {
        $path = ['app.pages', 'application.pages'];
        foreach ($path as $p) {
            $f = Yii::getPathOfAlias($p . '.' . $alias) . ".php";
            if (file_exists($f)) {
               return $f;
            }
        }
        return false;
    }

     
}
