<?php

namespace App\Http\Controllers;

use App\Models\FlaskSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FlaskSettingController extends Controller
{
    public function showForm()
    {
        return view('set_url');
    }

    public function storeUrl(Request $request)
    {
        $request->validate([
            'public_url' => 'required|url'
        ]);

        $cleanUrl = rtrim($request->public_url, '/');

        FlaskSetting::updateOrCreate(
            ['id' => 1],
            ['public_url' => $cleanUrl]
        );

        return redirect()->back()->with('success', 'URL saved successfully!');
    }


    public function getFlaskUrl()
    {
        $url = FlaskSetting::find(1)?->public_url;

        return response()->json(['public_url' => $url]);
    }

    public function sendVideoToFlask(Request $request)
    {
        $video = $request->file('video');

        if (!$video || !$video->isValid()) {
            return response()->json(['error' => 'Invalid or missing video file.'], 400);
        }

        $flaskUrl = FlaskSetting::find(1)?->public_url;

        if (!$flaskUrl) {
            return response()->json(['error' => 'Flask URL not configured.'], 400);
        }

        $response = Http::attach(
            'video',
            file_get_contents($video->getPathname()), // ✅ this is the fix
            $video->getClientOriginalName()
        )->post("$flaskUrl/predict");

        return $response->json();
    }
}
