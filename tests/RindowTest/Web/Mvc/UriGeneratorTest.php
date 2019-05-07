<?php
namespace RindowTest\Web\Mvc\UrlGeneratorTest;

use PHPUnit\Framework\TestCase;
use Rindow\Stdlib\Cache\CacheFactory;
use Rindow\Container\ModuleManager;
use Rindow\Web\Http\Message\ServerRequestFactory;
use Rindow\Web\Http\Message\TestModeEnvironment;

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
                    'Rindow\Web\Router\Module' => true,
                ),
                'enableCache'=>false,
            ),
            'container' => array(
                'aliases' => array(
                    'Rindow\Web\Mvc\DefaultServerRequest'=>'dummy',
                    'Rindow\Web\Mvc\DefaultResponse'=>'dummy',
                ),
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
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/app/web.php';
        $env->_SERVER['REQUEST_URI'] = '/app/web.php/test/ping/boo';
        $container = $this->getContainer($config);
        $router = $container->get('Rindow\Web\Mvc\DefaultRouter');
        $url = $container->get('Rindow\Web\Mvc\Util\DefaultUrlGenerator');

        $request = ServerRequestFactory::fromTestEnvironment($env);
        $url->setRequest($request);
        $path = $url->getPath();
        $routeInfo = $router->match($request,$path);
        $url->setRouteInfo($routeInfo);

        $result = $url->fromRoute('test',array('action'=>'bar','id'=>'boo'));
        $this->assertEquals('/app/web.php/test/bar/boo', $result);
        $result = $url->fromPath('/hoge');
        $this->assertEquals('/app/web.php/hoge', $result);
        $result = $url->rootPath();
        $this->assertEquals('/app/web.php', $result);
        $result = $url->prefix();
        $this->assertEquals('/app', $result);
        $result = $url->fromRoute('test',array('action'=>'bar','id'=>'boo'),array('namespace'=>'OtherNameSpace'));
        $this->assertEquals('/app/web.php/otherpath/bar/boo', $result);
        $result = $url->fromRoute('test',array('action'=>'bar','id'=>'boo'),array('query'=>array('a'=>'b')));
        $this->assertEquals('/app/web.php/test/bar/boo?a=b', $result);
        $result = $url->fromPath('/hoge',array('query'=>array('a'=>'b')));
        $this->assertEquals('/app/web.php/hoge?a=b', $result);
        $result = $url->fromRoute('home');
        $this->assertEquals('/app/web.php', $result);
        $result = $url->fromPath('/');
        $this->assertEquals('/app/web.php', $result);
    }
}