<?php
require __DIR__ . '/vendor/autoload.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Update;
use Dotenv\Dotenv;

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize Telegram bot
$botToken = $_ENV['BOT_TOKEN'];
$telegram = new Telegram($botToken);

require_once 'cards.php'; // Include the cards file

// Handle incoming updates
try {
    // Get updates from Telegram (webhook)
    $input = file_get_contents('php://input');
    $update = json_decode($input, true);
    $update = new Update($update);

    $message = $update->getMessage();
    if (!$message) {
        exit;
    }

    $chatId = $message->getChat()->getId();
    $text = trim($message->getText());

// Command handling
switch (strtolower($text)) {
    case '/start':
        $response = "Welcome to the Unity & C# Study Bot! Use:\n/unity - Unity basics\n/csharp - C# basics\n/testing - Testing basics\n/blockchain - Blockchain basics\n/multiplayer - Multiplayer basics\n/help - Show commands";
        break;

    case '/help':
        $response = "Commands:\n/start - Start the bot\n/unity - Get a Unity study card\n/csharp - Get a C# study card\n/testing - Get a testing study card\n/blockchain - Get a blockchain study card\n/multiplayer - Get a multiplayer study card\n/help - Show this help";
        break;

    case '/unity':
        $card = $unityCards[array_rand($unityCards)];
        $response = "*{$card['title']}*\n\n{$card['content']}";
        break;

    case '/csharp':
        $card = $csharpCards[array_rand($csharpCards)];
        $response = "*{$card['title']}*\n\n{$card['content']}";
        break;
        
    case '/testing':
        $card = $testingCards[array_rand($testingCards)];
        $response = "*{$card['title']}*\n\n{$card['content']}";
        break;
        
    case '/blockchain':
        $card = $blockchainCards[array_rand($blockchainCards)];
        $response = "*{$card['title']}*\n\n{$card['content']}";
        break;
        
    case '/multiplayer':
        $card = $multiplayerCards[array_rand($multiplayerCards)];
        $response = "*{$card['title']}*\n\n{$card['content']}";
        break; 

    default:
        $response = "Unknown command. Try /help for a list of commands.";
        break;
}

    // Send response
    Request::sendMessage([
        'chat_id' => $chatId,
        'text' => $response,
        'parse_mode' => 'Markdown',
    ]);

} catch (Exception $e) {
    // Log error (in production, use proper logging)
    error_log($e->getMessage());
}