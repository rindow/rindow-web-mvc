<?php
namespace Rindow\Web\Mvc;

class Module
{
    public function getConfig()
    {
        return array(
            'container' => array(
                'aliases' => array(
                    /* **********
                     *  Inject your router and view manager and more. 
                    'Rindow\\Web\\Mvc\\DefaultServerRequest' => 'your_server_request_http_message',
                    'Rindow\\Web\\Mvc\\DefaultResponse'      => 'your_response_http_message',
                    'Rindow\\Web\\Mvc\\DefaultViewManager'   => 'your_default_view_manager',
                    'Rindow\\Web\\Mvc\\DefaultRouter'        => 'your_web_router',
                    'Rindow\\Web\\Mvc\\DefaultCookieContextFactory' => 'your_cookie_context_factory',
                     */
                    'Rindow\\Web\\Mvc\\DefaultUrlGenerator'  => 'Rindow\\Web\\Mvc\\Util\\DefaultUrlGenerator',
                    'Rindow\\Web\\Mvc\\DefaultRedirector'    => 'Rindow\\Web\\Mvc\\Util\\DefaultRedirector',
                    'Rindow\\Web\\Mvc\\DefaultErrorPageHandler' => 'Rindow\\Web\\Mvc\\ErrorPageHandler\\DefaultErrorPageHandler',
                    'Rindow\\Web\\Mvc\\DefaultSender'        => 'Rindow\\Web\\Mvc\\Sender\\DefaultSender',
                    'Rindow\\Web\\Mvc\\DefaultDispatcher'    => 'Rindow\\Web\\Mvc\\Dispatcher\\DefaultDispatcher',
                    /* Middlewares */
                    'Rindow\\Web\\Mvc\\DefaultErrorPageHandlerMiddleware' => 'Rindow\\Web\\Mvc\\Middleware\\DefaultErrorPageHandlerMiddleware',
                    'Rindow\\Web\\Mvc\\DefaultRouterMiddleware'     => 'Rindow\\Web\\Mvc\\Middleware\\DefaultRouterMiddleware',
                    'Rindow\\Web\\Mvc\\DefaultDispatcherMiddleware' => 'Rindow\\Web\\Mvc\\Middleware\\DefaultDispatcherMiddleware',
                    //'Rindow\\Web\\Mvc\\DefaultForceCsrfTokenValidationMiddleware' => 'You must set some Csrf-Token-Validator-Middleware',
                ),
                'components' => array(
                    'Rindow\\Web\\Mvc\\DefaultApplication' => array(
                        'class' => 'Rindow\\Web\\Mvc\\Application',
                        'properties' => array(
                            'serviceLocator' => array('ref' => 'ServiceLocator'),
                            'config' => array('config' => 'web::mvc'),
                            'request'  => array('ref'=>'Rindow\\Web\\Mvc\\DefaultServerRequest'),
                            'response' => array('ref'=>'Rindow\\Web\\Mvc\\DefaultResponse'),
                            'sender'   => array('ref'=>'Rindow\\Web\\Mvc\\DefaultSender'),
                        ),
                    ),
                    'Rindow\\Web\\Mvc\\Util\\DefaultUrlGenerator' => array(
                        'class' => 'Rindow\\Web\\Mvc\\Util\\UrlGenerator',
                        'properties' => array(
                            'router' => array('ref' => 'Rindow\\Web\\Mvc\\DefaultRouter'),
                        ),
                    ),
                    'Rindow\\Web\\Mvc\\Util\\DefaultRedirector' => array(
                        'class' => 'Rindow\\Web\\Mvc\\Util\\Redirector',
                        'properties' => array(
                            'urlGenerator' => array('ref' => 'Rindow\\Web\\Mvc\\DefaultUrlGenerator'),
                        ),
                    ),
                    'Rindow\\Web\\Mvc\\Dispatcher\\DefaultDispatcher' => array(
                        'class' => 'Rindow\\Web\\Mvc\\Dispatcher\\Dispatcher',
                        'properties' => array(
                            'serviceLocator' => array('ref' => 'ServiceLocator'),
                            'config' => array('config' => 'web::dispatcher'),
                        ),
                    ),
                    'Rindow\\Web\\Mvc\\Sender\\DefaultSender' => array(
                        'class' => 'Rindow\\Web\\Mvc\\Sender\\Sender',
                    ),
                    'Rindow\\Web\\Mvc\\Middleware\\DefaultErrorPageHandlerMiddleware' => array(
                        'class' => 'Rindow\\Web\\Mvc\\Middleware\\ErrorPageHandler',
                        'properties' => array(
                            'handler' => array('ref' => 'Rindow\\Web\\Mvc\\DefaultErrorPageHandler'),
                            'config' => array('config'=>'web::error_page_handler'),
                        ),
                    ),
                    'Rindow\\Web\\Mvc\\Middleware\\DefaultRouterMiddleware' => array(
                        'class' => 'Rindow\\Web\\Mvc\\Middleware\\Router',
                        'properties' => array(
                            'router' => array('ref' => 'Rindow\\Web\\Mvc\\DefaultRouter'),
                            'urlGenerator' => array('ref' => 'Rindow\\Web\\Mvc\\DefaultUrlGenerator'),
                            'errorPageHandler' => array('ref' => 'Rindow\\Web\\Mvc\\DefaultErrorPageHandler'),
                        ),
                    ),
                    'Rindow\\Web\\Mvc\\Middleware\\DefaultDispatcherMiddleware' => array(
                        'class' => 'Rindow\\Web\\Mvc\\Middleware\\Dispatcher',
                        'properties' => array(
                            'dispatcher' => array('ref' => 'Rindow\\Web\\Mvc\\DefaultDispatcher'),
                        ),
                    ),
                    'Rindow\\Web\\Mvc\\Middleware\\DefaultViewManagerMiddleware' => array(
                        'class' => 'Rindow\\Web\\Mvc\\Middleware\\ViewManager',
                        'properties' => array(
                            'viewManager' => array('ref' => 'Rindow\\Web\\Mvc\\ViewManager\\DefaultViewManagerWrapper'),
                        ),
                    ),
                    'Rindow\\Web\\Mvc\\Middleware\\DefaultRestfullValidatorMiddleware' => array(
                        'class' => 'Rindow\\Web\\Mvc\\Middleware\\RestfullValidator',
                        'properties' => array(
                            'viewManager' => array('ref' => 'Rindow\\Web\\Mvc\\ViewManager\\DefaultViewManagerWrapper'),
                        ),
                    ),
                    'Rindow\\Web\\Mvc\\ViewManager\\DefaultViewManagerWrapper' => array(
                        'class' => 'Rindow\\Web\\Mvc\\ViewManager\\ViewManagerWrapper',
                        'properties' => array(
                            'serviceLocator' => array('ref' => 'ServiceLocator'),
                            'config' => array('config' => 'web::view')
                        ),
                    ),
                    'Rindow\\Web\\Mvc\\ErrorPageHandler\\DefaultErrorPageHandler' => array(
                        'class' => 'Rindow\\Web\\Mvc\\ErrorPageHandler\\ErrorPageHandler',
                        'properties' => array(
                            'serviceLocator' => array('ref' => 'ServiceLocator'),
                            'viewManagerName' => array('value' => 'Rindow\\Web\\Mvc\\ViewManager\\DefaultViewManagerWrapper'),
                            'config' => array('config' => 'web::error_page_handler')
                        ),
                    ),
                ),
            ),
            'web' => array(
                'mvc' => array(
                    'middlewares' => array(
                        'default' => array(
                            'Rindow\\Web\\Mvc\\DefaultErrorPageHandlerMiddleware' => DefaultPipelinePriority::ERRORHANDLER,

                            // **************************************************************************
                            // Validation without consideration of context is very ugly. I hate that.
                            // But that complaint is useless for me because it is a de facto standard.
                            'Rindow\\Web\\Mvc\\DefaultForceCsrfTokenValidationMiddleware' => (getenv('UNITTEST')? false : DefaultPipelinePriority::AFTER_ERRORHANDLER),
                            // **************************************************************************

                            'Rindow\\Web\\Mvc\\DefaultRouterMiddleware'     => DefaultPipelinePriority::ROUTER,
                            'Rindow\\Web\\Mvc\\DefaultDispatcherMiddleware' => DefaultPipelinePriority::DISPACHER,
                        ),
                    ),
                    'unittest' => getenv('UNITTEST'),
                ),
                'error_page_handler' => array(
                    // If you need throw exceptions when unit test;
                    'unittest' => getenv('UNITTEST'), // 1 or 0
                    'views' => array(
                        404 => 'error/404',
                        403 => 'error/403',
                        503 => 'error/503',
                        'default' => 'error/503',
                    ),
                    'error_policy' => array(
                        //'display_detail' => false,
                        //
                        // If you need to redirect:
                        //'redirect_url' => '/',
                    ),
                ),
                'dispatcher' => array(
                    'middlewares' => array(
                        'default' => array(
                            'view' => 'Rindow\\Web\\Mvc\\Middleware\\DefaultViewManagerMiddleware',
                            'restfull' => 'Rindow\\Web\\Mvc\\Middleware\\DefaultRestfullValidatorMiddleware',
                        ),
                    ),
                ),
                'view' => array(
                    'view_managers' => array(
                        'default' => array(
                            'view_manager' => 'Rindow\\Web\\Mvc\\DefaultViewManager',
                        ),
                        /*
                         * Inject your configuration for the view template manager
                         *
                        'namespace1' => array(
                            'layout' => 'layout_template_name',
                            'prefix' => 'template_name_prefix',
                            'template_paths' => array(
                                'template file path for namespace1',
                                'template file path for namespace1',
                            ),
                        ),
                        'namespace2' => array(
                            'template_paths' => array(
                                'prefix' => 'template_name_prefix',
                                'template file path for namespace2',
                                'template file path for namespace2',
                            ),
                            ....
                        ),
                        */
                    ),
                ),
            ),
        );
    }

    public function checkDependency($config)
    {
        $aliases = array(
            'Rindow\\Web\\Mvc\\DefaultServerRequest',
            'Rindow\\Web\\Mvc\\DefaultResponse',
            //'Rindow\\Web\\Mvc\\DefaultViewManager',
            'Rindow\\Web\\Mvc\\DefaultRouter',
        );
        foreach ($aliases as $alias) {
            if(!isset($config['container']['aliases'][$alias]))
                throw new \DomainException('module configuration must include the alias "'.$alias.'".');
        }
    }

    public function invoke($moduleManager)
    {
        $app = $moduleManager->getServiceLocator()->get('Rindow\Web\Mvc\DefaultApplication');
        return $app->run();
    }
}