<html>
<head>
<?php
if($this->headers)
    foreach($this->headers as $header)
        echo $header."\n";
?>
</head>
<body>
<?php $this->displayContent($this->content); ?>
</body>
</html>
