<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppNotification;
use Illuminate\Support\Facades\Auth;
 
class NotificationController extends Controller
{ 
    /**
     * Récupérer toutes les notifications de l'utilisateur connecté
     *
     * @return \Illuminate\Http\Response
     */
    public function getMyNotifications()
    {
        $notifications = AppNotification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications); 
    }

    /**
     * Récupérer les notifications non lues de l'utilisateur connecté
     *
     * @return \Illuminate\Http\Response
     */
    public function getMyUnreadNotifications()
    {
        $notifications = AppNotification::where('user_id', Auth::id())
            ->where('read', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    /**
     * Compter les notifications non lues de l'utilisateur connecté
     *
     * @return \Illuminate\Http\Response
     */
    public function countMyUnreadNotifications()
    {
        $count = AppNotification::where('user_id', Auth::id())
            ->where('read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Marquer une notification comme lue
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function markAsRead($id)
    { 
        $notification = AppNotification::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail(); 

        $notification->update(['read' => true]);

        return response()->json([
            'message' => 'Notification marquée comme lue.',
            'notification' => $notification
        ]);
    }

    /**
     * Marquer toutes les notifications de l'utilisateur comme lues
     *
     * @return \Illuminate\Http\Response
     */
    public function markAllAsRead()
    { 
        AppNotification::where('user_id', Auth::id())
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json([
            'message' => 'Toutes les notifications ont été marquées comme lues.'
        ]);
    }

    /**
     * Supprimer une notification
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteNotification($id)
    {
        $notification = AppNotification::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'message' => 'Notification supprimée.'
        ]);
    }

    /**
     * Supprimer toutes les notifications de l'utilisateur
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteAllNotifications()
    {
        AppNotification::where('user_id', Auth::id())->delete();

        return response()->json([
            'message' => 'Toutes les notifications ont été supprimées.'
        ]);
    } 
}
