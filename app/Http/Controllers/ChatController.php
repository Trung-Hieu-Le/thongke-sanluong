<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Pusher\Pusher;
use Illuminate\Support\Facades\Log;
use App\Events\MessageSent;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $userId = $request->session()->get('userid');
        $userRole = $request->session()->get('role');

        return view('layouts_chat.index', compact('userId'));
    }

    public function search(Request $request)
    {
        //TODO: nếu role!=3 thì ko hiện user không gửi tin cho mình, ko cho chat
        $query = $request->input('query');
        $currentUserId = session('userid');
        $userRole = session('role');
        $usersQuery = DB::table('tbl_user')
            ->select(
                'tbl_user.user_name',
                'tbl_user.user_id',
                DB::raw('SUM(CASE WHEN messages.seen = 0 AND messages.receiver_id = ' . $currentUserId . ' THEN 1 ELSE 0 END) as unread_count'),
                DB::raw('(SELECT message FROM messages 
                    WHERE (messages.sender_id = tbl_user.user_id AND messages.receiver_id = ' . $currentUserId . ') 
                        OR (messages.sender_id = ' . $currentUserId . ' AND messages.receiver_id = tbl_user.user_id) 
                    ORDER BY created_at DESC 
                    LIMIT 1) as latest_message'),
                DB::raw('(SELECT sender_id FROM messages 
                    WHERE (messages.sender_id = tbl_user.user_id AND messages.receiver_id = ' . $currentUserId . ') 
                        OR (messages.sender_id = ' . $currentUserId . ' AND messages.receiver_id = tbl_user.user_id) 
                    ORDER BY created_at DESC 
                    LIMIT 1) as sender_id'),
                DB::raw('(SELECT attachments FROM messages 
                    WHERE (messages.sender_id = tbl_user.user_id AND messages.receiver_id = ' . $currentUserId . ') 
                        OR (messages.sender_id = ' . $currentUserId . ' AND messages.receiver_id = tbl_user.user_id) 
                    ORDER BY created_at DESC 
                    LIMIT 1) as has_attachment')
            )
            ->leftJoin('messages', 'tbl_user.user_id', '=', 'messages.sender_id')
            ->where('tbl_user.user_id', '!=', $currentUserId)
            ->where('tbl_user.user_name', 'LIKE', "%{$query}%");
            if ($userRole != 3) {
                $usersQuery->where('messages.receiver_id', $currentUserId);
            }
            $users = $usersQuery->groupBy('tbl_user.user_id')
            ->get();
        return response()->json($users);
    }

    public function loadChat($userId)
    {
        DB::table('messages')
            ->where('sender_id', $userId)
            ->where('receiver_id', session('userid'))
            ->update(['seen' => 1]);
        $messages = DB::table('messages')
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', session('userid'))
                    ->where('receiver_id', $userId);
            })
            ->orWhere(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->where('receiver_id', session('userid'));
            })
            ->get();

        return response()->json(['messages' => $messages]);
    }

    public function sendMessage(Request $request)
    {
        try {
            $sender_id = session('userid');
            $receiver_id = $request->input('receiver_id');
            $message = $request->input('message');

            if (is_null($sender_id) || is_null($receiver_id)) {
                throw new \Exception('Missing sender or receiver.');
            }

            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $newName = uniqid() . '.' . $file->getClientOriginalExtension();
                    $oldName = $file->getClientOriginalName();
                    // Lưu file vào thư mục storage/app/public/attachments
                    $file->storeAs('public/attachments', $newName);
                    $attachments[] = [
                        'new_name' => $newName,
                        'old_name' => $oldName,
                    ];
                }
            }
            if (empty($message) && empty($attachments)) {
                throw new \Exception('Message content or attachment is required.');
            }
            DB::table('messages')->insert([
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id,
                'message' => $message,
                'attachments' => json_encode($attachments), // Lưu mảng attachments dưới dạng JSON
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // Phát sự kiện MessageSent qua Pusher
            event(new MessageSent($message, $sender_id, $receiver_id));

            return response()->json(['status' => 'Message sent successfully']);
        } catch (\Exception $e) {
            // Ghi log lỗi chi tiết và trả về phản hồi lỗi
            Log::error('Error sending message: ' . $e->getMessage());
            return response()->json(['status' => 'Error sending message', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteMessage($id)
    {
        try {
            $message = DB::table('messages')->where('id', $id)->first();
            if ($message && $message->attachments) {
                $attachments = json_decode($message->attachments, true);
                foreach ($attachments as $attachment) {
                    $filePath = storage_path('app/public/attachments/' . $attachment['new_name']);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
            DB::table('messages')->where('id', $id)->delete();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    public function markMessagesAsSeen($userId)
    {
        $currentUserId = session('userid');

        DB::table('messages')
            ->where('receiver_id', $currentUserId)
            ->where('sender_id', $userId)
            ->where('seen', 0)
            ->update(['seen' => 1]);

        return response()->json([
            'status' => 'success'
        ]);
    }
    public function getUnreadMessageCount($userId)
    {
        $currentUserId = session('userid');

        $unreadCount = DB::table('messages')
            ->where('receiver_id', $currentUserId)
            ->where('sender_id', $userId)
            ->where('seen', 0)
            ->count();

        return response()->json([
            'unreadCount' => $unreadCount
        ]);
    }
    public function checkUnreadMessages()
    {
        $unreadCount = DB::table('messages')
            ->where('receiver_id', session('userid'))
            ->where('seen', 0)
            ->count();

        return response()->json(['unread_count' => $unreadCount]);
    }
}
