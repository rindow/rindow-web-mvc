<?php
namespace RindowTest\Web\Mvc\RedirectorTest;

use PHPUnit\Framework\TestCase;
use Rindow\Stdlib\Cache\CacheFactory;
use Rindow\Container\ModuleManager;

class Test extends TestCase
{
    public function setUp()
    {
    }

    public function getContainer($options)
    {
        $namespace = __NAMESPACE__;
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
        $config = array_replace_recursive($config, $options);
        $mm = new ModuleManager($config);
        return $mm->getServiceLocator();
    }

	public function test()
	{
        $config = array(
            'web' => array(
                'router' => array(
                    'routes' => array(
                        __NAMESPACE__.'\home' => array(
                            'path' => '/',
                            'type' => 'literal',
                            'namespace' => __NAMESPACE__,
                        ),
                        __NAMESPACE__.'\test' => array(
                            'path' => '/test',
                            'type' => 'segment',
                            'parameters' => array('action','id'),
                            'namespace' => __NAMESPACE__,
                        ),
                        'OtherNameSpace'.'\test' => array(
                            'path' => '/otherpath',
                            'type' => 'segment',
                            'parameters' => array('action','id'),
                            'namespace' => __NAMESPACE__,
                        ),
                    ),
                ),
            ),
        );
        $container = $this->getContainer($config);
        $env = $container->get('Rindow\Web\Http\Message\TestModeEnvironment');
        $env->_SERVER['SCRIPT_NAME'] = '/app/web.php';
        $env->_SERVER['REQUEST_URI'] = '/app/web.php/test/ping/boo';
        $router = $container->get('Rindow\Web\Mvc\DefaultRouter');
        $url = $container->get('Rindow\Web\Mvc\Util\DefaultUrlGenerator');
        $redirect = $container->get('Rindow\Web\Mvc\Util\DefaultRedirector');

        $response = $container->get('Rindow\Web\Mvc\DefaultResponse');
        $request  = $container->get('Rindow\Web\Mvc\DefaultServerRequest');
        $url->setRequest($request);
        $path = $url->getPath();
        $routeInfo = $router->match($request,$path);
        $url->setRouteInfo($routeInfo);

        $result = $redirect->toRoute($response,'test',array('action'=>'bar','id'=>'boo'));
        $this->assertEquals('/app/web.php/test/bar/boo', $result->getHeaderLine('Location'));
        $result = $redirect->toPath($response,'/hoge');
        $this->assertEquals('/app/web.php/hoge', $result->getHeaderLine('Location'));
        $result = $redirect->toRoute($response,'test',array('action'=>'bar','id'=>'boo'),array('namespace'=>'OtherNameSpace'));
        $this->assertEquals('/app/web.php/otherpath/bar/boo', $result->getHeaderLine('Location'));
        $result = $redirect->toRoute($response,'test',array('action'=>'bar','id'=>'boo'),array('query'=>array('a'=>'b')));
        $this->assertEquals('/app/web.php/test/bar/boo?a=b', $result->getHeaderLine('Location'));
        $result = $redirect->toPath($response,'/hoge',array('query'=>array('a'=>'b')));
        $this->assertEquals('/app/web.php/hoge?a=b', $result->getHeaderLine('Location'));
        $result = $redirect->toRoute($response,'home');
        $this->assertEquals('/app/web.php', $result->getHeaderLine('Location'));
        $result = $redirect->toPath($response,'/');
        $this->assertEquals('/app/web.php', $result->getHeaderLine('Location'));
        $result = $redirect->toUrl($response,'/');
        $this->assertEquals('/', $result->getHeaderLine('Location'));
        $result = $redirect->toUrl($response,'http://foo.bar.com/');
        $this->assertEquals('http://foo.bar.com/', $result->getHeaderLine('Location'));

        $result = $redirect->toRoute($response,'test',array('action'=>'bar','id'=>'boo'));
        $this->assertEquals(302, $result->getStatusCode());
        $result = $redirect->toRoute($response,'test',array('action'=>'bar','id'=>'boo'),array('permanent'=>true));
        $this->assertEquals(301, $result->getStatusCode());
        $result = $redirect->toRoute($response,'test',array('action'=>'bar','id'=>'boo'),array('status'=>307));
        $this->assertEquals(307, $result->getStatusCode());

        $result = $redirect->toPath($response,'/hoge');
        $this->assertEquals(302, $result->getStatusCode());
        $result = $redirect->toPath($response,'/hoge',array('permanent'=>true));
        $this->assertEquals(301, $result->getStatusCode());

        $result = $redirect->toUrl($response,'/hoge');
        $this->assertEquals(302, $result->getStatusCode());
        $result = $redirect->toUrl($response,'/hoge',array('permanent'=>true));
        $this->assertEquals(301, $result->getStatusCode());

	}
}