<?php
namespace Rindow\Web\Mvc\ViewManager;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rindow\Web\Mvc\HttpMessageAttribute;
use Rindow\Web\Mvc\Exception;

class ViewManagerWrapper
{
    protected $serviceLocator;
    protected $config;

    public function setServiceLocator($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function getTemplatePaths($namespace)
    {
        $paths = array();
        if(isset($this->config['view_managers']['default']['template_paths']))
            $paths[] = $this->config['view_managers']['default']['template_paths'];
        if($namespace!=null && isset($this->config['view_managers'][$namespace]['template_paths'])) {
            $path = $this->config['view_managers'][$namespace]['template_paths'];
            if(!is_array($path))
                $path = array($path);
            $paths = array_merge($paths,$path);
        }
        return $paths;
    }

    public function getViewManager($namespace)
    {
        if(isset($this->config['view_managers'][$namespace]['view_manager']))
            $viewManagerName = $this->config['view_managers'][$namespace]['view_manager'];
        else
            $viewManagerName = $this->config['view_managers']['default']['view_manager'];
        if(!is_string($viewManagerName))
            throw new Exception\DomainException('the view manager name must be string for "'.$namespace.'" namepsace.');
        $viewManager = $this->serviceLocator->get($viewManagerName);
        $config = $this->config['view_managers']['default'];
        if(isset($this->config['view_managers'][$namespace]))
            $config = array_replace_recursive($config, $this->config['view_managers'][$namespace]);
        unset($config['template_paths']);
        $viewManager->setConfig($config);
        return $viewManager;
    }

    public function render(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $template, $variables=null, $templatePath=null)
    {
        if($variables!=null && !is_array($variables))
            throw new Exception\InvalidArgumentException('Variables must be array.');
            
        $route = $request->getAttribute(HttpMessageAttribute::ROUTING_INFORMATION);
        if(isset($route['namespace']))
            $namespace = $route['namespace'];
        else
            $namespace = 'default';
        $viewManager = $this->getViewManager($namespace);
        $viewManager->setCurrentTemplatePaths($this->getTemplatePaths($namespace));
        $body = $response->getBody();
        if($body) {
            $resource = $body->detach();
            $viewManager->setStream($resource);
            $body->attach($resource);
        }
        $output = $viewManager->render($template, $variables, $templatePath);
        if(is_resource($output)) {
            $resource = $response->getBody()->detach();
            stream_copy_to_stream($output, $resource);
            fclose($output);
            $response->getBody()->attach($resource);
        } else {
            $response->getBody()->write($output);
        }
        return $response;
    }
}