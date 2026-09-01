<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Recommendation\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private RecommendationService $recommendationService) {}

    public function index(Request $request): View
    {
        $featuredProducts = Product::query()
            ->where('is_active', true)
            ->latest()
            ->take(8)
            ->get();

        $recommendations = $request->user()
            ? $this->recommendationService->getRecommendationsFor($request->user())
            : collect();

        return view('user.home', compact('featuredProducts', 'recommendations'));
    }
}