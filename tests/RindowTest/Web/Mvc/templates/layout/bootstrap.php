<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title><?= $this->placeholder()->get('title','Title') ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, minimum-scale=1, user-scalable=no" />
        <link rel="stylesheet" href="<?= $this->url()->prefix() ?>/css/bootstrap.min.css" />
        <link rel="stylesheet" href="<?= $this->url()->prefix() ?>/css/bootstrap-theme.min.css" />
        <link rel="stylesheet" href="<?= $this->url()->prefix() ?>/css/colors.css" />
        <link rel="stylesheet" href="<?= $this->url()->prefix() ?>/css/style.css" />
        <!--[if lt IE 9]>
            <script src="<?= $this->url()->prefix() ?>/js/html5shiv.js"></script>
            <script src="<?= $this->url()->prefix() ?>/js/respond.min.js"></script>
        <![endif]-->
    </head>
    <body>
        <!-- Static navbar -->
        <header class="navbar navbar-static-top navbar-inverse">
            <div class="container">
              <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="<?= $this->url()->rootPath().'/' ?>">My Site</a>
              </div>
              <div class="navbar-collapse collapse">
                <ul class="nav navbar-nav">
                  <li class="active"><a href="#">Link</a></li>
                  <li><a href="#">Link</a></li>
                  <li><a href="#">Link</a></li>
                  <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Dropdown <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                      <li><a href="#">Action</a></li>
                      <li><a href="#">Another action</a></li>
                      <li><a href="#">Something else here</a></li>
                      <li class="divider"></li>
                      <li class="dropdown-header">Nav header</li>
                      <li><a href="#">Separated link</a></li>
                      <li><a href="#">One more separated link</a></li>
                    </ul>
                  </li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                  <li><a href="#"><span class="glyphicon glyphicon-home"></span></a></li>
                  <li><a href="#"><span class="glyphicon glyphicon-search"></span></a></li>
                  <li><a href="#"><span class="glyphicon glyphicon-cog"></span></a></li>
                </ul>
              </div><!--/.nav-collapse -->
            </div><!-- /.container -->
        </header>

        <div class="container">
            <!-- Main Content -->
            <?= $this->displayContent($this->content) ?>
        </div><!-- /.container -->
        <script src="<?= $this->url()->prefix() ?>/js/jquery-1.10.2.min.js"></script>
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
    </body>
</html>
