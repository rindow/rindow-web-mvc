<?php
namespace Rindow\Web\Mvc\Util;

use Psr\Http\Message\ResponseInterface;

class Redirector
{
    protected $urlGenerator;
    
    public function __construct($urlGenerator=null)
    {
        if($urlGenerator)
            $this->setUrlGenerator($urlGenerator);
    }

    public function setUrlGenerator($urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    protected function getStatus($options)
    {
        $status = 302;
        if(isset($options['permanent']) && $options['permanent'])
            $status = 301;
        if(isset($options['status']) && intval($options['status']))
            $status = intval($options['status']);
        return $status;
    }

    public function toRoute(ResponseInterface $response,$routeName,array $params=null,array $options=null)
    {
        $response = $response->withAddedHeader(
            'Location',$this->urlGenerator->fromRoute($routeName,$params,$options));
        return $response->withStatus($this->getStatus($options));
    }

    public function toPath(ResponseInterface $response,$path,array $options=null)
    {
        $response = $response->withAddedHeader(
            'Location',$this->urlGenerator->fromPath($path,$options));
        return $response->withStatus($this->getStatus($options));
    }

    public function toUrl(ResponseInterface $response,$url,array $options=null)
    {
        $response = $response->withAddedHeader('Location',$url);
        return $response->withStatus($this->getStatus($options));
    }
}
