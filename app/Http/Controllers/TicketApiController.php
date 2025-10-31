<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TicketApiController extends Controller
{
    /**
     * Get all tickets
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::query();

        // Optional filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('product')) {
            $query->where('product', $request->product);
        }

        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        // Pagination
        $perPage = $request->get('per_page', 50);
        $tickets = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }

    /**
     * Get single ticket
     */
    public function show(Ticket $ticket): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $ticket
        ]);
    }

    /**
     * Create new ticket
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product' => 'required|string',
            'module' => 'required|string',
            'device_type' => 'nullable|string',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'company_name' => 'required|string',
            'zoho_ticket_number' => 'nullable|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:Open,In Progress,Resolved,Closed',
            'assigned_to' => 'nullable|integer',
            'reported_by' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = Ticket::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Ticket created successfully',
            'data' => $ticket
        ], 201);
    }

    /**
     * Update ticket
     */
    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product' => 'sometimes|string',
            'module' => 'sometimes|string',
            'device_type' => 'nullable|string',
            'priority' => 'sometimes|in:Low,Medium,High,Critical',
            'company_name' => 'sometimes|string',
            'zoho_ticket_number' => 'nullable|string',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:Open,In Progress,Resolved,Closed',
            'assigned_to' => 'nullable|integer',
            'reported_by' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Ticket updated successfully',
            'data' => $ticket
        ]);
    }

    /**
     * Delete ticket
     */
    public function destroy(Ticket $ticket): JsonResponse
    {
        $ticket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ticket deleted successfully'
        ]);
    }
}
