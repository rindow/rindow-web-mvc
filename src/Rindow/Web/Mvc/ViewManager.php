<?php
namespace Rindow\Web\Mvc;

interface ViewManager
{
    public function setStream($stream);
    public function setConfig($config);
    public function setCurrentTemplatePaths(array $currentTemplatePaths);
    public function render($templateName, array $variables=null, $templatePath=null);
}
