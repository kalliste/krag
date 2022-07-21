<?php

namespace Krag;

class Routing implements RoutingInterface
{

    public function methodForRequest(RequestInfo $request) : ?callable
    {
        $path = parse_url($request->url)['path'];
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
        $up = count(explode('/', trim($fromCurrent, '/'))) - 1;
        $ret = ($up > 0) ? str_repeat('../', $up) : '/';
        $ret .= $className.'/'.$methodName;
        $ret .= (count($data)) ? '?'.http_build_query($data) : '';
        return $ret;
    }

}

?>
