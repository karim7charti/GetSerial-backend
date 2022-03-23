<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\orders;
use App\Models\wishList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    //
    public function addToCart(Request $request){
        $id=Auth::id();

        $cart=Cart::query()->where("id_user","=",$id)->where("id_product","=",$request->id_product)->first();
        if($cart===null){
            $cart=new Cart();

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

    public function insertToCart(Request $request){
        $id=Auth::id();
        $ids=json_decode($request->ids,true);
        $ids2=$ids;

        $cart=Cart::query()->select('id_product')->where("id_user","=",$id)->whereIn("id_product",$ids)->get();
        for($i=0;$i<count($cart);$i++){
            $in=array_search($cart[$i]['id_product'],$ids);
            array_splice($ids, $in, 1);
        }
        $order=[];
        for($i=0;$i<count($ids);$i++)
            $order[$i]=['id_user'=>$id,'id_product'=>$ids[$i],'created_at'=>now(),'updated_at'=>now(),'quantity'=>1];
        if(Cart::query()->insert($order))
        {
            wishList::query()->where("id_user","=",$id)->whereIn("id_product",$ids2)->delete();
            return response()->json([
                "status"=>200,
                "test"=>"inserted"
            ]);
        }





        //



    }

    public function getUsersCart(){

        $id=Auth::id();

        $products=DB::table('carts')->join('users','carts.id_user','=','users.id')->join('products','carts.id_product','=','products.id')->where('carts.id_user','=',$id)->select(['carts.id','carts.quantity','products.id as product_id','products.discount','products.qte','products.price','products.name','products.img-url as url'])->get();
        return response()->json([
            "products"=>$products
        ]);

    }
    public function deleteItem($id){

        $idUser=Auth::id();
        $res= DB::statement("delete from carts where id=:id and id_user=:id_user",['id'=>$id,'id_user'=>$idUser]);

        if($res)
            return response()->json([
                "status"=>200
            ]);
    }
}
