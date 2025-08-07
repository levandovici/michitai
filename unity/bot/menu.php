<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unity Learning Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap');
        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #e0e7ff 100%);
            font-family: 'Poppins', sans-serif;
        }
        .container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .title {
            color: #2d3748;
            font-weight: 700;
        }
        .menu-button {
            background: #4a90e2;
            color: #ffffff;
            border-radius: 12px;
            border: none;
            padding: 16px 20px;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        .menu-button:hover {
            background: #357abd;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .response-box {
            background: #f7fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            color: #4a5568;
        }
        @media (max-width: 640px) {
            .menu-button {
                padding: 12px 16px;
                font-size: 0.95rem;
            }
            #menu {
                gap: 0.75rem;
            }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="container p-8 w-full max-w-3xl mx-auto">
        <h1 class="title text-3xl text-center mb-6">Unity Learning Hub</h1>

        <div class="response-box p-4 min-h-[250px] text-sm mb-6">
            <?php
                function getCard($cards) {
                    return $cards[array_rand($cards)];
                }

                function formatMarkdown($text) {
                    $text = preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', $text);
                    return nl2br($text);
                }

                require_once 'cards.php'; // Include the cards file

                if (isset($_POST['command'])) {
                    $cmd = strtolower($_POST['command']);
                    switch ($cmd) {
                        case '/start':
                            echo formatMarkdown("Welcome to the Unity & C# Study Bot! Use:\n/unity - Unity basics\n/csharp - C# basics\n/testing - Testing basics\n/blockchain - Blockchain basics\n/multiplayer - Multiplayer basics\n/help - Show commands");
                            break;
                        case '/help':
                            echo formatMarkdown("Commands:\n/start - Start the bot\n/unity - Get a Unity study card\n/csharp - Get a C# study card\n/testing - Get a testing study card\n/blockchain - Get a blockchain study card\n/multiplayer - Get a multiplayer study card\n/help - Show this help");
                            break;
                        case 'unity':
                            $card = getCard($unityCards);
                            echo formatMarkdown("*{$card['title']}*\n\n{$card['content']}");
                            break;
                        case 'csharp':
                            $card = getCard($csharpCards);
                            echo formatMarkdown("*{$card['title']}*\n\n{$card['content']}");
                            break;
                        case 'testing':
                            $card = getCard($testingCards);
                            echo formatMarkdown("*{$card['title']}*\n\n{$card['content']}");
                            break;
                        case 'blockchain':
                            $card = getCard($blockchainCards);
                            echo formatMarkdown("*{$card['title']}*\n\n{$card['content']}");
                            break;
                        case 'multiplayer':
                            $card = getCard($multiplayerCards);
                            echo formatMarkdown("*{$card['title']}*\n\n{$card['content']}");
                            break;
                        default:
                            echo "Unknown command. Try /help for a list of commands.";
                    }
                } else {
                    echo "Click a command to get started!";
                }
            ?>
        </div>

        <form method="POST">
            <div id="menu" class="grid grid-cols-1 gap-3 mt-6 max-w-md mx-auto">
                <?php
                    $buttons = ['unity', 'csharp', 'testing', 'blockchain', 'multiplayer'];
                    foreach ($buttons as $btn) {
                        echo "<button name='command' value='$btn' class='menu-button'>$btn</button>";
                    }
                ?>
            </div>
        </form>
    </div>
</body>
</html>
