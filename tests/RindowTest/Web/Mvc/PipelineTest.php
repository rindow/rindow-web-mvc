<?php
namespace RindowTest\Web\Mvc\PipelineTest;

use PHPUnit\Framework\TestCase;
use Rindow\Container\Container;
use Rindow\Web\Http\Message\ServerRequestFactory;
use Rindow\Web\Http\Message\ServerRequest;
use Rindow\Web\Http\Message\Response;
use Rindow\Web\Mvc\Pipeline\Proceeding;
use Rindow\Web\Mvc\DefaultPipelinePriority as Priority;

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
        if(!($request instanceof ServerRequest))
            throw new \Exception('illegal request in '.get_class($this));
        if(!($response instanceof Response)) {
            throw new \Exception('illegal response in '.get_class($this));
        }
        if(!($next instanceof Proceeding))
            throw new \Exception('illegal next context in '.get_class($this));
            
        $this->logger->debug('start '.$this->getName());
        list($request,$response) = $this->frontend($request,$response);
        $response = call_user_func($next,$request,$response);
        if(!($response instanceof Response)) {
            throw new \Exception('illegal result response in '.get_class($this));
        }
        list($request,$response) = $this->backend($request,$response);
        $this->logger->debug('end '.$this->getName());
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
class TestBootstrap extends AbstractMiddleware
{}
class TestErrorhandler extends AbstractMiddleware
{}
class TestRouter extends AbstractMiddleware
{}
class TestDispacher extends AbstractMiddleware
{}
class TestDebugger extends AbstractMiddleware
{}

class TestBeforeRouter extends AbstractMiddleware
{
    public function frontend($request,$response)
    {
        if($request->getAttribute('before')!=null)
            throw new \Exception('invalid request TestBeforeRouter');
        if($response->getHeaderLine('before')!=null)
            throw new \Exception('invalid response TestBeforeRouter');
        return array(
            $request->withAttribute('before', 'before'),
            $response->withHeader('before', 'before'),
        );
    }
    public function backend($request,$response)
    {
        if($response->getHeaderLine('before')!='before')
            throw new \Exception('invalid response TestBeforeRouter');
        if($response->getHeaderLine('after')!='after')
            throw new \Exception('invalid response TestBeforeRouter');
        return array(
            $request,
            $response,
        );
    }
}

class TestIllegalResponse extends AbstractMiddleware
{
    public function backend($request,$response)
    {
        return array($request,$response=null);
    }
}

class TestAfterRouter extends AbstractMiddleware
{
    public function frontend($request,$response)
    {
        if($request->getAttribute('before')!='before')
            throw new \Exception('invalid request TestBeforeRouter');
        if($response->getHeaderLine('before')!='before')
            throw new \Exception('invalid response TestBeforeRouter');
        return array(
            $request->withAttribute('after', 'after'),
            $response->withHeader('after', 'after'),
        );
    }

    public function backend($request,$response)
    {
        if($response->getHeaderLine('before')!='before')
            throw new \Exception('invalid response TestBeforeRouter');
        if($response->getHeaderLine('after')!='after')
            throw new \Exception('invalid response TestBeforeRouter');
        return array(
            $request,
            $response,
        );
    }
}

class Test extends TestCase
{
    public function setUp()
    {
    }

    public function getContainer(array $options)
    {
        $config = array(
            'container' => array(
                'components' => array(
                    'Pipeline' => array(
                        'class' => 'Rindow\Web\Mvc\Pipeline\Manager',
                        'properties' => array(
                            'serviceLocator' => array('ref'=>'ServiceLocator'),
                        ),
                    ),
                    'Logger' => array(
                        'class' => __NAMESPACE__.'\TestLogger',
                    ),
                    'bootstrap_component' => array(
                        'class' => __NAMESPACE__.'\TestBootstrap',
                        'constructor_args' => array(
                            'logger' => array('ref'=>'Logger'),
                        ),
                    ),
                    'errorhandler_component' => array(
                        'class' => __NAMESPACE__.'\TestErrorhandler',
                        'constructor_args' => array(
                            'logger' => array('ref'=>'Logger'),
                        ),
                    ),
                    'router_component' => array(
                        'class' => __NAMESPACE__.'\TestRouter',
                        'constructor_args' => array(
                            'logger' => array('ref'=>'Logger'),
                        ),
                    ),
                    'dispacher_component' => array(
                        'class' => __NAMESPACE__.'\TestDispacher',
                        'constructor_args' => array(
                            'logger' => array('ref'=>'Logger'),
                        ),
                    ),
                    'debugger_component' => array(
                        'class' => __NAMESPACE__.'\TestDebugger',
                        'constructor_args' => array(
                            'logger' => array('ref'=>'Logger'),
                        ),
                    ),
                ),
            ),
            'web' => array(
                'mvc' => array(
                    'pipelines' => array(
                        'default' => array(
                            'bootstrap' => array(
                                'bootstrap_component' => Priority::BOOTSTRAP,
                            ),
                            'main' => array(
                                'errorhandler_component' => Priority::ERRORHANDLER,
                                'router_component'       => Priority::ROUTER,
                                'dispacher_component'    => Priority::DISPACHER,
                            ),
                            'final' => array(
                                'debugger_component' => Priority::DEBUGGER,
                            ),
                        ),
                    ),
                ),
            ),
        );
        $config = array_replace_recursive($config, $options);
        $container = new Container($config['container']);
        $container->setInstance('config',$config);
        $container->setInstance('ServiceLocator',$container);
        return $container;
    }

