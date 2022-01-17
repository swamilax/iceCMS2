<?php
declare(strict_types=1);
/**
 * iceCMS2 v0.1a
 * Created by Sergey Peshalov https://github.com/desfpc
 * https://github.com/desfpc/iceCMS2
 *
 * Routing class
 */

namespace iceCMS2\Routing;

use iceCMS2\Settings\Settings;

class Routing
{
    /**
     * Parsed path information
     *
     * @var array
     */
    public array $pathInfo;

    /**
     * Route info
     *
     * @var array
     */
    public array $route = [
        'controller' => '404',
        'method' => 'main',
        'parts' => [],
    ];

    /**
     * Getting route info from $pathInfo and Settings
     *
     * @param Settings $settings
     */
    public function getRoute(Settings $settings): void
    {
        if (!empty($this->pathInfo['call_parts']) && !empty($settings->routes)) {
            // TODO cache $rouresTree Making RoutesTree
            $rouresTree = [];
            $i = -1;
            foreach ($settings->routes as $route => $value)
            {
                $routeParts = explode('/', $route);
                if (!empty($routeParts)) {
                    $routeReal = [];
                    $realPartsCnt = 0;
                    $realRouteKey = '';
                    foreach ($routeParts as $part) {
                        if (mb_substr($part, 0, 1, 'UTF-8') === '$') {
                            $routeReal[] = [
                                'partName' => str_replace('$', '', $part),
                                'type' => 'value',
                            ];
                        } else {
                            $routeReal[] = [
                                'partName' => $part,
                                'type' => 'route',
                            ];
                            ++$realPartsCnt;
                            if ($realRouteKey !== '') {
                                $realRouteKey .= '/';
                            }
                            $realRouteKey .= $part;
                        }
                    }
                }
                if ($realPartsCnt > 0) {
                    $rouresTree[] = [
                        'route' => $route,
                        'key' => $realRouteKey,
                        'value' => $value,
                        'parts' => $routeReal,
                    ];
                }
            }

            // Finding a Match between a Request Query String and a Route // TODO cache
            $lastRoute = null;
            foreach ($rouresTree as $route) {
                $this->route['method'] = 'main';
                $this->route['parts'] = [];
                $addedQueryVars = [];
                $i = -1;
                foreach ($this->pathInfo['call_parts'] as $call_part) {
                    ++$i;
                    if (!isset($route['parts'][$i])) {
                        if ($this->route['method'] === 'main') {
                            $this->route['method'] = $call_part;
                        } else {
                            $this->route['parts'][] = $call_part;
                        }
                        continue;
                    }
                    $part = $route['parts'][$i];

                    if($part['type'] === 'route') {
                        if (mb_strtolower($call_part, 'UTF-8') !== $part['partName']) {
                            continue(2);
                        }
                    } else {
                        $addedQueryVars[$part['partName']] = $call_part;
                    }
                }
                if (isset($route['parts'][$i+1])) {
                    continue;
                }

                $lastRoute = $route;
                if (is_array($route['value'])) {
                    $this->route['controller'] = $route['value']['controller'];
                    if (isset($route['value']['method'])) {
                        $this->route['method'] = $route['value']['method'];
                    }
                } else {
                    $this->route['controller'] = $route['value'];
                }
                $this->pathInfo['query_vars'] = array_merge($this->pathInfo['query_vars'], $addedQueryVars);
                break;
            }
        }
    }

    /**
     * URL parsing
     *
     * @return void
     */
    public function parseURL(): void
    {
        $path = [];

        if (!empty($_SERVER['REQUEST_URI'])) {
            $request_path = explode('?', $_SERVER['REQUEST_URI']);

            $path['base'] = rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/');
            $path['call_utf8'] = substr(urldecode($request_path[0]), strlen($path['base']) + 1);
            $path['call'] = utf8_decode($path['call_utf8']);
            if ($path['call'] == basename($_SERVER['PHP_SELF'])) {
                $path['call'] = '';
            }
            $path['call_parts'] = explode('/', $path['call_utf8']);
            if (!empty($path['call_parts'])) {
                if ($path['call_parts'][count($path['call_parts'])-1] === '') {
                    unset($path['call_parts'][count($path['call_parts'])-1]);
                }
            }

            if (isset($request_path[1])) {
                $path['query_utf8'] = urldecode($request_path[1]);
                $path['query'] = utf8_decode(urldecode($request_path[1]));
            } else {
                $path['query_utf8'] = '';
                $path['query'] = '';
            }
            $vars = explode('&', $path['query_utf8']);
            foreach ($vars as $var) {
                $t = explode('=', $var);
                if (isset($t[1])) {
                    $path['query_vars'][$t[0]] = $t[1];
                } else {
                    $path['query_vars'][$t[0]] = '';
                }

            }
        }
        $this->pathInfo = $path;
    }
}