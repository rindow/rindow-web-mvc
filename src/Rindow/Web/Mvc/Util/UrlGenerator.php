<?php
namespace Rindow\Web\Mvc\Util;

use Psr\Http\Message\ServerRequestInterface;

class UrlGenerator
{
    protected $router;
    protected $serverParams;
    protected $path;
    protected $pathPrefix;
    protected $rootPath;
    protected $routeInfo;
    protected $namespace;
    protected $scriptNames = array();

    public function __construct($router=null)
    {
        if($router)
            $this->setRouter($router);
    }

    public function setRouter($router)
    {
        $this->router = $router;
    }

    public function setRequest(ServerRequestInterface $request)
    {
        $this->serverParams = $request->getServerParams();
        $this->path = null;
        $this->pathPrefix = null;
        $this->rootPath = null;
    }

    public function setScriptNames(array $scriptNames=null)
    {
        if($scriptNames==null)
            return;
        $this->scriptNames = $scriptNames;
    }

    public function hasRequest()
    {
        return isset($this->serverParams);
    }

    protected function translateScriptName($scriptName)
    {
        if(isset($this->scriptNames[$scriptName]))
            return $this->scriptNames[$scriptName];
        return $scriptName;
    } 

    public function getPath()
    {
        if($this->path)
            return $this->path;
        $path = '';
        if(isset($this->serverParams['REQUEST_URI'])) {
            $uri = $this->serverParams['REQUEST_URI'];
            $scriptName = $this->translateScriptName($this->serverParams['SCRIPT_NAME']);
            $start = strlen($scriptName);
            if(strpos($uri,$scriptName)!==0) {
                $start = strlen($this->getPathPrefix());
            }
            $pos = strpos($uri,'?');
            if($pos===false) {
                $path = substr($uri,$start);
            } else {
                $path = substr($uri,$start,$pos-$start);
            }
            if(strlen($path)==0)
                $path = '/';
        }
        return $this->path = $path;
    }

    public function getPathPrefix()
    {
        if($this->pathPrefix)
            return $this->pathPrefix;

        $path = '';
        if(isset($this->serverParams['SCRIPT_NAME'])) {
            $path = dirname($this->translateScriptName($this->serverParams['SCRIPT_NAME']));
            if($path==='\\' || $path==='/')
                $path='';
        }
        return $this->pathPrefix = $path;
    }

    public function getRootPath()
    {
        if($this->rootPath)
            return $this->rootPath;

        if(!isset($this->serverParams['SCRIPT_NAME']) || !isset($this->serverParams['REQUEST_URI'])) {
            return '';
        }
        $scriptName = $this->translateScriptName($this->serverParams['SCRIPT_NAME']);
        $requestUri = $this->serverParams['REQUEST_URI'];
        if(substr($requestUri,0,strlen($scriptName))==$scriptName)
            return $scriptName;
        $path = dirname($scriptName);
        if($path==='\\' || $path==='/')
            $path='';
        return $this->rootPath = $path;
    }

    public function setRouteInfo(array $routeInfo=null)
    {
        $this->routeInfo = $routeInfo;
        if(isset($routeInfo['route']['namespace']))
            $this->namespace = $routeInfo['route']['namespace'];
        else
            $this->namespace = null;
    }

    public function currentNamespace()
    {
        return $this->namespace;
    }

    public function currentRouteName()
    {
        if(isset($this->routeInfo['route']['name']))
            return $this->routeInfo['route']['name'];
        return null;
    }

    public function getFullRouteName($routeName,array $options=null)
    {
        if(isset($options['namespace'])) {
            $namespace = $options['namespace'];
        } else {
            $namespace = $this->namespace;
        }
        if($namespace && $namespace!='\\')
            return $namespace.'\\'.$routeName;
        return $routeName;
    }

    /* ************************************************** *
     *       interface for application developers
     * ************************************************** */
    public function fromRoute($routeName,array $params=null,array $options=null)
    {
        $url = $this->getRootPath();
        $routeName = $this->getFullRouteName($routeName,$options);
        $path = $this->router->assemble($routeName,$params,$options);
        if($path=='/') {
            if($url=='')
                $url = '/';
        } else {
            $url .= $path;
        }
        if(isset($options['query']))
            $url .= '?'.http_build_query($options['query']);
        return $url;
    }

    public function fromPath($path,array $options=null)
    {
        $url = $this->getRootPath();
        if($path=='/') {
            if($url=='')
                $url = '/';
        } else {
            $url .= $path;
        }
        if(isset($options['query']))
            $url .= '?'.http_build_query($options['query']);
        return $url;
    }

    public function rootPath()
    {
        return $this->getRootPath();
    }

    public function prefix()
    {
        return $this->getPathPrefix();
    }
}
