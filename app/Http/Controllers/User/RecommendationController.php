<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\Recommendation\RecommendationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function index(RecommendationService $recommendationService): View
    {
        $user = Auth::user();
        $purchasedProductIds = $recommendationService->getPurchasedProductIds($user);
        $hasPurchaseHistory = $purchasedProductIds->isNotEmpty();
        $recommendations = $recommendationService->getRecommendationsFor($user, 2);
        $frequentlyBoughtTogether = $recommendationService->getFrequentlyBoughtTogether($user, 2);
        $hasOrderBasedPairs = $frequentlyBoughtTogether->contains('based_on_orders', true);

        return view('user.recommendations.index', compact(
            'hasPurchaseHistory',
            'purchasedProductIds',
            'hasOrderBasedPairs',
            'recommendations',
            'frequentlyBoughtTogether',
        ));
    }
}
