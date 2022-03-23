<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use App\Models\Cart;
use App\Models\orders;
use App\Models\User;
use App\Models\wishList;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    //

    public function login(Request $request){
        $validate=Validator::make($request->all(),[

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
        }else{
            $user =User::query()->where('email', $request->email)->first();
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
                return response()->json([
                    "status"=>200,
                    "name"=>$user->name,
                    "email"=>$user->email,
                    "token"=>$token,



                ]);
            }

        }

    }
    public function changeUsername(Request $request){
        $validate=Validator::make($request->all(),[
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

            $user=User::query()->find($id);
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
    public function changePassword(Request $request){
        $validate=Validator::make($request->all(),[
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

            $user=User::query()->find($id);
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
    public function changeMail(Request $request){
        $validate=Validator::make($request->all(),[
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

            $user=User::query()->find($id);
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
                    $user->email_verified_at=null;
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
    public function logout(Request $request){
        $user=$request->user();
        $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();

        return response()->json(
            [
                "status"=>200,

            ]
        );


    }


    public function verifyAcc(Request $request){
        $validate=Validator::make($request->all(),[

            'code'=>'required|numeric|min:10000|max:100000'

        ]);
        if($validate->fails())
        {
            return response()->json([
                "status"=>400,
                "errors"=>$validate->getMessageBag()

            ]);
        }
        else{
            $id=Auth::id();
            $code=User::query()->select('verifyCode')->where('id',"=",$id)->first();
            if($code['verifyCode']==$request->code)
            {
                $user=User::query()->find($id);
                $user->email_verified_at=now();
                if($user->save())
                    return response()->json([
                        "status"=>200,

                    ]);


            }

            else
                return response()->json([
                    "status"=>404,

                ]);

        }
    }

    public function register(Request $request){
        $validate=Validator::make($request->all(),[

            'email'=>'required|email|unique:users',
            'password'=>'required|min:8|confirmed',
            'username'=>'required|max:15'

        ]);
        if($validate->fails())
        {
            return response()->json([
                "status"=>400,
                "errors"=>$validate->getMessageBag()

            ]);
        }
        else
        {
            $verifyCode=random_int(10000,100000);
            $details=[
                'title'=>'Verification code',
                'body'=>"code :".$verifyCode
            ];
            Mail::to($request->email)->send(new TestMail($details));



            $user=new User();


            $user->name=$request->username;
            $user->email=$request->email;
            $user->verifyCode=$verifyCode;

            $user->password=bcrypt($request->password);


            if($user->save())
                $token=$user->createToken($request->email)->plainTextToken;
                return response()->json([
                    "status"=>200,
                    "name"=>$user->name,
                    "email"=>$user->email,
                    "token"=>$token


                ]);

        }
    }
    public static function getCountCart(){
        $id=Auth::id();

        $count=Cart::query()->where('id_user','=',$id)->count('id');
        return $count;
    }
    public static function getCountWish(){
        $id=Auth::id();

        $count=wishList::query()->where('id_user','=',$id)->count('id');
        return $count;
    }
    public static function getCountOrders(){
        $id=Auth::id();

        $count=orders::query()->where('id_user','=',$id)->where('see','=',1)->count('id');
        return $count;
    }
}
