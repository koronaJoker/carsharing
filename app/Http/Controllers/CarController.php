<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function index(Request $request)
    {
        $query = Car::query();
        $priceBounds = Car::query()
            ->selectRaw('MIN(price_per_minute) as min_price, MAX(price_per_minute) as max_price')
            ->first();

        $minAllowedPrice = (float) ($priceBounds->min_price ?? 0);
        $maxAllowedPrice = (float) ($priceBounds->max_price ?? 0);

        $minPrice = $request->filled('min_price') ? (float) $request->input('min_price') : $minAllowedPrice;
        $maxPrice = $request->filled('max_price') ? (float) $request->input('max_price') : $maxAllowedPrice;

        if ($minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        if ($request->brand) {
            $query->where('brand', $request->brand);
        }

        if ($request->fuel) {
            $query->where('fuel_type', $request->fuel);
        }

        if ($request->transmission) {
            $query->where('transmission', $request->transmission);
        }

        if ($request->year) {
            $query->where('year', $request->year);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $query->whereBetween('price_per_minute', [$minPrice, $maxPrice]);

        // SORTING
        if ($request->sort === 'price_asc') {
            $query->orderBy('price_per_minute', 'asc');
        }

        if ($request->sort === 'price_desc') {
            $query->orderBy('price_per_minute', 'desc');
        }

        // DATA
        $cars = $query->get();

        $brands = Car::select('brand')->distinct()->pluck('brand');
        $years = Car::select('year')->distinct()->pluck('year');

        return view('cars', [
            'cars' => $cars,
            'brands' => $brands,
            'years' => $years,
            'minAllowedPrice' => $minAllowedPrice,
            'maxAllowedPrice' => $maxAllowedPrice,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'active' => 'cars',
            'title' => 'Автомобили'
        ]);
    }
}