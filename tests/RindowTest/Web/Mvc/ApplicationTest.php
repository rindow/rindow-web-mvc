<?php
namespace RindowTest\Web\Mvc\ApplicationTest;

use PHPUnit\Framework\TestCase;
use Rindow\Container\ModuleManager;
use Rindow\Web\View\ViewManager;

class TestStringModeViewManager extends ViewManager
{
    public function setStream($stream)
    {
    }
}

class TestLogger
{
    protected $log = array();
    public function debug($message)
    {
        $this->log[] = $message;
    }
    public function getLog()
    {
        return $this->log;
    }
}

class TestSender
{
    protected $logger;

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function send($response)
    {
        $this->logger->debug(sprintf('HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $this->logger->debug($name.':'.$value);
            }
        }

        $this->logger->debug(strval($response->getBody()));
    }
}

class TestHandler
{
    public function __invoke($request,$response,$args)
    {
        $response->getBody()->write('Hello:');
        foreach ($args as $name => $value) {
            $response->getBody()->write('{'.$name.'='.$value.'}');
        }
        $response = $response->withHeader('Content-Type','text/plain');
        return $response;
    }
}

class TestHandlerWithViewExplicite
{
    protected $viewManager;

    public function setViewManager($viewManager)
    {
        $this->viewManager = $viewManager;
    }

    public function __invoke($request,$response,$args)
    {
        return $this->viewManager->render($request,$response,'baz',$args);
    }
}

class TestHandlerWithViewImplicite
{
    public function __invoke($request,$response,$args)
    {
        return $args;
    }
}

class Test extends TestCase
{
    static $RINDOW_TEST_RESOURCES;
    public static function setUpBeforeClass()
    {
        self::$RINDOW_TEST_RESOURCES = __DIR__.'/../../../resources';
    }

