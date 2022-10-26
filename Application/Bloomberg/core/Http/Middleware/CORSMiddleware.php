<?php
/**
 * Created by PhpStorm.
 * User: davi
 * Date: 2/21/17
 * Time: 12:22 AM
 */

namespace Application\Bloomberg\Http\Middleware;

use Application\Bloomberg\Exceptions\InvalidArgumentException;
use Closure;
use Illuminate\Http\Response;
use Laravel\Lumen\Http\Request;

class CORSMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // TODO: Should check whether route has been registered
        if ($this->isPreflightRequest($request)) {
            $response = $this->createEmptyResponse();
        } else {
            $response = $next($request);
        }

        return $this->addCorsHeaders($request, $response);
    }

    /**
     * Determine if request is a preflight request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function isPreflightRequest($request)
    {
        return $request->isMethod('OPTIONS');
    }

    /**
     * Create empty response for preflight request.
     *
     * @return \Illuminate\Http\Response
     */
    protected function createEmptyResponse()
    {
        return new Response(null, 204);
    }

    /**
     * @param $request
     * @param $response
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function addCorsHeaders(Request  $request, $response)
    {
        if (env('APP_ENV') !== 'production') {
            foreach ([
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'no-cache',
                'Access-Control-Max-Age' => (60 * 60 * 24),
                'Access-Control-Allow-Headers' => $request->header('Access-Control-Request-Headers'),
                'Access-Control-Allow-Methods' => $request->header('Access-Control-Request-Methods')
                    ?: 'GET, HEAD, POST, PUT, PATCH, DELETE, OPTIONS',
                'Access-Control-Allow-Credentials' => 'true',
            ] as $header => $value) {
                $response->header($header, $value);
            }
            return $response;
        }
        if (isset($_GET['viewSystemLogs'])) {
            foreach ([
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'no-cache',
                'Access-Control-Max-Age' => (60 * 60 * 24),
                'Access-Control-Allow-Headers' => $request->header('Access-Control-Request-Headers'),
                'Access-Control-Allow-Methods' => $request->header('Access-Control-Request-Methods')
                    ?: 'GET, HEAD, POST, PUT, PATCH, DELETE, OPTIONS',
                'Access-Control-Allow-Credentials' => 'true',
            ] as $header => $value) {
                $response->header($header, $value);
            }
            return $response;
        }

        $allowedOrigin = ($request->headers->get('origin') && (in_array($request->headers->get('origin'), config('cors')['allowedOrigins'])) || $request->headers->get('origin') == config('cors')['allowedBackendOrigin']) ? $request->headers->get('origin') : null;

        $maxAge = 'no-cache';
        $allowedMethods = 'GET, OPTIONS';

        if ($request->headers->get('fePlatform') == 'FrontEnd' || $request->headers->get('fePlatform') == 'mobApp06Zy4yQG7T') {
            $maxAge = 'private, max-age=30';
        }


        if ($allowedOrigin == config('cors')['allowedBackendOrigin']) {
            $allowedMethods = 'GET, HEAD, POST, PUT, PATCH, DELETE, OPTIONS';
        }


        $headers = [
            'Cache-Control' => $maxAge,
            'Access-Control-Max-Age' => (60 * 60 * 24),
            'Access-Control-Allow-Headers' => $request->header('Access-Control-Request-Headers'),
            'Access-Control-Allow-Methods' => $allowedMethods,
            'Access-Control-Allow-Credentials' => 'true',
        ];
        if (! empty($allowedOrigin )) {
            $headers['Access-Control-Allow-Origin'] = $allowedOrigin;
        } else {
            if ($request->headers->get('fePlatform') == 'mobApp06Zy4yQG7T' || $request->headers->get('fePlatform') == 'FrontEnd') {
                $headers['Access-Control-Allow-Origin'] = '*';
            } else  {
                $headers['Access-Control-Allow-Origin'] = config('cors')['allowedOrigins'][0];
            }
        }

        foreach ($headers as $header => $value) {
            $response->header($header, $value);
        }

        return $response;
    }
}
