<?php

namespace App\Http\Controllers;

use App\Models\Healthcare;
use Illuminate\Http\Request;

class HealthcareController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Healthcare::all();
        return Response()->json($data, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

            if ($request->hasFile('video')) {
                $video = $request->file('video');
                $name = time() . '.' . $video->getClientOriginalExtension();
                $destinationPath = public_path('/healthcare_video');
                $video->move($destinationPath, $name);
                $data['video'] = $name;
            }

            $healthcare = Healthcare::create($data);

            return response()->json($healthcare, 201);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data = Healthcare::find($id);
        return response()->json($data, 200);
    }

    public function uploadVideo(Request $request)
    {
        try {
            $healthcare = Healthcare::find($request->healthcare_id);

            // check if data exists
            if (!$healthcare) {
                return response()->json(['message' => 'Healthcare not found'], 404);
            }

            // delete existing video
            if ($healthcare->video) {
                $video_path = public_path('/healthcare_video/') . $healthcare->video;
                if (file_exists($video_path)) {
                    unlink($video_path);
                }
            }

            // Handle video upload
            if ($request->hasFile('video')) {
                $video = $request->file('video');
                $name = time() . '.' . $video->getClientOriginalExtension();
                $destinationPath = public_path('/healthcare_video');
                $video->move($destinationPath, $name);

                // Update the healthcare with the video name
                $healthcare->update(['video' => $name]);

                return response()->json($healthcare, 200);
            }

            return response()->json(['message' => 'No video uploaded'], 400);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Healthcare $healthcare)
    {
        $data = $request->all();

        if ($request->hasFile('video')) {
            $video = $request->file('video');
            $name = time() . '.' . $video->getClientOriginalExtension();
            $destinationPath = public_path('/healthcare_video');
            $video->move($destinationPath, $name);
            $data['video'] = $name;
        }

        $healthcare->update($data);

        return response()->json($healthcare, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Healthcare $healthcare)
    {
        // Delete the video file
        if (file_exists(public_path('/healthcare_video/' . $healthcare->video))) {
            unlink(public_path('/healthcare_video/' . $healthcare->video));
        }

        $healthcare->delete();
        return response()->json(null, 204);
    }
}
