<head>
<title><?= $this->placeholder()->get('title','DEFAULT') ?></title>
</head>
<body>
<h1><?= $this->placeholder()->get('header','default header') ?></h1>
<?php $this->displayContent($this->content); ?>
</body>