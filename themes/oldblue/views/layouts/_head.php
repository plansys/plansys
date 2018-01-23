<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="<?= Yii::app()->controller->staticUrl('/css/bootstrap.min.css'); ?>" type="text/css" />
    <link rel="stylesheet" href="<?= Yii::app()->controller->staticUrl('/css/non-responsive.css'); ?>" type="text/css" />
    <link rel="stylesheet" href="<?= Yii::app()->controller->staticUrl('/css/font-awesome.min.css'); ?>" type="text/css" />
    <link rel="stylesheet" href="<?= Yii::app()->controller->staticUrl('/css/main.css'); ?>" type="text/css" />
    <script type="text/javascript" src="<?= Yii::app()->controller->staticUrl('/js/HotKeys.js'); ?>"></script>
    <title><?php echo CHtml::encode(Yii::app()->controller->pageTitle); ?></title>
   
    <?php ThemeManager::registerCoreScript(); ?> 
</head>