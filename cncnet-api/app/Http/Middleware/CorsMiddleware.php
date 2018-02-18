<?php namespace App\Http\Middleware;

use Closure;

class CorsMiddleware 
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
    {
        $response = $next($request);
        $IlluminateResponse = 'Illuminate\Http\Response';
        $SymfonyResopnse = 'Symfony\Component\HttpFoundation\Response';
        $headers = [
            'Access-Control-Allow-Origin' => 'https://forums.cncnet.org',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization, X-Requested-With, Application, Client-ID'
		];
		
        if($response instanceof $IlluminateResponse)
        {
            foreach ($headers as $key => $value) 
            {
                $response->header($key, $value);
            }
            return $response;
        }

        if($response instanceof $SymfonyResopnse)
        {
            foreach ($headers as $key => $value)
            {
                $response->headers->set($key, $value);
            } 
            return $response;
        }

        return $response;
    }
}
