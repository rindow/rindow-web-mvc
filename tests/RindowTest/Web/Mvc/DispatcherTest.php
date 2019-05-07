<?php
namespace RindowTest\Web\Mvc\DispatcherTest;

use PHPUnit\Framework\TestCase;
use Rindow\Web\Http\Message\ServerRequestFactory;
use Rindow\Web\Http\Message\Response;
use Rindow\Web\Mvc\Dispatcher\Dispatcher;

class TestLogger
{
    protected $log = array();
    public function debug($message)
    {
        $this->log[]=$message;
    }
    public function getLog()
    {
        return $this->log;
    }
}

abstract class AbstractMiddleware
{
    public function __construct($logger = null) {
        $this->logger = $logger;
    }
    public function getName()
    {
        $class = get_class($this);
        return substr($class, strrpos($class, '\\')+1);
    }
    public function __invoke($request,$response,$next)
    {
        $this->logger->debug('middleware('.$this->getName().')');
        $response = call_user_func($next,$request,$response);
        return $response;
    }
    public function frontend($request,$response)
    {
        return array($request,$response);
    }
    public function backend($request,$response)
    {
        return array($request,$response);
    }
}
class TestMiddleware1 extends AbstractMiddleware {}
class TestMiddleware2 extends AbstractMiddleware {}
class TestMiddleware3 extends AbstractMiddleware {}

class TestCallableController
{
    public static $staticLogger;

    public function __invoke($request,$response,$args)
    {
        $tt = '';
        foreach ($args as $key => $value) {
            $tt .= '{'.$key.'='.$value.'}';
        }
        self::$staticLogger->debug('handler('.$tt.')');
        return $response;
    }
}

class TestController
{
    public static $staticLogger;

    public function indexAction($request,$response,$args)
    {
        $tt = '';
        foreach ($args as $key => $value) {
            $tt .= '{'.$key.'='.$value.'}';
        }
        self::$staticLogger->debug('handler('.$tt.')');
        return $response;
    }
}

class Test extends TestCase
{
    public function getDispatcherConfig($logger)
    {
        $config = array(
            'middlewares' => array(
                'testnamespace' => array(
                    'mw1' => new TestMiddleware1($logger),
                    'mw2' => new TestMiddleware2($logger),
                    'mw3' => new TestMiddleware3($logger),
                ),
            ),
            'controllers' => array(
                'testnamespace\test' => __NAMESPACE__.'\TestController',
            ),
            'invokables' => array(
                __NAMESPACE__.'\TestController' => true,
                __NAMESPACE__.'\TestCallableController' => true,
            ),
        );
        return $config;
    }

