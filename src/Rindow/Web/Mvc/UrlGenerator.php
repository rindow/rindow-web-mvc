<?php
namespace Rindow\Web\Mvc;

use Psr\Http\Message\ServerRequestInterface;

interface UrlGenerator
{
    public function setRequest(ServerRequestInterface $request);
    public function getPath();
    public function setRouteInfo(array $routeInfo=null);
    /* ************************************************** *
     *       Optional:
     *       Interface for application developers
     * ************************************************** */
    public function fromRoute($routeName,array $params=null,array $options=null);
    public function fromPath($path,array $options=null);
    public function rootPath();
    public function prefix();
}
