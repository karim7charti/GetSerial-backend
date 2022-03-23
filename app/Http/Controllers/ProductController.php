<?php

namespace App\Http\Controllers;

use App\Models\categories;
use App\Models\orders;
use App\Models\product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    //



    public function getCategories()
    {
       if(Cache::has('categ'))
            $categoreis=Cache::get('categ');
        else {
            $categoreis = categories::query()->orderBy('name')->select(['id','name'])->limit(3)->get();
            Cache::put('categ',$categoreis);
        }
        return response()->json([

            "status"=>200,
            "categories"=>$categoreis

        ]);
    }
    public function getProduct($id){
        $categoreis=categories::query()->orderBy('name')->get('*');

        $product=product::query()->find($id);
        if($product)
        {
            return response()->json([
                "status"=>200,
                "product"=>$product,
                "categories"=>$categoreis

            ]);
        }
        else{
            return response()->json([
                "status"=>404,
                "message"=>"product not found"

            ]);
        }


    }

    public function getProducts(){
       if(Cache::has('products')) {
            $products = Cache::get('products');
        }
        else {

            $products = DB::table('products')->join('categories', 'products.id_cat', '=', 'categories.id')->select(


                'products.id', 'products.name', 'products.price', 'products.discount', 'products.img-url as url', 'products.description', 'products.initialPrice','products.qte', 'products.id_cat', 'products.created_at', 'categories.name as categ_name'

            )->orderBy('products.created_at', 'DESC')->get();
            Cache::put('products',$products);


       }
        return response()->json([
            "status"=>200,
            "products"=>$products,
        ]);


    }
    public function editProduct(Request $request,$id){
        if(strpos($request->input('img'),'products/toPath'))
        {
            $validate=\Illuminate\Support\Facades\Validator::make($request->all(),[
                'product_name'=>'required|max:50',
                'start_price'=>'required|numeric|min:1|max:1000',
                'discount_price'=>'required|numeric|min:1|max:1000',
                'initialPrice'=>'required|numeric|min:1|max:1000',
                'about_key'=>'required|max:255',
                'categ'=>'required|max:50',
                'qte'=>'required|numeric|min:1'



            ]);
            if($validate->fails())
            {
                return response()->json([
                    "status"=>422 ,
                    "errors"=>$validate->getMessageBag(),

                ]);
            }
            else{



                $product=product::query()->find($id);
                $idCateg=(categories::query()->where('name',"=",$request->categ)->select('id')->get())[0]->id;



                $product->name=$request->product_name;
                $product->price=$request->start_price;
                $product->discount=$request->discount_price;
                $product->description=$request->about_key;
                $product->id_cat=$idCateg;
                $product->qte=$request->qte;
                $product->initialPrice=$request->initialPrice;
                $product->updated_at=now();


                $produ=[

                        "id"=>$product->id,
                        "name"=>$product->name,
                        "price"=>$product->price,
                        "discount"=>$product->discount,
                        "url"=>$product->getAttribute('img-url'),
                        "description"=>$product->description,
                        "id_cat"=>$product->id_cat,
                        "categ_name"=>$request->categ,
                        "qte"=>$product->qte,
                        "created_at"=>$product->created_at,
                    "initialPrice"=>$product->initialPrice



                ];



                if($product->save()){
                    Cache::flush();
                    return response()->json([
                        "status"=>200,
                        "product"=>$produ
                    ]);
                }


            }
        }

        else
        {
            $validate=\Illuminate\Support\Facades\Validator::make($request->all(),[
                'product_name'=>'required|max:50',
                'start_price'=>'required|numeric|min:1|max:1000',
                'discount_price'=>'required|numeric|min:1|max:1000',
                'about_key'=>'required|max:255',
                'categ'=>'required|max:50',
                'initialPrice'=>'required|numeric|min:1|max:1000',
                'img'=>'required|mimes:png,jpg,jpeg',
                'qte'=>'required|numeric|min:1'


            ]);
            if($validate->fails())
            {
                return response()->json([
                    "status"=>422 ,
                    "errors"=>$validate->getMessageBag(),

                ]);
            }
            else{
                $product=product::query()->find($id);
                $idCateg=(categories::query()->where('name',"=",$request->categ)->select('id')->get())[0]->id;
                File::delete('products/'.$product->getAttribute('img-url'));
                $product->delete();


                $product->name=$request->product_name;
                $product->price=$request->start_price;
                $product->discount=$request->discount_price;
                $product->description=$request->about_key;
                $product->id_cat=$idCateg;
                $product->qte=$request->qte;
                $product->initialPrice=$request->initialPrice;
                $product->updated_at=now();

                $product->setAttribute('img-url',$request->file('img')->store('toPath', ['disk' => 'my_files']));


                $produ=[

                    "id"=>$product->id,
                    "name"=>$product->name,
                    "price"=>$product->price,
                    "discount"=>$product->discount,
                    "url"=>$product->getAttribute('img-url'),
                    "description"=>$product->description,
                    "id_cat"=>$product->id_cat,
                    "categ_name"=>$request->categ,
                    "qte"=>$product->qte,
                    "created_at"=>$product->created_at,
                    "initialPrice"=>$product->initialPrice



                ];

                if($product->save()){
                    Cache::flush();
                    return response()->json([
                        "status"=>200,
                        "product"=>$produ
                    ]);
                }

            }
        }


    }



    function createCat($name){
        $cat=new categories();

        $cat->name=$name;
        $cat->save();
        Cache::pull('categ');

        return $cat->id;


    }

    public function storeProduct(Request $request)
    {


        $validate=\Illuminate\Support\Facades\Validator::make($request->all(),[
            'product_name'=>'required|max:50',
            'start_price'=>'required|numeric|min:1|max:1000',
            'discount_price'=>'required|numeric|min:1|max:1000',
            'about_key'=>'required|max:255',
            'categ'=>'required|max:50',
            'img'=>'required|mimes:png,jpg,jpeg',
            'initialPrice'=>'required|numeric|min:1|max:1000',
            'qte'=>'required|numeric|min:1'


        ]);
        if($validate->fails())
        {
            return response()->json([
                "status"=>422 ,
                "errors"=>$validate->getMessageBag(),

            ]);
        }
        else{
            $product=new product();
            $cat=categories::query()->where('name',"=",$request->categ)->select('id')->get();

           if(count($cat)!=0)
               $idCateg=$cat[0]->id;
           else
              $idCateg=$this->createCat($request->categ);





           $product->name=$request->product_name;
           $product->price=$request->start_price;
           $product->discount=$request->discount_price;
           $product->description=$request->about_key;
           $product->id_cat=$idCateg;
           $product->qte=$request->qte;
            $product->initialPrice=$request->initialPrice;

           $product->setAttribute('img-url',$request->file('img')->store('toPath', ['disk' => 'my_files']));
           $product->save();
           Cache::flush();

           $produ=[
               [
               "id"=>$product->id,
               "name"=>$product->name,
               "price"=>$product->price,
               "discount"=>$product->discount,
               "url"=>$product->getAttribute('img-url'),
               "description"=>$product->description,
               "id_cat"=>$product->id_cat,
                   "qte"=>$product->qte,
                   "initialPrice"=>$product->initialPrice,
               "categ_name"=>$request->categ,
                   "created_at"=>$product->created_at
               ]



           ];

            return response()->json([
                "status"=>200,
                "product"=>$produ
            ]);
        }


    }

    public function destroy($id){

        $product=product::query()->find($id);

        File::delete('products/'.$product->getAttribute('img-url'));
        $product->delete();
        Cache::flush();
        return response()->json([
            "status"=>200,
            "message"=>"product deleted"
        ]);
    }

    public function getDicountedProducts(){

       if(Cache::has('dicountedProducts')){
            $product=Cache::get('dicountedProducts');


        }
        else
        {
            $product=product::query()->whereColumn('price','<>','discount')->select(['id','name','price','discount','description','qte','img-url as url'])->where('qte','<>',0)->orderBy('created_at','Desc')->limit(5)->get();
            Cache::put('dicountedProducts',$product);

        }

        return response()->json([
            "status"=>200,
            "products"=>$product,


        ]);




    }

    public function getUsersProducts(Request $request){
        $filter=$request->filter;
        $filter2=$request->universalFilter;
        if($filter2==="all")
        {
            if($filter==="Lasted first")
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->orderBy('created_at','DESC')->paginate(8);
            else if($filter==="Earliest first")
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->paginate(8);
            else if($filter==="AZ")
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->orderBy('name')->paginate(8);
            else if($filter==="ZA")
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->orderBy('name','DESC')->paginate(8);
            else
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->where('name', 'like', $filter . '%')->paginate(8);
        }
        else if($filter2==="other"){
            $catids=Cache::get('categ');
            $ids=[];
            for($i=0;$i<count($catids);$i++)
                $ids[$i]=$catids[$i]['id'];
            if($filter==="Lasted first")
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->whereNotIn('id_cat',$ids)->orderBy('created_at','DESC')->paginate(8);
            else if($filter==="Earliest first")
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->whereNotIn('id_cat',$ids)->paginate(8);
            else if($filter==="AZ")
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->whereNotIn('id_cat',$ids)->orderBy('name')->paginate(8);
            else if($filter==="ZA")
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->whereNotIn('id_cat',$ids)->orderBy('name','DESC')->paginate(8);
            else
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->where('name', 'like', $filter . '%')->paginate(8);
        }
        else
        {
            if($filter==="Lasted first")
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->where('id_cat','=',$filter2)->orderBy('created_at','DESC')->paginate(8);
            else if($filter==="Earliest first")
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->where('id_cat','=',$filter2)->paginate(8);
            else if($filter==="AZ")
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->where('id_cat','=',$filter2)->orderBy('name')->paginate(8);
            else if($filter==="ZA")
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->where('id_cat','=',$filter2)->orderBy('name','DESC')->paginate(8);
            else
                $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->where('name', 'like', $filter . '%')->paginate(8);
        }


        return response()->json([
           "status"=>200,
           "products"=>$product,

        ]);
    }
    public function getLastProducts(){
        $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->orderBy('created_at','DESC')->limit(6)->get();
        return response()->json([
            "status"=>200,
            "products"=>$product,

        ]);
    }
    public function getSoonProducts(){
        $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->where('qte','=',0)->orderBy('created_at','DESC')->limit(6)->get();
        return response()->json([
            "status"=>200,
            "products"=>$product,

        ]);
    }
    public function getBestSelled(){
        $id=orders::query()->groupBy('id_product')->selectRaw('count(id) as total, id_product')->orderBy('total','DESC')->limit(6)->get();
        $ids=[];
        for($i=0;$i<count($id);$i++){
            $ids[$i]=$id[$i]['id_product'];
        }
        $product= $product=product::query()->select(['id','name','price','discount','description','qte','img-url as url'])->whereIn('id',$ids)->get();
        return response()->json([
            "status"=>200,
            "products"=>$product

        ]);

    }

    public function search(Request $request)
    {
        $text = $request->text;
        if(empty($text))
            $product=[];
        else
            $product = product::query()->select(['id', 'name', 'price', 'discount', 'description', 'qte', 'img-url as url'])->where('name', 'like', $text . '%')->limit(5)->get();
        return response()->json([
            "status" => 200,
            "products" => $product

        ]);
    }
}
