<?php

namespace App\Http\Controllers;

use App\Http\Requests\Rating\RatingRequest;
use App\Models\Rating;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function index()
    {
        $ratings = Rating::with(['rater', 'ratee'])->get();
        return ResponseTrait::success('Ratings fetched successfully', $ratings);
    }

    public function giveRating(RatingRequest $request)
    {
        try {
            $rating = Rating::giveRating($request->validated());
            return ResponseTrait::success('Rating created successfully', $rating);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Failed to create rating', $th->getMessage());
        }
    }

    public function getRatingByUserId() {
        $userId = auth()->id();
        $ratings = Rating::where('rater_id', $userId)->with(['rater', 'ratee'])->get();
        return ResponseTrait::success('Ratings fetched successfully', $ratings);
    }
}
