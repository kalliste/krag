<?php

namespace Krag;

class Routing implements RoutingInterface
{

    public function __construct() {}

    public function methodForRequest(Request $request, array $controllers = []) : ?callable
    {
        $path = parse_url($request->uri)['path'];
        $urlParts = explode('/', $path);
        if ($path == '/')
        {
            return 'index';
        }
        if (count($urlParts) >= 3)
        {
            $controllerName = $urlParts[1];
            $methodName = $urlParts[2];
            return [$controllerName, $methodName];
        }
        return null;
    }

    public function makeLink(string $className, string $methodName, string $fromCurrent = '/', array $data = []) : string
    {
        $source = explode('/', trim($fromCurrent, '/'));
        $target = [$className, $methodName];
        while (count($source) && count($target) && $source[0] == $target[0])
        {
            $source = array_slice($source, 1);
            $target = array_slice($target, 1);
        }
        $ret = (count($source)) ? str_repeat('../', count($source)) : '';
        $ret .= implode('/', $target).'/';
        $ret .= (count($data)) ? '?'.http_build_query($data) : '';
        return $ret;
    }

}

?>
