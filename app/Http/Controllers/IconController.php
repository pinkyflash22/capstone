<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Multimedia;
use Illuminate\Http\Request;

class IconController extends Controller
{

    public function index()
    {
     
        return response()->json(Multimedia::all());
    }
    //save icon
    public function store(Request $request){
        //    $imagepath = null;
        // if($request -> hasFile('image'))
        //     {
        //         $photopath = $request ->file('image')-> store('images', 'public');
        //     }

         $request->validate([
            'path' => 'required|string',
            'type' => 'required|string',

        ]);

        $icon = Multimedia::create([
            'path' => $request->input('path'),
            'type' => $request->input('type'),

        ]);

        
        return response()->json([
            'success' => true,
            'icon' => $icon
        ]);
    }
   public function getIcons()
{
    $posts = Multimedia::all();
    
    return response()->json([
        'success' => true,
        'data' => $posts,
        'count' => $posts->count()
    ]);
   
}
}
