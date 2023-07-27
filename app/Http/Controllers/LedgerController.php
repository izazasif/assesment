<?php

namespace App\Http\Controllers;
use App\Models\Product;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
class LedgerController extends Controller
{
    public function stockReport(Request $request)
    {   
          $startDate1 = $request->input('startDate');
          $endDate1 = $request->input('endDate');

          $startDate = Carbon::createFromFormat('Y-m-d', $startDate1);
          $endDate = Carbon::createFromFormat('Y-m-d', $endDate1);
          
        $dates = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }

        $purchaseQuery = DB::table('products')
            ->crossJoin('purchase_details')
            ->select(
                'products.id',
                'products.name',
                DB::raw('DATE(purchase_details.created_at) as date'),
                DB::raw('COALESCE(SUM(purchase_details.quantity), 0) as stock_in'),
                DB::raw('0 as stock_out')
            )
            ->whereBetween('purchase_details.created_at', [$startDate, $endDate])
            ->groupBy('products.id', 'products.name', 'date');

        $saleQuery = DB::table('products')
            ->crossJoin('sale_details')
            ->select(
                'products.id',
                'products.name',
                DB::raw('DATE(sale_details.created_at) as date'),
                DB::raw('0 as stock_in'),
                DB::raw('COALESCE(SUM(sale_details.quantity), 0) as stock_out')
            )
            ->whereBetween('sale_details.created_at', [$startDate, $endDate])
            ->groupBy('products.id', 'products.name', 'date');

        $unionQuery = $purchaseQuery->union($saleQuery);
        $report = DB::table(DB::raw("({$unionQuery->toSql()}) as union_table"))
                ->mergeBindings($unionQuery)
                ->orderBy('id')
                ->orderBy('date')
                ->get();

        $productStock = [];
        foreach ($report as $row) {
            $productId = $row->id;
            $productName = $row->name;
            $date = $row->date;
            $stockIn = $row->stock_in;
            $stockOut = $row->stock_out;

            $remainingStock = $stockIn - $stockOut;

            $productStock[$productId][$productName][$date] = [
                'stock_in' => $stockIn,
                'stock_out' => $stockOut,
                'remaining_stock' => $remainingStock,
            ];
        }
   return $productStock;
   
    }
}
