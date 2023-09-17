<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\AuthService;
use \App\Http\Services\PlayerService;
use App\News;
use App\PlayerActiveHandle;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ApiNewsController extends Controller
{
    public function __construct()
    {
    }


    public function getNews(Request $request)
    {
        try
        {
            $news = News::limit(20)->get();
            return response()->json($news, 200);
        }
        catch (ValidationException $ex)
        {
            return response()->json(["message" => $ex->getMessage()], 400);
        }
        catch (Exception $ex)
        {
            return response()->json(["message" => "Something went wrong"], 500);
        }
    }
}
