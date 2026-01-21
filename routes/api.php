<?php

use App\Http\Controllers\AuthControlle;
use App\Http\Controllers\CodeProblemController;
use App\Http\Controllers\EnemiesController;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PowerupController;
use App\Http\Controllers\ProfilesSave;
use App\Models\CodeProblems;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/code-problems/check-structure', [CodeProblemController::class, "checkCodeStructure"]);
Route::post('/code-problems', [CodeProblemController::class, "getProblems"]);
Route::post('/enemies', [EnemiesController::class, "getEnemies"]);
Route::post('/register', [AuthControlle::class , 'register']); 
Route::post('/login' ,[AuthControlle::class , 'login']); 
Route::get('/powerups',[InventoryController::class, 'getPowerups']);
Route::post('/send-mail', [AuthControlle::class, 'sendMailVerification']);
Route::post('/gemini', [GeminiController::class, 'ask']);

Route::middleware('auth:sanctum')->group(
    function () {
        Route::post("/facebook-login", [AuthControlle::class, "facebookLogin"]);
        
        //powerups 
        Route::get('/getPowerups', [PowerupController::class, "showAll"]); 
        //inventory 
        Route::post('/addInventory/{itemId}',[InventoryController::class , 'addInventory']); 
        Route::post('/removeItem/{itemId}', [InventoryController::class, 'removeItem']);
        Route::post('/updateItem/{itemId}', [InventoryController::class, 'updateItem']);
        Route::get('/getInventory', [InventoryController::class, 'getInventory']);
        //profile
        Route::get('/LoadsaveProgress', [ProfilesSave::class, 'levelshowCompletedOnly']);
       
        //code PRoblem
        Route::post('/code-problems/check-blocks', [CodeProblemController::class, "checkSolution"]);
        Route::get('/code-problems/Profile', [CodeProblemController::class, "analyzePlayerStrengths"]);
        Route::get('/Profile', [CodeProblemController::class, "profile"]);

    }
);
