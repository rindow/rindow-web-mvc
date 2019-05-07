<?php
namespace Rindow\Web\Mvc;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ErrorPageHandler
{
    public function setStatus($status);
    public function addDataTable($label, array $data)
    public function handleError($level, $message, $file = null, $line = null);
    public function handleException(ServerRequestInterface $request, ResponseInterface $response, \Exception $e)
}
