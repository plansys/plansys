
    function pageReady(f) {
        if (!window.Root) {
            setTimeout(function() {
                pageReady(f);
            },10)
        }
        else f();
    }

    pageReady(function() {
        window.Loader.baseUrl = "<?= $baseUrl ?>";
        window.Loader.basePath = "<?= $basePath ?>";
        window.render( "<?= ($this->placeholder ?  $this->placeholder->alias : $this->alias) ?>");
    });