    public function testCallbackClosure()
    {
        $logger = new TestLogger();
        $config = $this->getDispatcherConfig($logger);
        $request = ServerRequestFactory::fromTestEnvironment();
        $response = new Response();
        $route = array(
            'handler' => array(
                'callable' => function ($request,$response,$args) use ($logger) {
                    $tt = '';
                    foreach ($args as $key => $value) {
                        $tt .= '{'.$key.'='.$value.'}';
                    }
                    $logger->debug('handler('.$tt.')');
                    return $response;
                }
            ),
            'namespace' => 'testnamespace',
            'name' => 'testnamespace\baz',
            'middlewares' => array(
                'mw1' => -1, //
                'mw3' => -3, // <-- low priority
                'mw2' => -2, //
            ),
        );
        $params = array('foo'=>'bar');
        $dispacher = new Dispatcher($config);
        $dispacher->dispatch($request,$response,$route,$params);
        $result = array(
            'middleware(TestMiddleware1)',
            'middleware(TestMiddleware2)',
            'middleware(TestMiddleware3)',
            'handler({foo=bar})',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testCallbackClassName()
    {
        $logger = new TestLogger();
        TestCallableController::$staticLogger = $logger;
        $config = $this->getDispatcherConfig($logger);
        $request = ServerRequestFactory::fromTestEnvironment();
        $response = new Response();
        $route = array(
            'handler' => array(
                'callable' => __NAMESPACE__.'\TestCallableController',
            ),
            'namespace' => 'testnamespace',
            'name' => 'testnamespace\baz',
            'middlewares' => array(
                'mw1' => -1, //
                'mw3' => -3, // <-- low priority
                'mw2' => -2, //
            ),
        );
        $params = array('foo'=>'bar');
        $dispacher = new Dispatcher($config);
        $dispacher->dispatch($request,$response,$route,$params);
        $result = array(
            'middleware(TestMiddleware1)',
            'middleware(TestMiddleware2)',
            'middleware(TestMiddleware3)',
            'handler({foo=bar})',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testClassAndMethod()
    {
        $logger = new TestLogger();
        TestController::$staticLogger = $logger;
        $config = $this->getDispatcherConfig($logger);
        $request = ServerRequestFactory::fromTestEnvironment();
        $response = new Response();
        $route = array(
            'handler' => array(
                'class' => __NAMESPACE__.'\TestController',
                'method' => 'indexAction',
            ),
            'namespace' => 'testnamespace',
            'name' => 'testnamespace\baz',
            'middlewares' => array(
                'mw1' => -1, //
                'mw3' => -3, // <-- low priority
                'mw2' => -2, //
            ),
        );
        $params = array('foo'=>'bar');
        $dispacher = new Dispatcher($config);
        $dispacher->dispatch($request,$response,$route,$params);
        $result = array(
            'middleware(TestMiddleware1)',
            'middleware(TestMiddleware2)',
            'middleware(TestMiddleware3)',
            'handler({foo=bar})',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testControllerAndAction()
    {
        $logger = new TestLogger();
        TestController::$staticLogger = $logger;
        $config = $this->getDispatcherConfig($logger);
        $request = ServerRequestFactory::fromTestEnvironment();
        $response = new Response();
        $route = array(
            'handler' => array(
                'controller' => 'test',
                'action' => 'index',
            ),
            'namespace' => 'testnamespace',
            'name' => 'testnamespace\baz',
            'middlewares' => array(
                'mw1' => -1, //
                'mw3' => -3, // <-- low priority
                'mw2' => -2, //
            ),
        );
        $params = array('foo'=>'bar');
        $dispacher = new Dispatcher($config);
        $dispacher->dispatch($request,$response,$route,$params);
        $result = array(
            'middleware(TestMiddleware1)',
            'middleware(TestMiddleware2)',
            'middleware(TestMiddleware3)',
            'handler({foo=bar})',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testParameters()
    {
        $logger = new TestLogger();
        TestController::$staticLogger = $logger;
        $config = $this->getDispatcherConfig($logger);
        $request = ServerRequestFactory::fromTestEnvironment();
        $response = new Response();
        $route = array(
            'handler' => array(
                'controller' => '%controller',
                'action' => '%action',
            ),
            'namespace' => 'testnamespace',
            'name' => 'testnamespace\baz',
            'middlewares' => array(
                'mw1' => -1, //
                'mw3' => -3, // <-- low priority
                'mw2' => -2, //
            ),
        );
        $params = array('controller'=>'test','action'=>'index');
        $dispacher = new Dispatcher($config);
        $dispacher->dispatch($request,$response,$route,$params);
        $result = array(
            'middleware(TestMiddleware1)',
            'middleware(TestMiddleware2)',
            'middleware(TestMiddleware3)',
            'handler({controller=test}{action=index})',
        );
        $this->assertEquals($result,$logger->getLog());
    }


    public function testWithoutMiddleware()
    {
        $logger = new TestLogger();
        TestController::$staticLogger = $logger;
        $config = $this->getDispatcherConfig($logger);
        $request = ServerRequestFactory::fromTestEnvironment();
        $response = new Response();
        $route = array(
            'handler' => array(
                'controller' => '%controller',
                'action' => '%action',
            ),
            'namespace' => 'testnamespace',
            'name' => 'testnamespace\baz',
        );
        $params = array('controller'=>'test','action'=>'index');
        $dispacher = new Dispatcher($config);
        $dispacher->dispatch($request,$response,$route,$params);
        $result = array(
            'handler({controller=test}{action=index})',
        );
        $this->assertEquals($result,$logger->getLog());
    }
}
