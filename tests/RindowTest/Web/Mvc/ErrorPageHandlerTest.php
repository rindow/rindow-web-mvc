<?php
namespace RindowTest\Web\Mvc\ErrorPageHandlerTest;

use PHPUnit\Framework\TestCase;
use Rindow\Web\Mvc\ErrorPageHandler\ErrorPageHandler;
use Rindow\Web\Http\Message\ServerRequestFactory;
use Rindow\Web\Http\Message\Response;
use Rindow\Web\Http\Message\Stream;
use Rindow\Container\ModuleManager;

class Test extends TestCase
{
    public function setUp()
    {
    }

    public function testTextPlainDefault()
    {
        $errorPageHandler = new ErrorPageHandler();
        $errorPageHandler->setStatus(404);
        $errorPageHandler->addDataTable('label-test',array('foo-name'=>'bar-value','baz-name'=>array('bar-item','boo-item')));
        $request = ServerRequestFactory::fromTestEnvironment();
        $e = new \Exception('Error',123); 
        $request = $request->withHeader('Accept','text/plain');
        $response = new Response(null,null,new Stream(fopen('php://temp','w+b')));
        $response = $errorPageHandler->handleException($request,$response,$e);

        $this->assertEquals('text/plain',$response->getHeaderLine('Content-Type'));
        $this->assertEquals(404,$response->getStatusCode());
        $response->getBody()->rewind();
        $result = $response->getBody()->getContents();
        $answer = <<<EOD
exception:Exception
message:Error
code:123
EOD;
        $answer = str_replace(array("\r","\n"), array("",""), $answer);
        $result = str_replace(array("\r","\n"), array("",""), $result);
        $this->assertEquals($answer,$result);
        $this->assertNotContains('label-test',$result);
        $this->assertNotContains('file:',$result);
        $this->assertNotContains('line:',$result);
        $this->assertNotContains('trace:',$result);
    }

    public function testTextPlainDetailDefault()
    {
        $config = array(
            'error_policy' => array(
                'display_detail' => true,
            ),
        );
        $errorPageHandler = new ErrorPageHandler();
        $errorPageHandler->setConfig($config);
        $errorPageHandler->setStatus(404);
        $request = ServerRequestFactory::fromTestEnvironment();
        $e = new \Exception('Error',123); 
        $request = $request->withHeader('Accept','text/plain');
        $response = new Response(null,null,new Stream(fopen('php://temp','w+b')));
        $response = $errorPageHandler->handleException($request,$response,$e);

        $this->assertEquals('text/plain',$response->getHeaderLine('Content-Type'));
        $this->assertEquals(404,$response->getStatusCode());
        $response->getBody()->rewind();
        $result = $response->getBody()->getContents();
        $result = str_replace(array("\r","\n"), array("",""), $result);
        $this->assertContains('file:',$result);
        $this->assertContains('line:',$result);
        $this->assertContains('trace:',$result);
    }

    public function testTextPlainDetailWithDataTable()
    {
        $config = array(
            'error_policy' => array(
                'display_detail' => true,
            ),
        );
        $errorPageHandler = new ErrorPageHandler();
        $errorPageHandler->setConfig($config);
        $errorPageHandler->setStatus(404);
        $errorPageHandler->addDataTable('label-test',array('foo-name'=>'bar-value','baz-name'=>array('bar-item','boo-item')));
        $request = ServerRequestFactory::fromTestEnvironment();
        $e = new \Exception('Error',123); 
        $request = $request->withHeader('Accept','text/plain');
        $response = new Response(null,null,new Stream(fopen('php://temp','w+b')));
        $response = $errorPageHandler->handleException($request,$response,$e);

        $this->assertEquals('text/plain',$response->getHeaderLine('Content-Type'));
        $this->assertEquals(404,$response->getStatusCode());
        $response->getBody()->rewind();
        $result = $response->getBody()->getContents();
        $result = str_replace(array("\r","\n"), array("",""), $result);
        $this->assertContains('label-test',$result);
        $this->assertContains('foo-name',$result);
        $this->assertContains('bar-value',$result);
        $this->assertContains('baz-name',$result);
        $this->assertContains('bar-item',$result);
        $this->assertContains('boo-item',$result);
        $this->assertContains('file:',$result);
        $this->assertContains('trace:',$result);
    }

