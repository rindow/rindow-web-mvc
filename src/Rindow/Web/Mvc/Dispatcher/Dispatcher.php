<?php
namespace Rindow\Web\Mvc\Dispatcher;

use Rindow\Web\Mvc\Pipeline\Manager as PipelineManager;
use Rindow\Web\Mvc\Exception;
use Rindow\Web\Mvc\HttpMessageAttribute;

class Dispatcher
{
    protected $config;
    protected $serviceLocator;

    public function __construct(array $config=null,/*ServiceLocator*/ $serviceLocator=null)
    {
        if($config)
            $this->setConfig($config);
        if($serviceLocator)
            $this->setServiceLocator($serviceLocator);
    }

    public function setConfig(array $config=null)
    {
        $this->config = $config;
    }

    public function setServiceLocator($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function mergeRestfullOptions($request,$route)
    {
        if(!isset($route['vendorOptions']))
            return array($request,$route);
        if(is_string($route['vendorOptions']) && 
            $route['vendorOptions']=='restfull') {
            $restfull = array();
        } elseif(is_array($route['vendorOptions']) && 
            isset($route['vendorOptions']['restfull'])) {
            $restfull = $route['vendorOptions']['restfull'];
        } else {
            return array($request,$route);
        }
        // -- merge csrf middleware --
        $middleware = isset($restfull['middleware'])? $restfull['middleware'] : 'restfull';
        $priority = isset($restfull['priority'])? $restfull['priority'] : -1;
        $middlewares = array($middleware=>$priority);
        $route['middlewares'] = isset($route['middlewares']) ? array_merge($middlewares,$route['middlewares']):$middlewares;
        $request = $request->withAttribute(HttpMessageAttribute::RESTFULL_INFORMATION,$restfull);
        return array($request,$route);
    }

    public function dispatch($request,$response,$route,array $params=null)
    {
        list($request,$route) = $this->mergeRestfullOptions($request,$route);
        $routeName = $route['name'];
        if(!isset($route['handler']))
            throw new Exception\DomainException('a handler is not specified from a route "'.$routeName.'".');
        $handler = $this->getCallableHandler($route,$params);
        if($handler==null) {
            $className  = $this->getControllerName($route,$params);
            $methodName = $this->getMethodName($route,$params);
            $controller = $this->getController($className);
            $handler = array($controller,$methodName);
            if(!is_callable($handler))
                throw new Exception\DomainException('A action method is not found in a controller.(Class:'.$className.',Method:'.$methodName);
        }
        $request = $this->setParameters($request,$params);

        if(!$params)
            $params = array();
        if(!isset($route['middlewares'])) {
            return call_user_func($handler,$request,$response,$params);
        }
        $middlewares = $route['middlewares'];
        $namespace = isset($route['namespace']) ? $route['namespace'] : 'default';
        $pipeline = new PipelineManager();
        $pipeline->setServiceLocator($this->serviceLocator);
        foreach ($middlewares as $name => $priority) {
            if(!$priority)
                continue;
            if(isset($this->config['middlewares'][$namespace][$name]))
                $name = $this->config['middlewares'][$namespace][$name];
            elseif(isset($this->config['middlewares']['default'][$name]))
                $name = $this->config['middlewares']['default'][$name];
            else
                throw new Exception\DomainException('Undefined middleware alias:"'.$name.'" in route "'.$route['name'].'"');
                
            $pipeline->attach('dispatch',$name,$priority);
        }
        return $pipeline->run('dispatch',$request,$response,$handler,$params);
    }

    protected function setParameters($request,array $params=null)
    {
        if(empty($params))
            return $request;
        foreach ($params as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }
        return $request;
    }

    protected function getCallableHandler($route)
    {
        $routeName = $route['name'];
        $handler = $route['handler'];
        if(!isset($handler['callable']))
            return null;

        $callable = $handler['callable'];
        if(is_callable($callable))
            return $callable;
        if(!is_string($callable))
            throw new Exception\DomainException('Invalid handler in route "'.$routeName.'"');
        $component = $callable;
        $callable = $this->getController($component);
        if(!is_callable($callable))
            throw new Exception\DomainException('Invalid handler "'.$component.'" in route "'.$routeName.'"');
        return $callable;
    }

    protected function fixVariable($value,$route,$params)
    {
        if(strpos($value, '%')===0) {
            $variable = substr($value,1);
            if(!isset($params[$variable]))
                throw new Exception\DomainException('the parameter "'.$variable.'" is not found in route "'.$route['name'].'"');
            $value = str_replace(array('<','>','.','/','\\','\'','"','-','_'),'',$params[$variable]);
        }
        return $value;
    }

    protected function getControllerName($route,array $params=null)
    {
        $handler = $route['handler'];
        if(isset($handler['class']))
            return $handler['class'];
        if(!isset($handler['controller']))
            throw new Exception\DomainException('A controller parameter is not specified from a route.');
        $controllerName = $this->fixVariable($handler['controller'],$route,$params);
        $namespace = isset($route['namespace']) ? $route['namespace'] : null;
        if($namespace) {
            if(isset($this->config['controllers'])) {
                $controllerName = $namespace.'\\'.$controllerName;
            } else {
                $controllerName = $namespace.'\\'.ucfirst(strtolower($controllerName));
            }
        }
        if(isset($this->config['controllers'])) {
            if(!isset($this->config['controllers'][$controllerName]))
                throw new Exception\PageNotFoundException('A controller is not found in the invokables configuration:'.$controllerName);
            $className = $this->config['controllers'][$controllerName];
        } else {
            $className = $controllerName.'Controller';
        }
        return $className;
    }

    protected function getMethodName($route,array $params=null)
    {
        $handler = $route['handler'];
        if(isset($handler['method']))
            return $handler['method'];

        if(!isset($handler['action']))
            throw new Exception\DomainException('A action parameter is not specified from a route.');
        $action = $this->fixVariable($handler['action'],$route,$params);
        return $action.'Action';
    }

    protected function getController($className)
    {
        if($this->serviceLocator) {
            $controller = $this->serviceLocator->get($className);
        } else {
            if(!isset($this->config['invokables'][$className]))
                throw new Exception\PageNotFoundException('The controller is not allow:'.$className);
            if(!class_exists($className)) {
                throw new Exception\PageNotFoundException('the controller class is not found:'.$className);
            }
            $controller = new $className();
        }
        return $controller;
    }
}
