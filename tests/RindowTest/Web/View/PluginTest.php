<?php
namespace RindowTest\Web\View\PluginTest;

use PHPUnit\Framework\TestCase;
use Rindow\Stdlib\Cache\CacheFactory;
use Rindow\Container\ModuleManager;
use Rindow\Web\Http\Message\ServerRequestFactory;
use Rindow\Web\Http\Message\TestModeEnvironment;
use Rindow\Web\Http\Message\Response;
use Rindow\Web\Router\Router;
use Rindow\Web\Form\Annotation as Form;
use Rindow\Stdlib\Entity\AbstractEntity;

/**
 * @Form\Form(attributes={"action"="/app/form","method"="post"})
 */
class Entity extends AbstractEntity
{
    /**
     * @Form\Input(type="text",label="Full Name")
     */
    public $name;
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

    public function getContainer($options)
    {
        $namespace = __NAMESPACE__;
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Web\Mvc\Module' => true,
                    'Rindow\Web\Router\Module' => true,
                    'Rindow\Web\Form\Module' => true,
                    'Rindow\Web\View\Module' => true,
                ),
                'annotation_manager' => true,
                'enableCache' => false,
            ),
            'web' => array(
                'view' => array(
                    //'layout' => 'layout/layout',
                    'template_paths' => array(
                        'default'  => self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/View/Resources/views/global',
                        $namespace => self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Web/View/Resources/views/local',
                    ),
                ),
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

    public function testUrl()
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
                    ),
                ),
            ),
        );
        $container = $this->getContainer($config);
        $env = new TestModeEnvironment();
        $env->_SERVER['SCRIPT_NAME'] = '/app/web.php';
        $env->_SERVER['REQUEST_URI'] = '/app/web.php/test/ping/boo';
        $router = $container->get('Rindow\Web\Router\DefaultRouter');
        $viewManager = $container->get('Rindow\Web\View\DefaultViewManager');
        $url = $container->get('Rindow\Web\Mvc\DefaultUrlGenerator');

        $request = ServerRequestFactory::fromTestEnvironment($env);
        $url->setRequest($request);
        $path = $url->getPath();
        $route = $router->match($request,$path);
        $url->setRouteInfo($route);
        $config = $container->get('config');
        $viewManager->setCurrentTemplatePaths($config['web']['view']['template_paths']);

        $result = $viewManager->render('index/url',array('action'=>'bar','id'=>'boo'));
        $this->assertEquals('/app/web.php/test/bar/boo', $result);

    }
    public function testPlaceholder()
    {
        $config = array(
            'web' => array(
                'view' => array(
                    'layout' => 'layout/placeholder',
                ),
            ),
        );
        $container = $this->getContainer($config);
        $viewManager = $container->get('Rindow\Web\View\DefaultViewManager');
        $config = $container->get('config');
        $viewManager->setConfig($config['web']['view']);
        $viewManager->setCurrentTemplatePaths($config['web']['view']['template_paths']);
        $answer = <<<EOD
<head>
<title>TEST</title>
</head>
<body>
<h1>default header</h1>
Hello World
</body>
EOD;
        $result = $viewManager->render('index/placeholder',array('name'=>'World'));
        $answer = str_replace(array("\r","\n"), array("",""), $answer);
        $result = str_replace(array("\r","\n"), array("",""), $result);
        $this->assertEquals($answer,$result);
    }

    public function testForm()
    {
        $config = array(
            'web' => array(
                'form' => array(
                    'autoCsrfToken' => false,
                ),
            ),
        );
        $container = $this->getContainer($config);
        $viewManager = $container->get('Rindow\Web\View\DefaultViewManager');
        $formBuilder = $container->get('Rindow\Web\Form\DefaultFormContextBuilder');

        $config = $container->get('config');
        $viewManager->setCurrentTemplatePaths($config['web']['view']['template_paths']);
        $entity = new Entity();
        $formContext = $formBuilder->build($entity);
        $form = $formContext->getForm();

        $answer = <<<EOD
<form action="/app/form" method="post">
<label>Full Name</label>
<input type="text" name="name">
<input type="submit" value="Go" name="go">
</form>
EOD;
        $result = $viewManager->render('index/form',array('form'=>$form));
        $answer = str_replace(array("\r","\n"), array("",""), $answer);
        $result = str_replace(array("\r","\n"), array("",""), $result);
        $this->assertEquals($answer,$result);
    }

    public function testView()
    {
        $config = array(
        );
        $container = $this->getContainer($config);
        $viewManager = $container->get('Rindow\Web\View\DefaultViewManager');
        $config = $container->get('config');
        $viewManager->setCurrentTemplatePaths($config['web']['view']['template_paths']);
        $answer = <<<EOD
main(partical(a),partical(b))
EOD;
        $result = $viewManager->render('index/view');
        $answer = str_replace(array("\r","\n"), array("",""), $answer);
        $result = str_replace(array("\r","\n"), array("",""), $result);
        $this->assertEquals($answer,$result);
    }

    public function testStreamModeView()
    {
        $config = array(
            'container' => array(
                'components' => array(
                    'Rindow\Web\View\DefaultViewManager' => array(
                        'properties' => array(
                            'stream' => array('value'=>true),
                        ),
                    ),
                ),
            ),
        );
        $container = $this->getContainer($config);
        $viewManager = $container->get('Rindow\Web\View\DefaultViewManager');
        $config = $container->get('config');
        $viewManager->setCurrentTemplatePaths($config['web']['view']['template_paths']);
        $answer = <<<EOD
main(partical(a),partical(b))
EOD;
        $result = stream_get_contents($viewManager->render('index/view'));
        $answer = str_replace(array("\r","\n"), array("",""), $answer);
        $result = str_replace(array("\r","\n"), array("",""), $result);
        $this->assertEquals($answer,$result);
    }
}
