<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ArtisanController extends Controller
{
    public function runGenerateShippingCommand(Request $request)
    {
        // Run the Artisan command
        Artisan::call('pos:GenerateShippingLabels');

        // Optionally get the output of the command
        $output = Artisan::output();

        // Return a response or redirect back with the result
        return response()->json([
            'success' => true,
            'message' => 'Artisan command executed successfully!',
            'output' => $output
        ]);
    }

    public function runGenerateShippingCommandGLS(Request $request)
    {
        // Run the Artisan command
        Artisan::call('pos:GenerateShippingLabelsGLS');

        // Optionally get the output of the command
        $output = Artisan::output();

        // Return a response or redirect back with the result
        return response()->json([
            'success' => true,
            'message' => 'Artisan command executed successfully!',
            'output' => $output
        ]);
    }
}
