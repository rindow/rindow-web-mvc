<?php
namespace Rindow\Web\Mvc\Sender;

use Psr\Http\Message\ResponseInterface;

class Sender
{
    public function send(ResponseInterface $response)
    {
        header(sprintf('HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }
        $body = $response->getBody();
        if($body==null)
            return;
        if(!$body->isReadable())
            return;
        if($body->isSeekable()) {
            $size = $body->getSize();
            if($size<=0)
                return;
            if(!$response->hasHeader('Content-Length')) {
                header('Content-Length: '.$size);
            }
            $body->rewind();
        }
        while(!$body->eof()) {
            $blob=$body->read(8192);
            echo $blob;
        }
    }
}