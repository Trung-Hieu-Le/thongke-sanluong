<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function viewLogin(Request $request)
    {
        try {
            return view('users.login');
        } catch (\Exception $e) {
            return view('errors.404');
        }
    }

    public function actionLogin(Request $request)
    {
        $err = '';
        if (!empty($request->username) && !empty($request->password)) {
            $user = DB::table('tbl_user')
                ->where('user_account', '=', $request->username)
                ->where('user_password', '=', $request->password)
                ->get()->toArray();

            if (count($user) == 1) {
                if ($request->termsCheckbox) {
                    config(['session.lifetime' => 10080]);
                } else {
                    config(['session.lifetime' => config('session.lifetime')]);
                }
                $request->session()->put('userid', $user[0]->user_id);
                $request->session()->put('username', $user[0]->user_name);
                $request->session()->put('role', $user[0]->user_permission);
                return redirect('/');
            } else {
                $err = "Sai tài khoản hoặc mật khẩu";
                return view('users.login', compact('err'));
            }
        }
        else {
            $err = "Vui lòng nhập đầy đủ thông tin";
                return view('users.login', compact('err'));
        }
    }
    public function actionLogout(Request $request){
        $request->session()->flush();
        return redirect('/login');
    }
}