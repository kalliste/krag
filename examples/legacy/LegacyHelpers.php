<?php

use Krag\Result;

function app_redirect($action = "index", $vars = []): Result
{
    return (new Result($vars))->redirect($action);
}
