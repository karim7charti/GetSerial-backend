<?php

namespace App\Http\Controllers;



use App\Models\Cart;
use App\Models\orders;
use App\Models\product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use Srmklive\PayPal\Facades\PayPal;


class PaymentCOntroller extends Controller
{
    private $paypalClient;
    public function __construct()
    {
        $this->paypalClient=PayPal::setProvider();
    }

    //
    public function create_payment2(Request $request){
        $someArray = json_decode($request->ids, true);
        if(count($someArray)%2==0){
            $price=product::query()->select(['discount','qte','name'])->where('id',"=",$someArray[0])->first();
            if($price!==null)
            {
               if($price['qte']<$someArray[1] || $someArray[1]<=0){
                   return response()->json([
                       "error"=>""
                   ],401);
               }
               else{
                   $order=['id_user'=>Auth::id(),'id_product'=>$someArray[0],'quantity'=>$someArray[1],'buyPrice'=>$price['discount'],'created_at'=>now()];
                   Cache::put("".Auth::id(),$order);
                   $this->paypalClient->setApiCredentials(config('paypal'));
                   $token =  $this->paypalClient->getAccessToken();
                   $this->paypalClient->setAccessToken($token);
                   $pr= $price['discount']*$someArray[1];
                   $items=[];
                   for($i=0;$i<1;$i++)
                       $items[$i]=array(
                           'name' => $price['name'],
                           'description' => 'serial keys',
                           'sku' => 'sku01',
                           'unit_amount' =>
                               array(
                                   'currency_code' => 'USD',
                                   'value' => round($someArray[1]*$price['discount'],2),
                               ),
                           'tax' =>
                               array(
                                   'currency_code' => 'USD',
                                   'value' => round($someArray[1]*$price['discount']*0.05,2),
                               ),
                           'quantity' => $someArray[1],
                           'category' => 'PHYSICAL_GOODS',
                       );
                   $order =  $this->paypalClient->createOrder([
                       "intent"=> "CAPTURE",
                       'purchase_units' =>
                           array(
                               0 =>
                                   array(
                                       'reference_id' => 'PUHF',
                                       'description' => 'Sporting Goods',
                                       'custom_id' => 'CUST-HighFashions',
                                       'soft_descriptor' => 'HighFashions',
                                       'amount' =>
                                           array(
                                               'currency_code' => 'USD',
                                               'value' => round($pr+$pr*0.05,2),
                                               'breakdown' =>
                                                   array(
                                                       'item_total' =>
                                                           array(
                                                               'currency_code' => 'USD',
                                                               'value' => round($pr,2),
                                                           ),
                                                       'shipping' =>
                                                           array(
                                                               'currency_code' => 'USD',
                                                               'value' => '0.00',
                                                           ),
                                                       'handling' =>
                                                           array(
                                                               'currency_code' => 'USD',
                                                               'value' => '0.00',
                                                           ),
                                                       'tax_total' =>
                                                           array(
                                                               'currency_code' => 'USD',
                                                               'value' => round($pr*0.05,2),
                                                           ),
                                                       'shipping_discount' =>
                                                           array(
                                                               'currency_code' => 'USD',
                                                               'value' => '0.00',
                                                           ),
                                                   ),
                                           ),
                                       'items' =>
                                           $items,
                                       'shipping' =>
                                           array(
                                               'method' => 'United States Postal Service',
                                               'address' =>
                                                   array(
                                                       'address_line_1' => '123 Townsend St',
                                                       'address_line_2' => 'Floor 6',
                                                       'admin_area_2' => 'San Francisco',
                                                       'admin_area_1' => 'CA',
                                                       'postal_code' => '94107',
                                                       'country_code' => 'US',
                                                   ),
                                           ),
                                   ),)




                   ]);


                   return response()->json($order);


               }
            }
            else
                return response()->json([
                    "error"=>"somthing went wrong"
                ],404);
        }
        else{
            return response()->json([
                "error"=>"somthing went wrong"
            ],404);
        }

    }
    public function execute_payment2(Request $request){
        $data = json_decode($request->getContent(), true);
        $orderId = $data['orderId'];
        $this->paypalClient->setApiCredentials(config('paypal'));
        $token = $this->paypalClient->getAccessToken();
        $this->paypalClient->setAccessToken($token);
        $result = $this->paypalClient->capturePaymentOrder($orderId);
        $order=Cache::get("".Auth::id());
        if($result['status'] === "COMPLETED"){
            if(orders::query()->insert($order))
            {
                $update=product::query()->find($order['id_product']);
                $update->qte-=$order['quantity'];
                $update->save();
                if($update->qte===0)
                    Cache::flush();
                return response()->json($result);
            }

        }
    }

