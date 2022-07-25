<?php

use Krag\Result;

/**
 * @param array<string, mixed> $vars
 */
function app_redirect(string $action = "index", $vars = []): Result
{
    return (new Result($vars))->redirect($action);
}
