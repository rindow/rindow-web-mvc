<?php
namespace Rindow\Web\Mvc;

interface DefaultPipelinePriority
{
    const BEFORE_BOOTSTRAP = 6000;
    const BOOTSTRAP        = 5500;
    const AFTER_BOOTSTRAP  = 5000;

    const BEFORE_ERRORHANDLER = 8000;
    const ERRORHANDLER        = 7500;
    const AFTER_ERRORHANDLER  = 7000;
    const BEFORE_ROUTER       = 6000;
    const ROUTER              = 5500;
    const AFTER_ROUTER        = 5000;
    const BEFORE_DISPACHER    = 4000;
    const DISPACHER           = 3500;
    const AFTER_DISPACHER     = 3000;

    const BEFORE_DEBUGGER = 6000;
    const DEBUGGER        = 5500;
    const AFTER_DEBUGGER  = 5000;
}