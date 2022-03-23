<?php

namespace App\Http\Controllers;

use App\Mail\AskRefundMail;
use App\Mail\sendOrders;
use App\Mail\TestMail;
use App\Models\orders;
use App\Models\product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    //
    public function getOrdersCount(){
        $count=orders::query()->where('livred','=',0)->where('returned','=',0)->where('ask_refund','=',0)->count('id');
        $returned=orders::query()->where('returned','=',1)->count('id');
        $totalSales=DB::table('orders')->join('products', 'orders.id_product', '=', 'products.id')->selectRaw('round(sum(orders.buyPrice*orders.quantity),2) as totalSales')->where('orders.livred','=',1)->get();
        $profit=DB::table('orders')->join('products', 'orders.id_product', '=', 'products.id')
            ->selectRaw('round(sum(orders.buyPrice*orders.quantity)-sum(products.initialPrice*orders.quantity),2) as profit')
            ->where('orders.livred','=',1)->get();
        return response()->json([
            "status"=>200,
            "count"=>$count,
            "totalSales"=>$totalSales[0],
            "profit"=>$profit[0],
            "returned"=>$returned

        ]);
    }
    public function bestSelledProducts(){

        $product=DB::select(
            "
        SELECT orders.id_product,count(orders.id) as co,round(sum(orders.buyPrice*orders.quantity)-sum(products.initialPrice*orders.quantity),2) as profit
        from orders INNER JOIN products on orders.id_product=products.id
        WHERE orders.livred=1 GROUP BY orders.id_product ORDER BY profit DESC limit 6
        ");
        $array=json_decode(json_encode($product),true);
        $ids=[];
        for($i=0;$i<count($array);$i++){
            $ids[$i]=$array[$i]['id_product'];
        }

        $product= $product=product::query()->select(['id','name','img-url as url'])->whereIn('id',$ids)->get();
        $arr=[];
        for($i=0;$i<count($product);$i++)
        {
            $arr[$i]=[
                "url"=>$product[$i]['url'],
                "name"=>$product[$i]['name'],
                "profit"=>$array[$i]['profit'],
            ]   ;
        }

        return response()->json([
            "status"=>200,
            "products"=>$arr

        ]);

    }
    public function getOrdersDetails(){
        $results = DB::select( 'SELECT DATE_FORMAT(DATE(orders.created_at),"%d %b %Y") as date ,users.name,orders.livred,orders.buyPrice*orders.quantity as amount,orders.returned,orders.ask_refund from orders INNER JOIN users on orders.id_user=users.id  INNER JOIN products on orders.id_product=products.id order  by orders.created_at DESC limit 9 ');
        return response()->json([
            "status"=>200,
            "details"=>$results


        ]);
    }
    public function getAllOrdersDetails(){
        $results = DB::select( 'SELECT DATE_FORMAT(DATE(orders.created_at),"%d %b %Y") as date ,users.name,orders.livred,orders.buyPrice*orders.quantity as amount,orders.returned,orders.ask_refund from orders INNER JOIN users on orders.id_user=users.id  INNER JOIN products on orders.id_product=products.id order  by orders.created_at DESC');
        return response()->json([
            "status"=>200,
            "details"=>$results


        ]);
    }
    public function usersOrders(){
        $id=Auth::id();
        $results=DB::select('

SELECT orders.id as orderId,products.id,products.name,`img-url` as url,products.qte,orders.livred,orders.buyPrice as discount,orders.quantity,DATE_FORMAT(DATE(orders.created_at),"%d %b %Y") as date
FROM orders INNER JOIN products on orders.id_product=products.id
WHERE orders.id_user='.$id.' and orders.see=1 ORDER by orders.created_at DESC
        ');
        return response()->json([
            "status"=>200,
            "orders"=>$results


        ]);
    }
    public function hideOrder($id){
        $idUser=Auth::id();
       $res= DB::statement("update orders set see=0 where id=:id and id_user=:id_user",['id'=>$id,'id_user'=>$idUser]);

        if($res)
            return response()->json([
                "status"=>200,
            ]);



}
    public function askForRefund($id){
    $idUser=Auth::id();
    $res=DB::select('select livred from orders  where id=:id and id_user=:id_user',['id'=>$id,'id_user'=>$idUser]);
    $array=json_decode(json_encode($res),true);
    if($array[0]['livred']===1)
        return  response()->json([
            "status"=>400,
            "message"=>"this order is already shipped",
        ]);
    else
    {

        $res=DB::select('SELECT orders.id,orders.quantity*orders.buyPrice as refund_amount,users.email,users.name,orders.created_at from
orders INNER JOIN products on orders.id_product=products.id INNER JOIN users on orders.id_user=users.id WHERE orders.id=:id and users.id=:id_user
        ',['id'=>$id,'id_user'=>$idUser]);
        $array=json_decode(json_encode($res),true);
        $details=[
            'title'=>'refund details ',
            'body'=>$array
        ];
        Mail::to(env('MAIL_USERNAME'))->send(new AskRefundMail($details));
        $res=DB::statement('update orders set ask_refund=1 where id=:id and id_user=:id_user',['id'=>$id,'id_user'=>$idUser]);
       if($res)
           return  response()->json([
            "status"=>200,

        ]);

    }
}
public function adminOrders(){
    $results=DB::select('

SELECT orders.id,products.name as prod,users.name,users.email,`img-url` as url,orders.ask_refund,orders.buyPrice as discount,orders.quantity,DATE_FORMAT(DATE(orders.created_at),"%d %b %Y") as date
FROM orders INNER JOIN products on orders.id_product=products.id INNER JOIN users on orders.id_user=users.id
WHERE orders.livred=0 and orders.returned=0
 ORDER by orders.created_at DESC
        ');
    return response()->json([
        "status"=>200,
        "orders"=>$results


    ]);

}
public function confirmRefund($id){
    $stmt=DB::statement('update orders set returned=1 where id=:id',["id"=>$id]);
    if($stmt)
        return response()->json([
            "status"=>200

        ]);

}
public function sendOrder(Request $request)
{
    $str="";
    $length=strlen($request->serials);
    for($i=0;$i<$length;$i++)
    {
        if($i!==0 && $i!==$length-1)
            $str[$i]=$request->serials[$i];
    }
    $arr=explode(',',$str);
    $details=[
        'title'=>'order delivry ',
        'data'=>$request->all(),
        'serials'=>$arr
    ];
    Mail::to($request->email)->send(new sendOrders($details));
    $stmt=DB::statement('update orders set livred=1 where id=:id',['id'=>$details['data']['code']]);
    if($stmt)
        return response()->json([
            "status"=>200,

        ]);
}

    public function getDetailsOfYear(){

        $results = DB::select("SELECT count(id) as co,MONTH(created_at) as month from orders WHERE YEAR(created_at)=YEAR(NOW()) GROUP BY MONTH(created_at)");
        $results2 = DB::select("SELECT count(id) as co,MONTH(created_at) as month from orders WHERE YEAR(created_at)=YEAR(NOW()) and livred=1 GROUP BY MONTH(created_at)");
        $profitPerMonth=DB::select("

        SELECT round(sum(products.discount*orders.quantity)-sum(products.initialPrice*orders.quantity),2) as profit,MONTH(orders.created_at) as month from orders INNER JOIN products ON orders.id_product=products.id WHERE YEAR(orders.created_at)=YEAR(NOW()) and orders.livred=1 GROUP BY MONTH(orders.created_at)

        ");
        $array=json_decode(json_encode($results),true);
        $arr=[];
        $array2=json_decode(json_encode($results2),true);
        $arr1=[];
        $array3=json_decode(json_encode($profitPerMonth),true);
        $arr3=[];
        for($j=0;$j<count($array);$j++)
        {
            for($i=0;$i<11;$i++)
            {
                if($array[$j]['month']===$i+1)
                    $arr[$i]=$array[$j]['co'];
                else
                {
                    if(!isset($arr[$i]))
                        $arr[$i]=0;
                }


            }
        }
        for($j=0;$j<count($array2);$j++)
        {
            for($i=0;$i<11;$i++)
            {
                if($array2[$j]['month']===$i+1)
                    $arr1[$i]=$array2[$j]['co'];
                else
                {
                    if(!isset($arr1[$i]))
                        $arr1[$i]=0;
                }


            }
        }
        for($j=0;$j<count($array3);$j++)
        {
            for($i=0;$i<11;$i++)
            {
                if($array3[$j]['month']===$i+1)
                    $arr3[$i]=$array3[$j]['profit'];
                else
                {
                    if(!isset($arr3[$i]))
                        $arr3[$i]=0;
                }


            }
        }


        return response()->json([
            "status"=>200,
            "countOrdersPerMonth"=>$arr,
            "countSalesPerMonth"=>$arr1,
            "profit"=>$arr3


        ]);

    }
}
