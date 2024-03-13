<?php

namespace App\Http\Controllers;

use App\Models\News;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
