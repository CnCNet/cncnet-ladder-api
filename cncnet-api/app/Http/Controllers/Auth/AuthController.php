<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Http\Request;

class AuthController extends Controller
{

	/*
	|--------------------------------------------------------------------------
	| Registration & Login Controller
	|--------------------------------------------------------------------------
	|
	| This controller handles the registration of new users, as well as the
	| authentication of existing users. By default, this controller uses
	| a simple trait to add these behaviors. Why don't you explore it?
	|
	*/

	use AuthenticatesAndRegistersUsers;

	protected $redirectTo = '/account';

	public function __construct()
	{
		$this->middleware('guest', ['except' => 'getLogout']);
	}

	public function postRegister(Request $request)
	{
		$validator = $this->validator($request->all());

		if ($request->play_nay != null) return redirect()->back();

		if ($validator->fails())
		{
			$this->throwValidationException(
				$request,
				$validator
			);
		}

		$this->auth->login($this->create($request->all()));

		$this->auth->user()->sendNewVerification();
		return redirect()->back();
	}

	public function validator(array $data)
	{
		return Validator::make($data, [
			'name' => 'required|max:11|regex:/^[a-zA-Z0-9_\[\]\{\}\^\`\-\\x7c]+$/',
			'email' => 'required|email|max:255|unique:users',
			'password' => 'required|confirmed|min:6',
		]);
	}

	/**
	 * Create a new user instance after a valid registration.
	 *
	 * @param  array  $data
	 * @return User
	 */
	public function create(array $data)
	{
		return User::create([
			'name' => $data['name'],
			'email' => $data['email'],
			'password' => bcrypt($data['password']),
		]);
	}
}
