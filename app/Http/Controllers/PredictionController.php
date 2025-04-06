<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PredictionController extends Controller
{
    public function predict(Request $request)
    {
        $request->validate([
            'public_url' => 'required|url',
            'video' => 'required|file|mimes:mp4,avi,mov',
        ]);

        try {
            $response = Http::attach(
                'video',
                file_get_contents($request->file('video')->getRealPath()),
                $request->file('video')->getClientOriginalName()
            )->post($request->public_url . '/predict');

            if ($response->successful()) {
                return redirect()->back()->with('result', $response->json());
            } else {
                return redirect()->back()->withErrors(['API Error' => $response->body()]);
            }
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['Exception' => $e->getMessage()]);
        }
    }
}
