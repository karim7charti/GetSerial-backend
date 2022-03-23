<?php

namespace App\Http\Middleware;

use App\Mail\TestMail;
use App\Models\User;
use Closure;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class verifyEmail
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $id=Auth::id();
        $confirmed=User::query()->select(['email_verified_at','email'])->where('id',"=",$id)->first();
        if($confirmed['email_verified_at']===null)
        {

            $verifyCode=random_int(10000,100000);
            $details=[
                'title'=>'Verification code',
                'body'=>"code :".$verifyCode
            ];
            Mail::to($confirmed['email'])->send(new TestMail($details));
            $res=User::query()->where('id',"=",$id)->update(['verifyCode'=>$verifyCode]);
            return \response()->json([
                "status"=>"not verified"
            ],400);
        }

        return $next($request);
    }
}
