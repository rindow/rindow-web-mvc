<?php
namespace Rindow\Web\Mvc\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rindow\Web\Mvc\Exception;

class ErrorPageHandler
{
    protected $handler;
    protected $config;
    protected $viewMamager;

    public function setHandler($handler)
    {
        $this->errorPageHandler = $handler;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        set_error_handler(array($this->errorPageHandler,'handleError'));
        try {
            if(is_object($next))
                $response =  $next->__invoke($request, $response);
            else
                $response = call_user_func($next, $request, $response);
            restore_error_handler();
            return $response;
        } catch(Exception\PageNotFoundException $e) {
            $status = 404;
        } catch(Exception\ForbiddenException $e) {
            $status = 403;
        } catch(\PHPUnit_Framework_Error_Notice $e) {
            restore_error_handler();
            throw $e;
        } catch(\Exception $e) {
            $status = 503;
        } catch(\Error $e) {
            $status = 503;
        }
        if(isset($this->config['unittest']) && $this->config['unittest']) {
            restore_error_handler();
            throw $e;
        }
        $this->errorPageHandler->setStatus($status);
        $response = $this->errorPageHandler->handleException($request,$response,$e);
        restore_error_handler();
        return $response;
    }
}
