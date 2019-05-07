<?php $this->placeholder()->set('title','Server Error') ?>
<div class="row">
<h1>Server Error</h1>
<?php if(isset($policy['display_detail']) && $policy['display_detail']) : ?>
<h4><?= $message ?></h4>
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
<p>Exception: <?= $exception ?></p>
<p>Code: <?= $code ?></p>
<p>Message: <?= $message ?></p>
<p>Source: <?= $file ?>(<?= $line ?>)</p>
<p>Trace:</p>
<pre>
<?= $trace ?>
</pre>
<?php endif ?>
</div>
