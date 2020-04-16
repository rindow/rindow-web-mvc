<?php
namespace Rindow\Web\Mvc\ErrorPageHandler;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;

class ErrorPageHandler
{
    protected static $errorLevel = array(
        E_ERROR           => 'E_ERROR',
        E_WARNING         => 'E_WARNING',
        E_PARSE           => 'E_PARSE',
        E_NOTICE          => 'E_NOTICE',
        E_CORE_ERROR      => 'E_CORE_ERROR',
        E_CORE_WARNING    => 'E_CORE_WARNING',
        E_COMPILE_ERROR   => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR      => 'E_USER_ERROR',
        E_USER_WARNING    => 'E_USER_WARNING',
        E_USER_NOTICE     => 'E_USER_NOTICE',
        E_STRICT          => 'E_STRICT ',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED      => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        E_ALL             => 'E_ALL',
    );
    protected $serviceLocator;
    protected $viewManagerName;
    protected $config;
    protected $dataTables = array();
    protected $status;
    protected $debug;
    protected $logger;

    public function setServiceLocator($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function setViewManagerName($viewManagerName)
    {
        $this->viewManagerName = $viewManagerName;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function addDataTable($label, array $data)
    {
        $this->dataTables[] = array('label'=>$label,'data'=>$data);
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function handleError($level, $message, $file = null, $line = null)
    {
        if(!(error_reporting() & $level))
            return false;
        if(isset(self::$errorLevel[$level]))
            $levelText = self::$errorLevel[$level];
        else
            $levelText = 'Unknown';
        $this->addDataTable('Error reporting',array('Level'=>$levelText));
        throw new \ErrorException($message,0,$level,$file,$line);
    }

    public function handleException(ServerRequestInterface $request, ResponseInterface $response, $e)
    {
        $type = $this->determineContentType($request);
        if(($type=='text/html' || $type=='text/plain') &&
            isset($this->config['error_policy']['redirect_url'])) {
            return $this->redirect($request, $response,$this->config['error_policy']['redirect_url']);
        }

        switch ($type) {
            case 'text/html':
                $response = $this->renderView($request, $response, $e);
                break;

            case 'text/plain':
                $response = $this->renderText($request, $response, $e);
                break;

            case 'application/json':
                $response = $this->renderJson($request, $response, $e);
                break;

            case 'application/xml':
                $response = $this->renderXml($request, $response, $e);
                break;

            default:
                throw new Exception\DomainException('invalid handler type:'.$type);
        }
        if($this->status)
            $response = $response->withStatus($this->status);
        $response = $this->postProcess($request,$response,$e);
        $this->loggingException($e);
        return $response;
    }

    public function postProcess($request,$response,$e)
    {
        if(!$this->debug)
            return $response;
        $origin = $request->getHeader('origin');
        $method = $request->getMethod();
        if(empty($origin) || strtoupper($method)=='OPTIONS')
            return $response;
        $response = $response->withHeader('Access-Control-Allow-Origin',$origin);
        $response = $response->withHeader('Access-Control-Allow-Methods',$method);
        $response = $response->withHeader('Access-Control-Allow-Headers','Content-Type, Authorization');
        return $response;
    }

    public function isDisplayDetail()
    {
        return isset($this->config['error_policy']['display_detail']) && $this->config['error_policy']['display_detail'];
    }

    protected function determineContentType($request)
    {
        $accept = $request->getHeaderLine('Accept');
        if(!$accept) {
            $type = 'text/html';
            return $type;
        }
        $types = array(
            'text/html','text/plain',
            'application/json','application/xml');
        $firstPos = 100000;
        $type = 'text/plain';
        foreach ($types as $select) {
            $pos = strpos($accept, $select);
            if($pos===false)
                continue;
            if($firstPos>$pos) {
                $firstPos = $pos;
                $type = $select;
            }
        }
        if($firstPos==100000 && strpos($accept,'*/*')!==false)
            $type = 'text/html';
        return $type;
    }

    protected function redirect($request, $response,$url)
    {
        $response = $response->withHeader('Location',$url);
        $response = $response->withStatus(302);
        return $response;
    }

    protected function renderView($request, $response, $e)
    {
        if(!$this->viewManagerName)
            return $this->renderText($request, $response, $e);
        try {
            $viewManger = $this->serviceLocator->get($this->viewManagerName);
            if(isset($this->config['views']))
                $templates = $this->config['views'];
            else
                $templates = $this->config['default'];
            if($this->status) {
                if(isset($templates[$this->status]))
                    $template = $templates[$this->status];
                else
                    $template = $templates['default'];
            } else {
                $template = $templates['default'];
            }
            $variables = array(
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $this->getTraceString($e),
                'dataTables' => $this->convertDataTables($this->dataTables),
                'policy' => $this->config['error_policy'],
            );
            $response = $viewManger->render($request, $response,$template,$variables);
            $response = $response->withHeader('content-type','text/html');
            return $response;
        } catch(\Exception $eTmp) {
            $this->addDataTable('Error at the ErrorPageHandler',array(
                'Exception'=>get_class($eTmp),
                'Exception Message'=>$eTmp->getMessage(),
                'Exception Code'=>$eTmp->getCode(),
                'File'=>$eTmp->getFile(),
                'Line'=>$eTmp->getLine(),
            ));
            return $this->renderText($request, $response, $e);
        }
    }

    protected function convertDataTables($dataTables)
    {
        $printableTables = array();
        foreach ($dataTables as $dataTable) {
            $printable = array();
            $printable['label'] = $dataTable['label'];
            $printable['data'] = array();
            foreach ($dataTable['data'] as $name => $value) {
                $printable['data'][$name] = str_replace(array("\r","\n"), array("",""), var_export($value,true));
            }
            $printableTables[] = $printable;
        }
        return $printableTables;
    }

    protected function getTraceString($e)
    {
        $ep = $e;
        $trace = '';
        while($ep) {
            if(!empty($trace)) {
                $trace .="\n";
                $trace .= 'Exception: '.get_class($ep)."\n";
                $trace .= 'Code: '.$ep->getCode()."\n";
                $trace .= 'Message: '.$ep->getMessage()."\n";
                $trace .= 'Source: '.$ep->getFile().'('.$ep->getLine().')'."\n";
                $trace .="\n";
            }
            $trace .= $ep->getTraceAsString()."\n";
            $ep = $ep->getPrevious();
        }
        return $trace;
    }

    public function renderText($request, $response, $e)
    {
        ob_start();
        echo 'exception:'.get_class($e)."\n";
        echo 'message:'.$e->getMessage()."\n";
        echo 'code:'.$e->getCode()."\n";
        if($this->isDisplayDetail()) {
            foreach ($this->dataTables as $dataTable) {
                echo '['.$dataTable['label']."]\n";
                foreach ($dataTable['data'] as $name => $value) {
                    echo $name.': ';
                    echo str_replace(array("\r","\n"), array("",""), var_export($value,true))."\n";
                }
                echo "\n";
            }
            echo 'file:'.$e->getFile()."\n";
            echo 'line:'.$e->getLine()."\n";
            echo 'trace:'.$this->getTraceString($e);
        }
        $output = ob_get_clean();
        $response->getBody()->write($output);
        $response = $response->withHeader('Content-Type','text/plain');
        return $response;
    }

    public function renderJson($request, $response, $e)
    {
        if(!$this->isDisplayDetail()) {
            $json = array('error' => array(
                'message'=>'error'
            ));
        } else {
            $json = array('error' => array(
                'exception'=>get_class($e),
                'message'=>$e->getMessage(),
                'code'=>$e->getCode(),
                'file'=>$e->getFile(),
                'line'=>$e->getLine(),
                'trace'=>$this->getTraceString($e)
            ));
        }

        $output = json_encode($json,defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0);
        $response->getBody()->write($output);
        $response = $response->withHeader('Content-Type','application/json');
        return $response;
    }

    public function renderXml($request, $response, $e)
    {
        $error = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><error/>");
        if(!$this->isDisplayDetail()) {
            $error->addChild('message','error');
        } else {
            $error->addChild('exception',get_class($e));
            $error->addChild('message',$e->getMessage());
            $error->addChild('code',$e->getCode());
            $error->addChild('file',$e->getFile());
            $error->addChild('line',$e->getLine());
            $error->addChild('trace',$this->getTraceString($e));
        }
        $output = $error->asXml();
        $response->getBody()->write($output);
        $response = $response->withHeader('Content-Type','application/xml');
        return $response;
    }

    protected function loggingException($e)
    {
        if($this->debug && $this->logger) {
            $this->logger->debug('errorHandler',array(
                'exception'=>get_class($e),
                'message'=>$e->getMessage(),
                'code'=>$e->getCode(),
                'file'=>$e->getFile(),
                'line'=>$e->getLine(),
                'trace'=>$this->getTraceString($e),
            ));
        }
    }
}
