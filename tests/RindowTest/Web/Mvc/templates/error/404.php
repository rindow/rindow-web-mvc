<?php $this->placeholder()->set('title','Page Not Found') ?>
<div class="row">
<h1>Page Not Found</h1>
<?php if(isset($policy['display_detail']) && $policy['display_detail']) : ?>
<p>Exception: <?= $exception ?></p>
<p>Code: <?= $code ?></p>
<p>Message: <?= $message ?></p>
<hr>
<?php foreach($dataTables as $dataTable): ?>
<h3><?= $dataTable['label'] ?></h3>
<dl>
<?php foreach($dataTable['data'] as $name => $value): ?>
<dt><?= $name ?></dt>
<dd><?= var_export($value,true) ?></dd>
<?php endforeach ?>
</dl>
<?php endforeach ?>
<hr>
<p>Source: <?= $file ?>(<?= $line ?>)</p>
<p>Trace:</p>
<pre>
<?= $trace ?>
</pre>
<?php endif ?>
</div>
