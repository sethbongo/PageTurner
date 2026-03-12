<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Support\Facades\Auth;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Order summary
        $totalOrders = $user->orders()->where('status', '!=', 'Cart')->count();
        $recentOrders = $user->orders()
            ->where('status', '!=', 'Cart')
            ->with('orderItems.book')
            ->latest()
            ->take(5)
            ->get();
        
        // Order status counts
        $orderStatusCounts = $user->orders()
            ->where('status', '!=', 'Cart')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');
        
        // Recently purchased books (from delivered orders)
        $purchasedBooks = $user->orders()
            ->whereIn('status', ['Delivered', 'Processing', 'Shipped'])
            ->with('orderItems.book')
            ->latest()
            ->take(3)
            ->get()
            ->pluck('orderItems')
            ->flatten()
            ->pluck('book')
            ->unique('id')
            ->take(6);
        
        // Review activity
        $reviewCount = Review::where('user_id', $user->id)->count();
        $recentReviews = Review::where('user_id', $user->id)
            ->with('book')
            ->latest()
            ->take(5)
            ->get();
        
        // Account status indicators
        $emailVerified = $user->hasVerifiedEmail();
        $twoFactorEnabled = !is_null($user->two_factor_secret);
        
        return view('customer.dashboard', compact(
            'user',
            'totalOrders',
            'recentOrders',
            'orderStatusCounts',
            'purchasedBooks',
            'reviewCount',
            'recentReviews',
            'emailVerified',
            'twoFactorEnabled'
        ));
    }
}
