<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Role;
use App\Models\RequestOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display admin dashboard data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminDashboard(Request $request)
    {
        // Get date range for filtering
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        
        // Total income
        $totalIncome = Order::where('payment_status', 'Paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');
            
        // Orders count by status
        $ordersByStatus = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select('order_status', DB::raw('count(*) as count'))
            ->groupBy('order_status')
            ->get();
            
        // Recent orders
        $recentOrders = Order::with(['user', 'orderItems.product'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        // Low stock products
        $lowStockProducts = Product::whereRaw('quantity <= low_stock_threshold')
            ->get();
            
        // Pending request orders
        $pendingRequestOrders = RequestOrder::with(['product', 'requestedBy'])
            ->where('admin_approval_status', 'Pending')
            ->get();
            
        // Top selling products
        $topSellingProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.payment_status', 'Paid')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.quantity * order_items.price) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();
            
        // User statistics
        $userStats = [
            'total_customers' => User::whereHas('role', function($q) {
                $q->where('name', 'Customer');
            })->count(),
            'new_customers' => User::whereHas('role', function($q) {
                $q->where('name', 'Customer');
            })->whereBetween('created_at', [$startDate, $endDate])->count()
        ];
        
        return response()->json([
            'total_income' => $totalIncome,
            'orders_by_status' => $ordersByStatus,
            'recent_orders' => $recentOrders,
            'low_stock_products' => $lowStockProducts,
            'pending_request_orders' => $pendingRequestOrders,
            'top_selling_products' => $topSellingProducts,
            'user_stats' => $userStats
        ]);
    }

    /**
     * Display warehouse manager dashboard data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function warehouseDashboard(Request $request)
    {
        // Pending request orders that need warehouse approval
        $pendingApprovals = RequestOrder::with(['product', 'requestedBy'])
            ->where('admin_approval_status', 'Approved')
            ->where('warehouse_approval_status', 'Pending')
            ->get();
            
        // Low stock products
        $lowStockProducts = Product::whereRaw('quantity <= low_stock_threshold')
            ->get();
            
        // Recent approved request orders
        $recentApprovedRequests = RequestOrder::with(['product', 'requestedBy'])
            ->where('warehouse_approval_status', 'Approved')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();
            
        // Product inventory summary
        $inventorySummary = [
            'total_products' => Product::count(),
            'low_stock_count' => Product::whereRaw('quantity <= low_stock_threshold')->count(),
            'out_of_stock_count' => Product::where('quantity', 0)->count(),
        ];
        
        return response()->json([
            'pending_approvals' => $pendingApprovals,
            'low_stock_products' => $lowStockProducts,
            'recent_approved_requests' => $recentApprovedRequests,
            'inventory_summary' => $inventorySummary
        ]);
    }

    /**
     * Display staff dashboard data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function staffDashboard(Request $request)
    {
        // Pending orders that need staff approval
        $pendingOrders = Order::with(['user', 'orderItems.product'])
            ->where('order_status', 'Pending')
            ->orderBy('created_at', 'asc')
            ->get();
            
        // Orders processed by this staff member
        $processedOrders = Order::with(['user', 'orderItems.product'])
            ->where('staff_id', $request->user()->id)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();
            
        // Orders ready for delivery
        $readyForDelivery = Order::with(['user', 'orderItems.product'])
            ->where('order_status', 'Processing')
            ->orderBy('updated_at', 'asc')
            ->get();
            
        return response()->json([
            'pending_orders' => $pendingOrders,
            'processed_orders' => $processedOrders,
            'ready_for_delivery' => $readyForDelivery
        ]);
    }

    /**
     * Display customer dashboard data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function customerDashboard(Request $request)
    {
        // Recent orders
        $recentOrders = Order::with(['orderItems.product', 'paymentMethod'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        // Order statistics
        $orderStats = [
            'total_orders' => Order::where('user_id', $request->user()->id)->count(),
            'pending_orders' => Order::where('user_id', $request->user()->id)
                ->whereIn('order_status', ['Pending', 'Processing'])
                ->count(),
            'delivered_orders' => Order::where('user_id', $request->user()->id)
                ->where('order_status', 'Delivered')
                ->count()
        ];
        
        // Cart summary
        $cart = $request->user()->cart()->with('cartItems.product')->first();
        $cartSummary = [
            'items_count' => $cart ? $cart->cartItems->count() : 0,
            'total_amount' => $cart ? $cart->getTotalAmount() : 0
        ];
        
        return response()->json([
            'recent_orders' => $recentOrders,
            'order_stats' => $orderStats,
            'cart_summary' => $cartSummary
        ]);
    }
}
