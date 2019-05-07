<?php
namespace Rindow\Web\Mvc\Pipeline;

class Terminator
{
    public function __invoke($request,$response)
    {
        return $response;
    }
}