    public function setUp()
    {
    }
    public function getConfig(array $options=array())
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Web\Mvc\Module' => true,
                    'Rindow\Web\View\Module' => true,
                    'Rindow\Web\Router\Module' => true,
                    'Rindow\Web\Http\Module' => true,
                ),
                'enableCache'=>false,
            ),
            'container' => array(
                'aliases' => array(
                    'Rindow\Web\Mvc\DefaultSender'        => __NAMESPACE__.'\TestSender',
                ),
                'components' => array(
                    'Rindow\Web\View\DefaultViewManager' => array(
                        'class'=>'Rindow\Web\View\ViewManager',
                        'properties' => array(
                            'stream' => array('value'=>true),
                        )
                    ),
                    __NAMESPACE__.'\TestSender' => array(
                        'properties' => array(
                            'logger' => array('ref'=>__NAMESPACE__.'\TestLogger'),
                        ),
                    ),
                    __NAMESPACE__.'\TestHandler' => array(
                    ),
                    __NAMESPACE__.'\TestHandlerWithViewExplicite' => array(
                        'properties' => array(
                            'viewManager' => array('ref'=>'Rindow\Web\Mvc\ViewManager\DefaultViewManagerWrapper'),
                        ),
                    ),
                    __NAMESPACE__.'\TestHandlerWithViewImplicite' => array(
                    ),
                    __NAMESPACE__.'\TestLogger' => array(
                        'proxy' => 'disable',
                    ),
                ),
            ),
            'web' => array(
                'mvc' => array(
                    'unittest' => false,
                ),
                'error_page_handler' => array(
                    'unittest' => true,
                ),
                'router' => array(
                    'routes' => array(
                        'foo\home' => array(
                            'path' => '/',
                            'type' => 'segment',
                            'parameters' => array(
                                'name',
                            ),
                            'namespace' => 'foo',
                            'handler' => array(
                                'callable' => __NAMESPACE__.'\TestHandler',
                            ),
                        ),
                        'foo\baz' => array(
                            'path' => '/baz',
                            'type' => 'segment',
                            'parameters' => array(
                                'name',
                            ),
                            'namespace' => 'foo',
                            'handler' => array(
                                'callable' => __NAMESPACE__.'\TestHandlerWithViewExplicite',
                            ),
                            'view' => 'baz'
                        ),
                        'foo\baz2' => array(
                            'path' => '/baz2',
                            'type' => 'segment',
                            'parameters' => array(
                                'name',
                            ),
                            'namespace' => 'foo',
                            'handler' => array(
                                'callable' => __NAMESPACE__.'\TestHandlerWithViewImplicite',
                            ),
                            'view' => 'baz',
                            'middlewares' => array(
                                'view' => -1,
                            ),
                        ),
                    ),
                ),
                'view' => array(
                    'view_managers' => array(
                        'foo' => array(
                            'template_paths' => array(
                                self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/Mvc/resources/view',
                            ),
                        ),
                    ),
                 ),
            ),
        );
        $config = array_replace_recursive($config, $options);
        return $config;
    }

    public function testPlain()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $env = $mm->getServiceLocator()->get('Rindow\Web\Http\Message\TestModeEnvironment');
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/world';
        //$env->_SERVER['REQUEST_METHOD'] = 'GET';

        $app = $mm->getServiceLocator()->get('Rindow\Web\Mvc\DefaultApplication');
        $app->run();
        $answer = array(
            'HTTP/1.1 200 OK',
            'Content-Type:text/plain',
            'Hello:{name=world}',
        );
        $logger = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestLogger');
        $this->assertEquals($answer,$logger->getLog());
    }

    public function testWithViewRender()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $env = $mm->getServiceLocator()->get('Rindow\Web\Http\Message\TestModeEnvironment');
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/baz/world';
        //$env->_SERVER['REQUEST_METHOD'] = 'GET';

        $app = $mm->getServiceLocator()->get('Rindow\Web\Mvc\DefaultApplication');
        $app->run();
        $answer = array(
            'HTTP/1.1 200 OK',
            //'Content-Type:text/plain',
            'Hello world',
        );
        $logger = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestLogger');
        $this->assertEquals($answer,$logger->getLog());
    }

    public function testWithViewMiddleware()
    {
        $config = $this->getConfig();
        $mm = new ModuleManager($config);
        $env = $mm->getServiceLocator()->get('Rindow\Web\Http\Message\TestModeEnvironment');
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/baz2/world';
        //$env->_SERVER['REQUEST_METHOD'] = 'GET';

        $app = $mm->getServiceLocator()->get('Rindow\Web\Mvc\DefaultApplication');
        $app->run();
        $answer = array(
            'HTTP/1.1 200 OK',
            //'Content-Type:text/plain',
            'Hello world',
        );
        $logger = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestLogger');
        $this->assertEquals($answer,$logger->getLog());
    }

    public function testWithStringModeViewMiddleware()
    {
        $config = array(
            'container' => array(
                'components' => array(
                    'Rindow\Web\View\DefaultViewManager' => array(
                        'class'=>__NAMESPACE__.'\TestStringModeViewManager',
                    ),
                ),
            ),
        );
        $config = $this->getConfig($config);
        $mm = new ModuleManager($config);
        $env = $mm->getServiceLocator()->get('Rindow\Web\Http\Message\TestModeEnvironment');
        $env->_SERVER['SCRIPT_NAME'] = '/index.php';
        $env->_SERVER['REQUEST_URI'] = '/baz2/world';
        //$env->_SERVER['REQUEST_METHOD'] = 'GET';

        $app = $mm->getServiceLocator()->get('Rindow\Web\Mvc\DefaultApplication');
        $app->run();
        $answer = array(
            'HTTP/1.1 200 OK',
            //'Content-Type:text/plain',
            'Hello world',
        );
        $logger = $mm->getServiceLocator()->get(__NAMESPACE__.'\TestLogger');
        $this->assertEquals($answer,$logger->getLog());
    }
}