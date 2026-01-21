<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Interfaces\WishlistInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Wishlist;

class WishlistController extends Controller
{
    public function __construct(WishlistInterface $wishlistRepository) 
    {
        $this->wishlistRepository = $wishlistRepository;
        $this->ip = $_SERVER['REMOTE_ADDR'];
    }

    public function add(Request $request) 
    {
        $request->validate([
            "product_id" => "required|integer",
        ]);

        $params = $request->except('_token');
        $wishlistStore = $this->wishlistRepository->addToWishlist($params);

        if ($wishlistStore) {
            $wishlistCount = Wishlist::where('ip', $this->ip)->count();
            return response()->json(['status' => 200, 'message' => $wishlistStore, 'count' => $wishlistCount]);
            // return redirect()->back()->with('success', $wishlistStore);
        } else {
            return response()->json(['status' => 400, 'message' => 'Something happened']);
            // return redirect()->back()->with('failure', 'Something happened');
        }
    }

	public function remove(Request $request) 
    {
        $request->validate([
            "product_id" => "required|integer",
        ]);

        $params = $request->except('_token');

        $wishlistStore = $this->wishlistRepository->addToWishlist($params);

        if ($wishlistStore) {
            // return response()->json(['status' => 200, 'message' => $wishlistStore]);
            return redirect()->back()->with('success', 'Product removed from wishlist');
        } else {
            // return response()->json(['status' => 400, 'message' => 'Something happened']);
            return redirect()->back()->with('failure', 'Something happened');
        }
    }
}
