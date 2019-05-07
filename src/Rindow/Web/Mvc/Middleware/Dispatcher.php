<?php
namespace Rindow\Web\Mvc\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rindow\Web\Mvc\HttpMessageAttribute;

class Dispatcher
{
    protected $dispatcher;
    protected $viewManager;

    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function setViewManager($viewManager)
    {
        $this->viewManager = $viewManager;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $route = $request->getAttribute(HttpMessageAttribute::ROUTING_INFORMATION);
        $params = $request->getAttribute(HttpMessageAttribute::PARAMETERS);
        if($this->viewManager && isset($route['namespace']))
            $this->viewManager->setNamespace($route['namespace']);
        $response = $this->dispatcher->dispatch($request,$response,$route,$params);
        if(is_object($next))
            return $next->__invoke($request, $response);
        return call_user_func($next, $request, $response);
    }
}