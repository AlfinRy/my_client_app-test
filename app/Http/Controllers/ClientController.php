<?php


namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::whereNull('deleted_at')->get();

        $clients->transform(function ($client) {
            $client->client_logo = url('storage/' . $client->client_logo);
            return $client;
        });

        return response()->json($clients);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        try {
            // Log the incoming request data
            Log::info('Incoming request data:', $request->all());

            $validated = $request->validate([
                'name' => 'required',
                'slug' => 'required|unique:my_client,slug',
                'is_project' => 'required|boolean',
                'self_capture' => 'required|boolean',
                'client_prefix' => 'required|string|max:4',
                'client_logo' => 'nullable|string', // Changed to nullable since we're not handling file upload yet
                'address' => 'nullable|string',
                'phone_number' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:50',
            ]);

            // Log the validated data
            Log::info('Validated data:', $validated);

            $client = Client::create($validated);

            // Log the created client
            Log::info('Created client:', $client->toArray());

            Redis::set($request->slug, json_encode($client));

            return response()->json([
                'message' => 'Data created successfully',
                'data' => $client
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error:', $e->errors());
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating client: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error creating data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $client = Client::findOrFail($id);

        $client->update($request->all());

        Redis::del($client->slug);
        Redis::set($client->slug, json_encode($client));

        return response()->json(['message' => 'Data updated', 'data' => $client]);
    }

    public function destroy(string $id)
    {
        $client = Client::findOrFail($id);

        $client->update(['deleted_at' => now()]);

        Redis::del($client->slug);

        return response()->json(['message' => 'Data soft deleted']);
    }
}
