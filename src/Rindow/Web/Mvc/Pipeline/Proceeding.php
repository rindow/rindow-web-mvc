<?php
namespace Rindow\Web\Mvc\Pipeline;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rindow\Event\AbstractEventProceeding;
use Rindow\Web\Mvc\Exception;

class Proceeding extends AbstractEventProceeding
{
    //protected $request;
    //protected $response;

    public function __invoke(RequestInterface $request,ResponseInterface $response)
    {
        //$this->_setRequestAndResponse($request,$response);
        return $this->proceed($request,$response);
    }
/*
    public function _setRequestAndResponse($request,$response)
    {
        $this->request = $request;
        $this->response = $response;
    }
*/
    protected function preListener($current,array $arguments)
    {
        //if($this->eventManager->getLogger()) {
        //  if(is_array($current)) {
        //      $name = get_class($current[0]).'::'.$current[1];
        //  } elseif(is_object($current)) {
        //      $name = get_class($current);
        //  } elseif (is_string($current)) {
        //      $name = $current;
        //  } else {
        //      $name = 'closure';
        //  }
        //    $this->eventManager->debug('call middleware: '.$name);
        //}
        if(count($arguments)<2)
            throw new Exception\InvalidArgumentException("proceeding needs 2 arguments.");
        return array($arguments[0],$arguments[1],$this);
    }

    public function postListener($response,$current,array $arguments)
    {
        if($response instanceof ResponseInterface)
            return $response;
        if(is_object($current)) {
            $name = get_class($current);
        } elseif(is_string($current)) {
            $name = $current;
        } elseif(is_array($current)) {
            $name = get_class($current[0]).'::'.$current[1];
        } else {
            $name = 'Closure';
        }
        throw new Exception\InvalidArgumentException('a response must be ResponseInterface from "'.$name.'"');
    }

    protected function exceptionListener($exception,$current,array $arguments)
    {
        return $exception;
    }

    public function preTerminator($terminator,array $arguments)
    {
        if(count($arguments)<2)
            throw new Exception\InvalidArgumentException("proceeding needs 2 arguments.");
        $newArgument = array($arguments[0],$arguments[1],$this->getEvent()->getParameters());
        return $newArgument;
    }

    public function postTerminator($result,$terminator,array $arguments)
    {
        return $result;
    }

    public function exceptionTerminator($exception,$terminator,array $arguments)
    {
        return $exception;
    }

    protected function isPsr7Mode()
    {
        return true;
    }
}