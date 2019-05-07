<?php
namespace Rindow\Web\Mvc;

use Psr\Http\Message\ServerRequestInterface;

interface Router
{
    public function match(ServerRequestInterface $request, $path=null);
    public function assemble($routeName,array $params=null,array $options=null);
}