    public function testDefault()
    {
        $container = $this->getContainer(array());
        $pipeline = $container->get('Pipeline');
        $config = $container->get('config');
        $config = $config['web']['mvc']['pipelines']['default'];

        foreach($config as $group => $middlewares) {
            foreach ($middlewares as $component => $priority) {
                $pipeline->attach($group,$component,$priority);
            }
        }

        $request = ServerRequestFactory::fromTestEnvironment();
        $response = new Response();
        foreach(array_keys($config) as $group) {
            $response = $pipeline->run($group,$request,$response);
        }
        $logger = $container->get('Logger');
        $result = array(
            "start TestBootstrap",
            "end TestBootstrap",
            "start TestErrorhandler",
            "start TestRouter",
            "start TestDispacher",
            "end TestDispacher",
            "end TestRouter",
            "end TestErrorhandler",
            "start TestDebugger",
            "end TestDebugger",
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testInjectMiddleware()
    {
        $config = array(
            'container' => array(
                'components' => array(
                    'before_router' => array(
                        'class' => __NAMESPACE__.'\TestBeforeRouter',
                        'constructor_args' => array(
                            'logger' => array('ref'=>'Logger'),
                        ),
                    ),
                    'after_router' => array(
                        'class' => __NAMESPACE__.'\TestAfterRouter',
                        'constructor_args' => array(
                            'logger' => array('ref'=>'Logger'),
                        ),
                    ),
                ),
            ),
            'web' => array(
                'mvc' => array(
                    'pipelines' => array(
                        'default' => array(
                            'main' => array(
                                'before_router' => Priority::BEFORE_ROUTER,
                                'after_router'  => Priority::AFTER_ROUTER,
                            ),
                        ),
                    ),
                ),
            ),
        );
        $container = $this->getContainer($config);
        $pipeline = $container->get('Pipeline');
        $config = $container->get('config');
        $config = $config['web']['mvc']['pipelines']['default'];

        foreach($config as $group => $middlewares) {
            foreach ($middlewares as $component => $priority) {
                $pipeline->attach($group,$component,$priority);
            }
        }

        $request = ServerRequestFactory::fromTestEnvironment();
        $response = new Response();
        foreach(array_keys($config) as $group) {
            $response = $pipeline->run($group,$request,$response);
        }
        $logger = $container->get('Logger');
        $result = array(
            "start TestBootstrap",
            "end TestBootstrap",
            "start TestErrorhandler",
            "start TestBeforeRouter",
            "start TestRouter",
            "start TestAfterRouter",
            "start TestDispacher",
            "end TestDispacher",
            "end TestAfterRouter",
            "end TestRouter",
            "end TestBeforeRouter",
            "end TestErrorhandler",
            "start TestDebugger",
            "end TestDebugger",
        );
        $this->assertEquals($result,$logger->getLog());

        $this->assertEquals('before',$response->getHeaderLine('before'));
        $this->assertEquals('after',$response->getHeaderLine('after'));
    }

    /**
     * @expectedException        Rindow\Web\Mvc\Exception\InvalidArgumentException
     * @expectedExceptionMessage a response must be ResponseInterface from "RindowTest\Web\Mvc\PipelineTest\TestIllegalResponse
     */
    public function testIllegalResponse()
    {
        $config = array(
            'container' => array(
                'components' => array(
                    'after_router' => array(
                        'class' => __NAMESPACE__.'\TestIllegalResponse',
                        'constructor_args' => array(
                            'logger' => array('ref'=>'Logger'),
                        ),
                    ),
                ),
            ),
            'web' => array(
                'mvc' => array(
                    'pipelines' => array(
                        'default' => array(
                            'main' => array(
                                'after_router' => Priority::AFTER_ROUTER,
                            ),
                        ),
                    ),
                ),
            ),
        );
        $container = $this->getContainer($config);
        $pipeline = $container->get('Pipeline');
        $config = $container->get('config');
        $config = $config['web']['mvc']['pipelines']['default'];

        foreach($config as $group => $middlewares) {
            foreach ($middlewares as $component => $priority) {
                $pipeline->attach($group,$component,$priority);
            }
        }

        $request = ServerRequestFactory::fromTestEnvironment();
        $response = new Response();
        foreach(array_keys($config) as $group) {
            $response = $pipeline->run($group,$request,$response);
        }
    }
}