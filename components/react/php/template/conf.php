{
    alias: '<?= $this->alias ?>',
    css: <?= $css ?>,
<?php if ($this->placeholder): ?>
    placeholder: <?=  $placeholder; ?>,
<?php endif; ?>
    loaders: <?= $loaders . ",\n"; ?>
<?php if ($this->isRoot || $this->showDeps): ?>
    dependencies: <?= "\n\t\t" . $dependencies . "\n\t"; ?>,
<?php endif; ?>
<?php 
    $map = [];
    
    if ($mapInput) {
        $map[] = "\t\tinput: function() {\n\t\t\t   " . trim($mapInput) . "\n\t\t}";
    }
    
    if ($mapAction) {
        $map[] = "\t\taction: function() {\n\t\t\t   " . trim($mapAction) . "\n\t\t}";
    }
    
    if (count($map) > 0) {
        echo "\tmap: {\n";
        echo implode(",\n", $map);
        echo "\n\t},\n";
    } 
?>
<?php
    if ($this->isRoot) {
        $redux = [];
        
        if ($actionCreators) {
            $redux[] = "\t\tactionCreators: function() {\n\t\t\t   " . trim($actionCreators) . "\n\t\t}";
        }
        
        if ($reducers) {
            $redux[] = "\t\treducers: function(Immutable) {\n\t\t\t  " . trim($reducers) . "\n\t\t}";
        }
        
        if (count($redux) > 0) {
            echo "\tredux: {\n";
            echo implode(",\n", $redux);
            echo "\n\t},\n";
        } 
    }
?>
    js: function() {
        <?= $js ?>
    },
    render: function(h) {
        return <?= $component ?>;
    }
}

<?php if ($this->isRoot): ?>//# sourceURL=<?= $this->alias ?><?php endif; ?>