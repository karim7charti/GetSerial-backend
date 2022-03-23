<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\wishList;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WishListController extends Controller
{
    //
    public function addToWishList(Request $request){
        $id=Auth::id();

        $cart=wishList::query()->where("id_user","=",$id)->where("id_product","=",$request->id_product)->first();
        if($cart===null){
            $cart=new wishList();

            $cart->id_user=$id;

            $cart->id_product=$request->id_product;

            if($cart->save()){
                return response()->json([
                    "status"=>200

                ]);
            }
        }
        return response()->json([
            "status"=>400

        ]);
    }
    public function getUsersWishList(){
        $id=Auth::id();

        $products=DB::table('wish_lists')->join('users','wish_lists.id_user','=','users.id')->join('products','wish_lists.id_product','=','products.id')->where('wish_lists.id_user','=',$id)->select(['wish_lists.id','wish_lists.created_at','products.id as product_id','products.discount','products.qte','products.price','products.name','products.img-url as url'])->get();
        return response()->json([
            "products"=>$products
        ]);
    }
    public function deleteItemFromWish($id)
    {

        $idUser=Auth::id();
        $res= DB::statement("delete from wish_lists where id=:id and id_user=:id_user",['id'=>$id,'id_user'=>$idUser]);

        if($res)
            return response()->json([
                "status"=>200
            ]);
    }
}
