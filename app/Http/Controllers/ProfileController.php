<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Display user profile with list of chat history conversations.
     */
    public function index()
    {
        $user = Auth::user();

        $conversations = Conversation::where('user_id', $user->id)
            ->withCount('messages')
            ->with(['messages' => function ($q) {
                $q->latest('created_at')->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->paginate(12);

        return view('profile', compact('user', 'conversations'));
    }

    /**
     * Update user profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $user->name = $validated['name'];
        $user->phone = $validated['phone'] ?? null;
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Profil Anda berhasil diperbarui!');
    }
}
