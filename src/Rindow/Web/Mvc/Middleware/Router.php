<?php
namespace Rindow\Web\Mvc\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rindow\Web\Mvc\HttpMessageAttribute;
use Rindow\Web\Mvc\Exception;

class Router
{
    protected $router;
    protected $urlGenerator;

    public function setRouter($router)
    {
        $this->router = $router;
    }

    public function setUrlGenerator($urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function setErrorPageHandler($errorPageHandler)
    {
        $this->errorPageHandler = $errorPageHandler;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $this->urlGenerator->setRequest($request);
        $path = $this->urlGenerator->getPath();
        try {
            $routeInfo = $this->router->match($request,$path);
            if(isset($routeInfo['error'])) {
                $this->errorPageHandler->addDataTable('Routing Information',array('path'=>$path));
                throw new Exception\PageNotFoundException(
                    $routeInfo['error']['reason'],$routeInfo['error']['status']);
            }
            $request = $request->withAttribute(HttpMessageAttribute::ROUTING_INFORMATION,$routeInfo['route']);
            $request = $request->withAttribute(HttpMessageAttribute::PARAMETERS,$routeInfo['params']);
            $this->urlGenerator->setRouteInfo($routeInfo);
            if(is_object($next))
                return $next->__invoke($request, $response);
            return call_user_func($next, $request, $response);
        } catch(\Exception $e) {
            if(isset($routeInfo['route']))
                $this->errorPageHandler->addDataTable('Routing Information',$routeInfo['route']);
            if(isset($routeInfo['params']))
                $this->errorPageHandler->addDataTable('Routing Variables',$routeInfo['params']);
            throw $e;
        }
    }
}