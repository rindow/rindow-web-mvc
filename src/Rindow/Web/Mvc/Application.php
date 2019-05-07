<?php
namespace Rindow\Web\Mvc;

use Rindow\Web\Mvc\Pipeline\Manager as PipelineManger;

class Application
{
    protected static $methods = array(
        'CONNECT' => true,
        'DELETE' => true,
        'GET' => true,
        'HEAD' => true,
        'OPTIONS' => true,
        'PATCH' => true,
        'POST' => true,
        'PUT' => true,
        'TRACE' => true,
    );

    protected $config;
    protected $router;
    protected $request;
    protected $response;
    protected $urlGenerator;
    protected $serviceLocator;
    protected $messageSender;

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function setResponse($response)
    {
        $this->response = $response;
    }

    public function setSender($sender)
    {
        $this->messageSender = $sender;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getSender()
    {
        return $this->messageSender;
    }

    public function run($type=null)
    {
        if($type==null)
            $type = 'default';
        if(!is_string($type))
            throw new Exception\InvalidArgumentException('run type must be string.');
        if(!isset($this->config['middlewares'][$type])) {
            throw new Exception\InvalidArgumentException('the middleware type is not found.:'.$type);
        }
        
        $middlewares = $this->config['middlewares'][$type];
        $pipeline = new PipelineManger();
        $pipeline->setServiceLocator($this->serviceLocator);
        foreach ($middlewares as $component => $priority) {
            if($priority!==false)
                $pipeline->attach($type,$component,$priority);
        }
        $response = $pipeline->run($type,$this->request,$this->response);
        if(isset($this->config['unittest']) && $this->config['unittest'])
            return $response;
        $this->messageSender->send($response);
    }
}