    public function create_payment(Request $request){
        //$data = json_decode($request->getContent(), true);
        $someArray = json_decode($request->ids, true);

        if(count($someArray)%2==0)
        {
            $ar=[];
            $arQte=[];
            for($i=0,$j=0,$k=0;$i<count($someArray);$i++)
            {
                if($i%2==0){

                    $ar[$k]=$someArray[$i];
                    $k++;
                }
                else{
                    $arQte[$j]=$someArray[$i];
                    $j++;
                }


            }

            $selcetedProducts=DB::table('carts')->select('id_product')->where('id_user','=',Auth::id())
                ->whereIn('id',$ar)->get();
            Cache::put('ids'.Auth::id(),$ar);


            $arr1= json_decode(json_encode($selcetedProducts), true);
            $arr=[];
            for ($i=0;$i<count($selcetedProducts);$i++)
                $arr[$i]=$arr1[$i]['id_product'];
            $some=0;
            $items=[];
            $price=DB::table('products')->whereIn('id',$arr)->select('id','discount','name','qte')->get();
            $arr1= json_decode(json_encode($price), true);
            $order=[];
            for ($i=0;$i<count($arr1);$i++){
                $id=$arr[$i];

                $dicount=1;
                $id1=0;
                $qte=0;
                $name="";
                for ($j=0;$j<count($arr1);$j++){
                    if($id===$arr1[$j]['id']){
                        $dicount=$arr1[$j]['discount'];
                        $id1=$arr1[$j]['id'];
                        $qte=$arr1[$j]['qte'];
                        $name=$arr1[$j]['name'];
                        break;
                    }
                }
                if($qte<$arQte[$i] )
                    return response()->json([
                        "error"=>"there is only $qte of $name in the stock"
                    ],401);
                if($arQte[$i]<1)
                    return response()->json([
                        "error"=>"there is only $qte of $name in the stock"
                    ],401);

                $order[$i]=['id_user'=>Auth::id(),'id_product'=>$id1,'quantity'=>$arQte[$i],'buyPrice'=>$dicount,'created_at'=>now()];
                $some+=$arQte[$i]*$dicount;
                $items[$i]=array(
                    'name' => $name,
                    'description' => 'Sporting Goods',
                    'sku' => 'sku01',
                    'unit_amount' =>
                        array(
                            'currency_code' => 'USD',
                            'value' => round($arQte[$i]*$dicount,2),
                        ),
                    'tax' =>
                        array(
                            'currency_code' => 'USD',
                            'value' => round($arQte[$i]*$dicount*0.05,2),
                        ),
                    'quantity' => $arQte[$i],
                    'category' => 'PHYSICAL_GOODS',
                );
            }


            Cache::put("".Auth::id(),$order);

            $this->paypalClient->setApiCredentials(config('paypal'));
            $token =  $this->paypalClient->getAccessToken();
            $this->paypalClient->setAccessToken($token);
           //

            $order =  $this->paypalClient->createOrder([
                "intent"=> "CAPTURE",
                'purchase_units' =>
                    array(
                        0 =>
                            array(
                                'reference_id' => 'PUHF',
                                'description' => 'Sporting Goods',
                                'custom_id' => 'CUST-HighFashions',
                                'soft_descriptor' => 'HighFashions',
                                'amount' =>
                                    array(
                                        'currency_code' => 'USD',
                                        'value' => round($some+$some*0.05,2),
                                        'breakdown' =>
                                            array(
                                                'item_total' =>
                                                    array(
                                                        'currency_code' => 'USD',
                                                        'value' => round($some,2),
                                                    ),
                                                'shipping' =>
                                                    array(
                                                        'currency_code' => 'USD',
                                                        'value' => '0.00',
                                                    ),
                                                'handling' =>
                                                    array(
                                                        'currency_code' => 'USD',
                                                        'value' => '0.00',
                                                    ),
                                                'tax_total' =>
                                                    array(
                                                        'currency_code' => 'USD',
                                                        'value' => round($some*0.05,2),
                                                    ),
                                                'shipping_discount' =>
                                                    array(
                                                        'currency_code' => 'USD',
                                                        'value' => '0.00',
                                                    ),
                                            ),
                                    ),
                                'items' =>
                                    $items,
                                'shipping' =>
                                    array(
                                        'method' => 'United States Postal Service',
                                        'address' =>
                                            array(
                                                'address_line_1' => '123 Townsend St',
                                                'address_line_2' => 'Floor 6',
                                                'admin_area_2' => 'San Francisco',
                                                'admin_area_1' => 'CA',
                                                'postal_code' => '94107',
                                                'country_code' => 'US',
                                            ),
                                    ),
                            ),)




            ]);

            return response()->json($order);
        }
        else
            return response()->json([
                "error"=>"somthing went wrong"
            ],404);








    }

    public function test(){
        return Cache::get('ids'.Auth::id()) ;
    }
    public function execute_payment(Request $request){

        $data = json_decode($request->getContent(), true);
        $orderId = $data['orderId'];
        $this->paypalClient->setApiCredentials(config('paypal'));
        $token = $this->paypalClient->getAccessToken();
        $this->paypalClient->setAccessToken($token);
        $result = $this->paypalClient->capturePaymentOrder($orderId);
        $order=Cache::get("".Auth::id());
        $ids=Cache::get('ids'.Auth::id());
        if($result['status'] === "COMPLETED"){
            if(orders::query()->insert($order)){
                for($i=0;$i<count($order);$i++)
                {
                    $update=product::query()->find($order[$i]['id_product']);
                    $update->qte-=$order[$i]['quantity'];
                    if($update->qte===0)
                        Cache::flush();
                    $update->save();

                }
                Cart::destroy($ids);
                return response()->json($result);
            }


        }



    }
}
