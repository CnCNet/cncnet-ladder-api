<?php namespace App\Http\Controllers;

class ApiAuthController extends Controller 
{
    public function __construct()
    {
        $this->middleware('auth.basic');
    }
    
    public function getAuth()
    {
        $user = \Auth::user();
        return $user->usernames;
    }

    public function putAuth()
    {
        return "TODO - Put Auth";
    }
}
