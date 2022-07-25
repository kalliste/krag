<?php

use Krag\Result;

/**
 * @param array<string, mixed> $vars
 */
function app_redirect(string $action = "index", $vars = []): Result
{
    $vars['action'] = $action;
    return (new Result())->redirect($action, $vars);
}
