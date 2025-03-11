<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;
use Illuminate\Http\Request;

class Controller extends BaseController
{
	use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

	public function validateUsername(Request $request)
	{
		$this->validate($request, [
			'username' => 'required|string|min:3|regex:/^[a-zA-Z0-9_\[\]\{\}\^\`\-\\x7c]+$/|max:11', //\x7c = | aka pipe,
			'ladderAbbrev' => 'required|string'
		]);
	}
}
