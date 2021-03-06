<?php

namespace App\Http\Controllers;

use App\Cart;
use App\Categories;
use PDF;
use App\Product;
use Cartalyst\Stripe\Api\Products;
use Cartalyst\Stripe\Laravel\Facades\Stripe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::all();
        return view('product.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'qty' => 'required|numeric|min:1'
        ]);

        $cart = new Cart(session()->get('cart'));
        $cart->updateQty($product->id, $request->qty);
        session()->put('cart', $cart);
        return redirect()->route('cart.show')->with('success', 'Product updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $cart = new Cart(session()->get('cart'));
        $cart->remove($product->id);

        if ($cart->totalQty <= 0) {
            session()->forget('cart');
        } else {
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.show')->with('success', 'Product was removed');
    }

    public function addToCart(Product $product)
    {

        if (session()->has('cart')) {
            $cart = new Cart(session()->get('cart'));
        } else {
            $cart = new Cart();
        }
        $cart->add($product);
        //dd($cart);
        session()->put('cart', $cart);
        return redirect()->route('product.index')->with('success', 'Product was added');
    }

    public function showCart()
    {

        if (session()->has('cart')) {
            $cart = new Cart(session()->get('cart'));
        } else {
            $cart = null;
        }

        return view('cart.show', compact('cart'));
    }

    public function checkout($amount)
    {

        return view('cart.checkout', compact('amount'));
    }

    public function charge(Request $request)
    {

        //dd($request->stripeToken);
        $charge = Stripe::charges()->create([
            'currency' => 'USD',
            'source' => $request->stripeToken,
            'amount'   => $request->amount,
            'description' => $request->description
        ]);

        $chargeId = $charge['id'];

        if ($chargeId) {
            // save order in orders table ...

            auth()->user()->orders()->create([
                'cart' => serialize(session()->get('cart'))
            ]);
            // clearn cart

            session()->forget('cart');
            return redirect()->route('store')->with('success', "Payment was done. Thanks");
        } else {
            return redirect()->back();
        }
    }

    public function productdetail(Product $product, Request $request)
    {
        $id = $request->id;
        $productdetail = Product::where('id', $id)->get();
        return view('productdetail', compact('productdetail'));
    }

    public function categoryId(Product $product, Request $request)
    {
        $id = $request->id;
        $categoryId = Product::where('category_id', $id)->get();
        return view('product.indexcategory', compact('categoryId'));
    }

    public function search()
    {
        $search_text = $_GET['query'];
        $products = Product::where('title', 'LIKE', '%' . $search_text . '%')->get();
        return view('product.search', compact('products'));
    }


    // print
    public function report()
    {
        $pdf = PDF::loadView('report.pdf');
        return $pdf->download('report.pdf');

    }
    public function reportBill()
    {
        $pdf = PDF::loadView('report.pdfBill');
        if (session()->has('cart')) {
            $cart = new Cart(session()->get('cart'));
        } else {
            $cart = null;
        }
        return $pdf->download('reportBill.pdf');

    }
}
