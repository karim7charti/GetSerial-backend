<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use App\Models\admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Validator;

class AdminController extends Controller
{
    //
    public function getAdmin()
    {
        return response()->json(
            [
                "status"=>200,
                "first_name"=>auth()->guard('admins')->user()->name,

                "email"=>auth()->guard('admins')->user()->email,

            ]);
    }
    public function changeUsernameAdmin(Request $request)
    {
        $validate= \Illuminate\Support\Facades\Validator::make($request->all(),[
            'username'=>'required|max:15'
        ]);
        if($validate->fails())
        {
            return response()->json(
                [
                    "errors"=>$validate->getMessageBag(),
                    "status"=>400
                ]
            );
        }
        else{
            $id=Auth::id();

            $user=admin::query()->find($id);
            $user->name=$request->username;
            if($user->save())
                return response()->json(
                    [
                        "status"=>200,
                        "name"=>$user->name

                    ]
                );

        }
    }
    public function changeMailAdmin(Request $request)
    {
        $validate= \Illuminate\Support\Facades\Validator::make($request->all(),[
            'email'=>'required|email',
            'password'=>'required|min:8'
        ]);
        if($validate->fails())
        {
            return response()->json(
                [
                    "errors"=>$validate->getMessageBag(),
                    "status"=>400
                ]
            );
        }
        else{
            $id=Auth::id();

            $user=admin::query()->find($id);
            if(!Hash::check($request->password,$user->password))
            {
                return response()->json(
                    [
                        "status"=>404,

                    ]
                );
            }
            else{
                if($request->email!==$user->email)
                {
                    $user->email=$request->email;
                    if($user->save())
                        return response()->json(
                            [
                                "status"=>200,
                                "email"=>$request->email

                            ]
                        );
                }
                else
                    return response()->json(
                        [
                            "status"=>200,
                            "email"=>$request->email

                        ]
                    );


            }


        }
    }
    public function sendCodeVer(Request $request)
    {
        $validate= \Illuminate\Support\Facades\Validator::make($request->all(),[
            'email1'=>'required|email',
        ]);
        if($validate->fails())
        {
            return response()->json(
                [
                    "errors"=>$validate->getMessageBag(),
                    "status"=>400
                ]
            );
        }else{
            $user =admin::query()->where('email', $request->email1)->first();
            if(!$user)
            {
                return response()->json(
                    [

                        "status"=>401
                    ]
                );
            }
            else
            {
                $verifyCode=random_int(100000,1000000);
                $details=[
                    'title'=>'Verification code',
                    'body'=>"code :".$verifyCode
                ];
                Mail::to($request->email1)->send(new TestMail($details));
                $user->number=$verifyCode;
               if($user->save());
                return response()->json(
                    [

                        "status"=>200
                    ]
                );
            }



        }
    }
    public function resetPass(Request $request){
        $validate=\Illuminate\Support\Facades\Validator::make($request->all(),[
            'email1'=>'required|email',
            'code'=>'required',
            'password'=>'required|min:8|confirmed']);
             if($validate->fails())
             {
                 return response()->json(
                     [
                         "errors"=>$validate->getMessageBag(),
                         "status"=>400
                     ]
                 );
             }else{
                 $user =admin::query()->where('email', $request->email1)->first();
                 if(!$user)
                 {
                     return response()->json(
                         [
                             "status"=>401
                         ]
                     );
                 }
                 else{
                     $code=$user->number;
                     if($code===$request->code)
                     {
                         $user->password=bcrypt($request->password);
                         $user->tokens()->where('tokenable_id', $user->id)->delete();
                         $token=$user->createToken($request->email1)->plainTextToken;
                         if($user->save())
                             return response()->json(
                                 [
                                     "status"=>200,
                                     "token"=>$token
                                 ]
                             );
                     }
                     else
                     {
                         return response()->json(
                             [
                                 "status"=>404
                             ]
                         );
                     }
                 }

             }


    }
    public function changePasswordAdmin(Request $request){
        $validate= \Illuminate\Support\Facades\Validator::make($request->all(),[
            'OldPass'=>'required|min:8',
            'password'=>'required|min:8|confirmed',

        ]);
        if($validate->fails())
        {
            return response()->json(
                [
                    "errors"=>$validate->getMessageBag(),
                    "status"=>400
                ]
            );
        }
        else{
            $id=Auth::id();

            $user=admin::query()->find($id);
            if(!Hash::check($request->OldPass,$user->password))
            {
                return response()->json(
                    [
                        "status"=>404,

                    ]
                );
            }
            else{
                $user->password=bcrypt($request->password);
                if($user->save())
                    return response()->json(
                        [
                            "status"=>200,
                        ]
                    );

            }
        }
    }
    public function login(Request $request)
    {
        $validate=\Illuminate\Support\Facades\Validator::make($request->all(),[
            'email'=>'required|email',
            'password'=>'required'

        ]);
        if($validate->fails())
        {
            return response()->json(
                [
                    "errors"=>$validate->getMessageBag(),
                    "status"=>400
                ]
            );
        }else{
            $user =admin::query()->where('email', $request->email)->first();
            if(!$user || !Hash::check($request->password,$user->password)){
                return response()->json(
                    [
                        "message"=>"ivalide email or password",
                        "status"=>401
                    ]
                );
            }
            else{
                $token=$user->createToken($request->email)->plainTextToken;
                return response()->json(
                    [
                        "first_name"=>$user->first_name,
                        "last_name"=>$user->last_name,
                        "token"=>$token,
                        "status"=>200
                    ]
                );
            }

        }



    }
    public function logout(Request $request){
        $user=$request->user();
        $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();

        return response()->json(
            [
               "status"=>200,
                "message"=>"loged out succefuly"
            ]
        );


    }
}