    public function testHtmlServerErrorWithViewManager()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Web\Mvc\Module' => true,
                    'Rindow\Web\Http\Module' => true,
                    'Rindow\Web\Router\Module' => true,
                    'Rindow\Web\View\Module' => true,
                ),
                'enableCache'=>false,
            ),
            'web' => array(
                'view' => array(
                    'view_managers' => array(
                        'default' => array(
                            'template_paths' => __DIR__.'/templates',
                        ),
                    ),
                ),
            ),
        );
        $mm = new ModuleManager($config);
        $sm = $mm->getServiceLocator();
        $errorPageHandler = $sm->get('Rindow\Web\Mvc\DefaultErrorPageHandler');
        $errorPageHandler->setStatus(503);
        $errorPageHandler->addDataTable('label-test',array('foo-name'=>'bar-value','baz-name'=>array('bar-item','boo-item')));
        $request = ServerRequestFactory::fromTestEnvironment();
        $e = new \Exception('Error',123); 
        $request = $request->withHeader('Accept','text/html');
        $response = new Response(null,null,new Stream(fopen('php://temp','w+b')));
        $response = $errorPageHandler->handleException($request,$response,$e);

        $this->assertEquals('text/html',$response->getHeaderLine('Content-Type'));
        $this->assertEquals(503,$response->getStatusCode());
        $response->getBody()->rewind();
        $result = $response->getBody()->getContents();
        $result = str_replace(array("\r","\n"), array("",""), $result);
        $this->assertContains('Server Error',$result);
    }

    public function testHtmlPageNotFoundWithViewManager()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Web\Mvc\Module' => true,
                    'Rindow\Web\Http\Module' => true,
                    'Rindow\Web\Router\Module' => true,
                    'Rindow\Web\View\Module' => true,
                ),
                'enableCache'=>false,
            ),
            'web' => array(
                'view' => array(
                    'view_managers' => array(
                        'default' => array(
                            'template_paths' => __DIR__.'/templates',
                        ),
                    ),
                ),
            ),
        );
        $mm = new ModuleManager($config);
        $sm = $mm->getServiceLocator();
        $errorPageHandler = $sm->get('Rindow\Web\Mvc\DefaultErrorPageHandler');
        $errorPageHandler->setStatus(404);
        $request = ServerRequestFactory::fromTestEnvironment();
        $e = new \Exception('Error',123); 
        $request = $request->withHeader('Accept','text/html');
        $response = new Response(null,null,new Stream(fopen('php://temp','w+b')));
        $response = $errorPageHandler->handleException($request,$response,$e);

        $this->assertEquals('text/html',$response->getHeaderLine('Content-Type'));
        $this->assertEquals(404,$response->getStatusCode());
        $response->getBody()->rewind();
        $result = $response->getBody()->getContents();
        $result = str_replace(array("\r","\n"), array("",""), $result);
        $this->assertContains('Page Not Found',$result);
    }

    public function testJsonWithoutDetail()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Web\Mvc\Module' => true,
                    'Rindow\Web\Http\Module' => true,
                    'Rindow\Web\Router\Module' => true,
                ),
                'enableCache'=>false,
            ),
            'web' => array(
                'view' => array(
                    'view_managers' => array(
                        'default' => array(
                            'template_paths' => __DIR__.'/templates',
                        ),
                    ),
                ),
            ),
        );
        $mm = new ModuleManager($config);
        $sm = $mm->getServiceLocator();
        $errorPageHandler = $sm->get('Rindow\Web\Mvc\DefaultErrorPageHandler');
        $errorPageHandler->setStatus(503);
        $errorPageHandler->addDataTable('label-test',array('foo-name'=>'bar-value','baz-name'=>array('bar-item','boo-item')));
        $request = ServerRequestFactory::fromTestEnvironment();
        $e = new \Exception('Error',123); 
        $request = $request->withHeader('Accept','application/json');
        $response = new Response(null,null,new Stream(fopen('php://temp','w+b')));
        $response = $errorPageHandler->handleException($request,$response,$e);

        $this->assertEquals('application/json',$response->getHeaderLine('Content-Type'));
        $this->assertEquals(503,$response->getStatusCode());
        $response->getBody()->rewind();
        $result = $response->getBody()->getContents();
        $result = str_replace(array("\r","\n"), array("",""), $result);
        $this->assertContains('{"error":{"message":"error"}}',$result);
    }

    public function testJsonWithDetail()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Web\Mvc\Module' => true,
                    'Rindow\Web\Http\Module' => true,
                    'Rindow\Web\Router\Module' => true,
                ),
                'enableCache'=>false,
            ),
            'web' => array(
                'view' => array(
                    'view_managers' => array(
                        'default' => array(
                            'template_paths' => __DIR__.'/templates',
                        ),
                    ),
                ),
                'error_page_handler' => array(
                    'error_policy' => array(
                        'display_detail' => true,
                    ),
                ),
            ),
        );
        $mm = new ModuleManager($config);
        $sm = $mm->getServiceLocator();
        $errorPageHandler = $sm->get('Rindow\Web\Mvc\DefaultErrorPageHandler');
        $errorPageHandler->setStatus(503);
        $errorPageHandler->addDataTable('label-test',array('foo-name'=>'bar-value','baz-name'=>array('bar-item','boo-item')));
        $request = ServerRequestFactory::fromTestEnvironment();
        $e = new \Exception('Error',123); 
        $request = $request->withHeader('Accept','application/json');
        $response = new Response(null,null,new Stream(fopen('php://temp','w+b')));
        $response = $errorPageHandler->handleException($request,$response,$e);

        $this->assertEquals('application/json',$response->getHeaderLine('Content-Type'));
        $this->assertEquals(503,$response->getStatusCode());
        $response->getBody()->rewind();
        $result = $response->getBody()->getContents();
        $result = str_replace(array("\r","\n"), array("",""), $result);
        $this->assertContains('{"error":{"exception":"Exception","message":"Error","code":123,',$result);
    }

    public function testXmlWithoutDetail()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Web\Mvc\Module' => true,
                    'Rindow\Web\Http\Module' => true,
                    'Rindow\Web\Router\Module' => true,
                ),
                'enableCache'=>false,
            ),
            'web' => array(
                'view' => array(
                    'view_managers' => array(
                        'default' => array(
                            'template_paths' => __DIR__.'/templates',
                        ),
                    ),
                ),
            ),
        );
        $mm = new ModuleManager($config);
        $sm = $mm->getServiceLocator();
        $errorPageHandler = $sm->get('Rindow\Web\Mvc\DefaultErrorPageHandler');
        $errorPageHandler->setStatus(503);
        $errorPageHandler->addDataTable('label-test',array('foo-name'=>'bar-value','baz-name'=>array('bar-item','boo-item')));
        $request = ServerRequestFactory::fromTestEnvironment();
        $e = new \Exception('Error',123); 
        $request = $request->withHeader('Accept','application/xml');
        $response = new Response(null,null,new Stream(fopen('php://temp','w+b')));
        $response = $errorPageHandler->handleException($request,$response,$e);

        $this->assertEquals('application/xml',$response->getHeaderLine('Content-Type'));
        $this->assertEquals(503,$response->getStatusCode());
        $response->getBody()->rewind();
        $result = $response->getBody()->getContents();
        $result = str_replace(array("\r","\n"), array("",""), $result);
        $this->assertContains('<?xml version="1.0" encoding="utf-8"?><error><message>error</message></error>',$result);
    }

    public function testXmlWithDetail()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Web\Mvc\Module' => true,
                    'Rindow\Web\Http\Module' => true,
                    'Rindow\Web\Router\Module' => true,
                ),
                'enableCache'=>false,
            ),
            'web' => array(
                'view' => array(
                    'view_managers' => array(
                        'default' => array(
                            'template_paths' => __DIR__.'/templates',
                        ),
                    ),
                ),
                'error_page_handler' => array(
                    'error_policy' => array(
                        'display_detail' => true,
                    ),
                ),
            ),
        );
        $mm = new ModuleManager($config);
        $sm = $mm->getServiceLocator();
        $errorPageHandler = $sm->get('Rindow\Web\Mvc\DefaultErrorPageHandler');
        $errorPageHandler->setStatus(503);
        $errorPageHandler->addDataTable('label-test',array('foo-name'=>'bar-value','baz-name'=>array('bar-item','boo-item')));
        $request = ServerRequestFactory::fromTestEnvironment();
        $e = new \Exception('Error',123); 
        $request = $request->withHeader('Accept','application/xml');
        $response = new Response(null,null,new Stream(fopen('php://temp','w+b')));
        $response = $errorPageHandler->handleException($request,$response,$e);

        $this->assertEquals('application/xml',$response->getHeaderLine('Content-Type'));
        $this->assertEquals(503,$response->getStatusCode());
        $response->getBody()->rewind();
        $result = $response->getBody()->getContents();
        $result = str_replace(array("\r","\n"), array("",""), $result);
        $this->assertContains('><exception>Exception</exception><message>Error</message><code>123</code><file>',$result);
    }

    public function testHandleError()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Web\Mvc\Module' => true,
                    'Rindow\Web\Http\Module' => true,
                    'Rindow\Web\Router\Module' => true,
                ),
                'enableCache'=>false,
            ),
        );
        $mm = new ModuleManager($config);
        $sm = $mm->getServiceLocator();
        $errorPageHandler = $sm->get('Rindow\Web\Mvc\DefaultErrorPageHandler');
        $errorPageHandler->setStatus(404);
        $request = ServerRequestFactory::fromTestEnvironment();
        $e = new \Exception('Error',123); 
        $request = $request->withHeader('Accept','text/html');
        $response = new Response(null,null,new Stream(fopen('php://temp','w+b')));

        set_error_handler(array($errorPageHandler,'handleError'));
        try {
            trigger_error('Foo Error',E_USER_ERROR);
        } catch(\ErrorException $e) {

        }
        $this->assertEquals('Foo Error',$e->getMessage());
    }
}