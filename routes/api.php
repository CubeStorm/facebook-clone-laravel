<?php

use App\Http\Controllers\Api\FriendshipController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PokeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->group(function () {

        Route::controller(UserController::class)
            ->group(function () {
                Route::get('/user', 'user');
            });

        Route::controller(FriendshipController::class)
            ->prefix('/friendship')
            ->group(function () {
                Route::get('/friends/{user}', 'friends');
                Route::get('/suggests', 'suggests');
                Route::get('/invites', 'invites');
                
                Route::post('/invite', 'invite');
                Route::post('/accept', 'accept');
                Route::post('/reject', 'reject');
                Route::post('/destroy', 'destroy');
            });

        Route::controller(PokeController::class)
            ->group(function () {
                Route::get('/pokes', 'index');
                Route::post('/pokes', 'store');
                Route::post('/pokes/{poke}', 'update');
            });

        Route::controller(NotificationController::class)
            ->group(function () {
                Route::get('/notifications', 'index');
                Route::post('/notifications/mark-as-read', 'markAsRead');
            });

        Route::controller(MessageController::class)
            ->prefix('/messages')
            ->group(function () {
                Route::get('/{receiverId}', 'index')->whereNumber('receiverId');
                Route::post('/', 'store');
                Route::get('/messenger', 'messenger');
            });

        Route::controller(PostController::class)
            ->prefix('/posts')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
            });

        Route::controller(LikeController::class)
            ->prefix('/likes')
            ->group(function () {
                Route::post('/', 'store');
                Route::delete('/{post}', 'destroy');
            });

        Broadcast::routes();
    });

Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{user}', [UserController::class, 'show']);