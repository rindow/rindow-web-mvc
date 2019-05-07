<?php
namespace Rindow\Web\Mvc;

interface Dispacher
{
    public function dispatch(
        ServerRequestInterface $request,
        ResponseInterface $response,$route,
        array $params=null);
}
