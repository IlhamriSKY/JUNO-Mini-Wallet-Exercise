<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserToken;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class WalletController extends Controller
{
    public function signUp(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            $response = [
                'status' => 'success',
                'data' => [
                    'customer_xid' => $user->customer_xid,
                ],
            ];

            return response()->json($response, 201);
        } catch (\Exception $e) {
            $response = [
                'status' => 'error',
                'message' => 'Failed to create user.',
            ];

            return response()->json($response, 500);
        }
    }

    public function initialize(Request $request): JsonResponse
    {
        $customerXID = $request->input('customer_xid');

        $validatedData = $request->validate([
            'customer_xid' => 'required|string',
        ]);

        $user = User::where('customer_xid', $customerXID)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        $token = generateToken();

        $user->api_token = $token;
        $user->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'User initialized successfully.',
                'token' => $token,
            ],
        ]);
    }


    public function enableWallet(Request $request): JsonResponse
    {
        $token = getToken($request->header('Authorization'));
        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found. Please make sure you are logged in.',
            ], 401);
        }

        if ($user->wallet_enabled) {
            return response()->json([
                'status' => 'error',
                'message' => 'Wallet is already enabled.',
            ], 400);
        }

        $user->update([
            'wallet_enabled' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'Wallet enabled successfully.',
            ],
        ]);
    }

    public function viewBalance(Request $request): JsonResponse
    {
        $token = getToken($request->header('Authorization'));
        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. User not found.',
            ], 401);
        }

        $totalDeposits = WalletTransaction::where('user_id', $user->id)
            ->where('type', 'deposit')
            ->sum('amount');

        $totalWithdrawals = WalletTransaction::where('user_id', $user->id)
            ->where('type', 'withdrawal')
            ->sum('amount');

        $balance = $totalDeposits - $totalWithdrawals;

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'Wallet balance retrieved successfully.',
                'balance' => $balance,
            ],
        ]);
    }

    public function viewTransactions(Request $request): JsonResponse
    {
        $token = getToken($request->header('Authorization'));
        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. User not found.',
            ], 401);
        }

        $transactions = WalletTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedTransactions = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'reference_id' => $transaction->reference_id,
                'created_at' => $transaction->created_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'Wallet transactions retrieved successfully.',
                'transactions' => $formattedTransactions,
            ],
        ]);
    }

    public function addDeposit(Request $request): JsonResponse
    {
        $token = getToken($request->header('Authorization'));
        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. User not found.',
            ], 401);
        }

        $validatedData = $request->validate([
            'amount' => 'required|numeric',
            'reference_id' => 'required|unique:wallet_transactions',
        ]);

        if (!$user->wallet_enabled) {
            return response()->json([
                'status' => 'error',
                'message' => 'Wallet is not enabled for this user.',
            ], 400);
        }

        $transaction = new WalletTransaction();
        $transaction->user_id = $user->id;
        $transaction->type = 'deposit';
        $transaction->amount = $validatedData['amount'];
        $transaction->reference_id = $validatedData['reference_id'];
        $transaction->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'Deposit added successfully.',
            ],
        ]);
    }

    public function makeWithdrawal(Request $request): JsonResponse
    {
        $token = getToken($request->header('Authorization'));
        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. User not found.',
            ], 401);
        }

        $validatedData = $request->validate([
            'amount' => 'required|numeric',
            'reference_id' => 'required|unique:wallet_transactions',
        ]);

        if (!$user->wallet_enabled) {
            return response()->json([
                'status' => 'error',
                'message' => 'Wallet is not enabled for this user.',
            ], 400);
        }

        $totalDeposits = WalletTransaction::where('user_id', $user->id)
            ->where('type', 'deposit')
            ->sum('amount');

        $totalWithdrawals = WalletTransaction::where('user_id', $user->id)
            ->where('type', 'withdrawal')
            ->sum('amount');

        $currentBalance = $totalDeposits - $totalWithdrawals;

        if ($validatedData['amount'] > $currentBalance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Insufficient balance for withdrawal.',
            ], 400);
        }

        $transaction = new WalletTransaction();
        $transaction->user_id = $user->id;
        $transaction->type = 'withdrawal';
        $transaction->amount = $validatedData['amount'];
        $transaction->reference_id = $validatedData['reference_id'];
        $transaction->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'Withdrawal made successfully.',
            ],
        ]);
    }

    public function disableWallet(Request $request): JsonResponse
    {
        $token = getToken($request->header('Authorization'));
        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. User not found.',
            ], 401);
        }

        $validatedData = $request->validate([
            'is_disabled' => 'required|boolean',
        ]);

        $user->wallet_enabled = !$validatedData['is_disabled'];
        $user->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'Wallet status updated successfully.',
            ],
        ]);
    }
}
