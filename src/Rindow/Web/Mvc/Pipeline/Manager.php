<?php
namespace Rindow\Web\Mvc\Pipeline;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rindow\Event\AbstractEventManager;
use Rindow\Event\EventInterface;
use Iterator;

class Manager extends AbstractEventManager
{
    protected function doNotify($callback,$event,$listener)
    {
        throw new Exception\DomainException('Not supported operation');
    }

    public function run($event,RequestInterface $request,ResponseInterface $response,$terminator=null,array $params=null)
    {
        if($terminator==null)
            $terminator = new Terminator();
        if($params) {
            $event = $this->toEvent($event);
            $event->setParameters($params);
        }
        $eventQueue = $this->prepareCall($event);
        if($eventQueue==null) {
            return call_user_func($terminator,$request,$response,$params);
        }
        return $this->call($event,array($request,$response),$terminator,$eventQueue);
    }

    protected function createProceeding(
        EventInterface $event,
        array $args,
        $terminator,
        Iterator $iterator,
        /*ServiceLocator*/ $serviceLocator=null)
    {
        $proceeding = new Proceeding(
            $event,
            $terminator,
            $iterator,
            $serviceLocator);
        return array($proceeding,$args);
    }

    protected function isPsr7Mode()
    {
        return true;
    }
/*
    protected function callTerminator($terminator, $event)
    {
        $request = $this->request;
        $response = $this->response;
        $this->request = null;
        $this->response = null;

        $args = $event->getArgs();
        return call_user_func($terminator,$request,$response,$args);
    }
*/
}
