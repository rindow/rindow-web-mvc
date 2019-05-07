<?php
namespace Rindow\Web\Mvc\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rindow\Web\Mvc\HttpMessageAttribute;
use Rindow\Web\Mvc\Exception;

class ViewManager
{
    protected $viewManager;

    public function setViewManager($viewManager)
    {
        $this->viewManager = $viewManager;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        /*
         *  Call a Handler
         */
        if(is_object($next))
            $newResponse = $next->__invoke($request, $response);
        else
            $newResponse = call_user_func($next, $request, $response);
        if($newResponse instanceof ResponseInterface)
            return $newResponse;
        /*
         *  Call the ViewManager
         */
        $variables = null;
        $templateName = null;
        if(is_string($newResponse)) {
            $templateName = $newResponse;
        } elseif(is_array($newResponse)) {
            if(isset($newResponse['%view'])) {
                $templateName = $newResponse['%view'];
                unset($newResponse['%view']);
            } else {
                $route = $request->getAttribute(HttpMessageAttribute::ROUTING_INFORMATION);
                if(isset($route['view']))
                    $templateName = $route['view'];
            }
            if(isset($newResponse['%response'])) {
                $response = $newResponse['%response'];
                unset($newResponse['%response']);
                if(!($response instanceof ResponseInterface))
                    throw new Exception\InvalidArgumentException('%response must be ResponseInterface');
            }
            $variables = $newResponse;
        } else {
            $type = is_object($newResponse) ? get_class($newResponse) : gettype($newResponse);
            throw new Exception\InvalidArgumentException('response is invalid type:'.$type);
        }
        return $this->viewManager->render($request,$response,$templateName,$variables);
    }
}