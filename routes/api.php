<?php

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/resetPass',[\App\Http\Controllers\AdminController::class,'resetPass']);
Route::post('/sendCodeVer',[\App\Http\Controllers\AdminController::class,'sendCodeVer']);
Route::middleware(['auth:admins'])->group(function (){

    Route::post('/changeUsernameAdmin',[\App\Http\Controllers\AdminController::class,'changeUsernameAdmin']);
    Route::post('/changePasswordAdmin',[\App\Http\Controllers\AdminController::class,'changePasswordAdmin']);
    Route::post('/changeMailAdmin',[\App\Http\Controllers\AdminController::class,'changeMailAdmin']);
    Route::get('/getAdmin',[\App\Http\Controllers\AdminController::class,'getAdmin']);
    Route::post('/logout',[\App\Http\Controllers\AdminController::class,'logout']);
    Route::get('/getProducts',[\App\Http\Controllers\ProductController::class,'getProducts']);
    Route::get('/bestSelledProd',[\App\Http\Controllers\OrderController::class,'bestSelledProducts']);
    Route::get('/getOrders',[\App\Http\Controllers\OrderController::class,'getOrdersCount']);
    Route::get('/adminOrders',[\App\Http\Controllers\OrderController::class,'adminOrders']);
    Route::post('/sendOrder',[\App\Http\Controllers\OrderController::class,'sendOrder']);
    Route::get('/confirmRefund/{id}',[\App\Http\Controllers\OrderController::class,'confirmRefund']);
    Route::get('/getOrdersDetails',[\App\Http\Controllers\OrderController::class,'getOrdersDetails']);
    Route::get('/getAllOrdersDetails',[\App\Http\Controllers\OrderController::class,'getAllOrdersDetails']);
    Route::get('/getDetailsOfYear',[\App\Http\Controllers\OrderController::class,'getDetailsOfYear']);
    Route::post("/storeProduct",[\App\Http\Controllers\ProductController::class,'storeProduct']);

    Route::post('/editProduct/{id}',[\App\Http\Controllers\ProductController::class,'editProduct']);


    Route::delete("/deleteProduct/{id}",[\App\Http\Controllers\ProductController::class,"destroy"]);
    Route::get("/getProduct/{id}",[\App\Http\Controllers\ProductController::class,"getProduct"]);

    Route::get('/isAuth',function (){
        return response()->json(
            [
                "status"=>200,

            ]
        );

    });
});
Route::middleware(['auth:admins'])->get('/user', function(){
    return auth()->guard('admin')->user();
});
Route::post('/login',[\App\Http\Controllers\AdminController::class,'login']);
Route::get('/categories',[\App\Http\Controllers\ProductController::class,'getCategories']);
Route::get('/getDicountedProducts',[\App\Http\Controllers\ProductController::class,'getDicountedProducts']);

Route::post('/getUsersProducts',[\App\Http\Controllers\ProductController::class,'getUsersProducts']);
Route::get('/lastProducts',[\App\Http\Controllers\ProductController::class,'getLastProducts']);
Route::get('/bestSelled',[\App\Http\Controllers\ProductController::class,'getBestSelled']);
Route::get('/soon',[\App\Http\Controllers\ProductController::class,'getSoonProducts']);
Route::post('/search',[\App\Http\Controllers\ProductController::class,'search']);

Route::group(['middleware' => ['XssSanitizer']], function (){
    Route::post('/register',[\App\Http\Controllers\UserController::class,'register']);
});
Route::post('/usersLogin',[\App\Http\Controllers\UserController::class,'login']);

Route::middleware(['auth:users'])->group(function (){



    Route::get('/isAuthentacated',function (){
        $user=\Illuminate\Support\Facades\Auth::user();

        return response()->json(
            [
                "status"=>200,
                "name"=>$user->name,
                "email"=>$user->email,
                "countCart"=>\App\Http\Controllers\UserController::getCountCart(),
                "countWish"=>\App\Http\Controllers\UserController::getCountWish(),
                "countOrders"=>\App\Http\Controllers\UserController::getCountOrders(),

            ]
        );

    });
    Route::post('/changeUsername',[\App\Http\Controllers\UserController::class,'changeUsername']);
    Route::post('/changeMail',[\App\Http\Controllers\UserController::class,'changeMail']);
    Route::post('/changePassword',[\App\Http\Controllers\UserController::class,'changePassword']);
    Route::post('/getPrices',[\App\Http\Controllers\PaymentCOntroller::class,'getPriceOfSelctedItems']);
    Route::post('/addToCart',[\App\Http\Controllers\CartController::class,'addToCart']);
    Route::post('/insertToCart',[\App\Http\Controllers\CartController::class,'insertToCart']);
    Route::get('/getUsersCart',[\App\Http\Controllers\CartController::class,'getUsersCart']);
    Route::get('/usersOrders',[\App\Http\Controllers\OrderController::class,'usersOrders']);
    Route::delete('/deleteItem/{id}',[\App\Http\Controllers\CartController::class,'deleteItem']);
    Route::post('/hideOrder/{id}',[\App\Http\Controllers\OrderController::class,'hideOrder']);
    Route::post('/askForRefund/{id}',[\App\Http\Controllers\OrderController::class,'askForRefund']);
    Route::delete('/deleteItemFromWish/{id}',[\App\Http\Controllers\WishListController::class,'deleteItemFromWish']);

    Route::post('/addToWishList',[\App\Http\Controllers\WishListController::class,'addToWishList']);
    Route::get('/getUsersWishList',[\App\Http\Controllers\WishListController::class,'getUsersWishList']);



    Route::post('/confirm',[\App\Http\Controllers\UserController::class,'verifyAcc']);
    Route::post('/userLogOut',[\App\Http\Controllers\UserController::class,'logout']);
    Route::group(['middleware' => ['emailVerifier']], function (){
        Route::post('/create-payment',[\App\Http\Controllers\PaymentCOntroller::class,'create_payment']);
        Route::post('/create-payment2',[\App\Http\Controllers\PaymentCOntroller::class,'create_payment2']);
        Route::get('/test',[\App\Http\Controllers\PaymentCOntroller::class,'test']);

        Route::post('/execute-payment',[\App\Http\Controllers\PaymentCOntroller::class,'execute_payment']);
        Route::post('/execute-payment2',[\App\Http\Controllers\PaymentCOntroller::class,'execute_payment2']);
    });


});












