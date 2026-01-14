<?php
session_start();
require_once 'php/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multiplayer API – Core Cells</title>
    <link rel="icon" type="image/png" href="logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism-tomorrow.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-csharp.min.js"></script>
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --code-bg: #1e1e2d;
            --code-text: #ffffff;
            --code-comment: #6c757d;
            --code-keyword: #569cd6;
            --code-string: #ce9178;
            --code-number: #b5cea8;
            --code-type: #4ec9b0;
            --code-function: #dcdcaa;
            --code-operator: #d4d4d4;
        }
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .glass-effect {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
        }
        
        /* Code block styling */
        pre {
            background: var(--code-bg) !important;
            color: var(--code-text) !important;
            border-radius: 0.5rem !important;
            padding: 1.5rem !important;
            margin: 1rem 0 !important;
            font-family: 'Fira Code', 'Consolas', 'Monaco', 'Andale Mono', monospace !important;
            font-size: 0.9em !important;
            line-height: 1.6 !important;
            tab-size: 4 !important;
            overflow-x: auto !important;
            max-height: none !important;
            height: auto !important;
            min-height: 0 !important;
        }
        
        pre code {
            white-space: pre !important;
            word-wrap: normal !important;
            background: transparent !important;
            padding: 0 !important;
        }
        
        /* Syntax highlighting */
        .token.comment,
        .token.prolog,
        .token.doctype,
        .token.cdata {
            color: var(--code-comment) !important;
        }
        
        .token.keyword,
        .token.operator,
        .token.boolean,
        .token.selector {
            color: var(--code-keyword) !important;
        }
        
        .token.string,
        .token.attr-value,
        .token.char,
        .token.builtin {
            color: var(--code-string) !important;
        }
        
        .token.number,
        .token.constant,
        .token.symbol {
            color: var(--code-number) !important;
        }
        
        .token.class-name,
        .token.type-definition {
            color: var(--code-type) !important;
        }
        
        .token.function,
        .token.maybe-class-name {
            color: var(--code-function) !important;
        }
        
        /* Remove gradient overlays */
        .relative.before\:content-\[\'\'\]:before,
        .relative.after\:content-\[\'\'\]:after {
            content: none !important;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }
        
        .btn-secondary {
            background: var(--secondary-gradient);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(240, 147, 251, 0.4);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(240, 147, 251, 0.6);
        }
        
        .animated-bg {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .floating-card {
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .floating-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .feature-icon {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .stats-number {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="min-h-screen animated-bg">
    <!-- Header -->
    <header class="glass-effect border-b border-white/20 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <img src="logo.png" alt="Multiplayer API Logo" class="w-10 h-10 rounded-xl object-contain">
                    <div>
                        <h1 class="text-lg font-bold text-white">Multiplayer API</h1>
                        <p class="text-xs text-white/70">Core Cells</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="cabinet.html" class="btn-primary text-white px-6 py-2 rounded-lg font-medium">
                            <i class="fas fa-user-circle mr-2"></i>Cabinet
                        </a>
                    <?php else: ?>
                        <a href="login.html" class="btn-primary text-white px-6 py-2 rounded-lg font-medium">
                            <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative py-24 overflow-hidden">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 text-center">
            <h1 class="text-5xl md:text-6xl font-black text-white mb-6 leading-tight">
                Multiplayer API for<br>
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-purple-400 to-pink-500">Game Developers</span>
            </h1>
            <p class="text-xl text-white/90 max-w-3xl mx-auto mb-10">
                A scalable, secure REST API to connect any game to a shared multiplayer backend. 
                Manage users, project keys, JSON game states, matchmaking, and RTS sessions — all from one unified interface.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="#api-structure" class="btn-primary text-white px-8 py-4 rounded-xl text-lg font-semibold">
                    <i class="fas fa-code mr-2"></i>View API Docs
                </a>
                <a href="#sdk" class="glass-effect text-white px-8 py-4 rounded-xl text-lg font-semibold hover:bg-white/20 transition">
                    <i class="fas fa-rocket mr-2"></i>Get Started
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h3 class="text-4xl font-black text-white mb-6">Powerful Features</h3>
                <p class="text-xl text-white/70 max-w-3xl mx-auto">Everything you need to build, deploy, and scale multiplayer games</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="glass-effect p-8 rounded-2xl hover:transform hover:scale-105 transition-transform">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-key text-2xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold text-white mb-3 text-center">Per-Game API Keys</h4>
                    <p class="text-white/80 text-center">Each project gets a unique key for secure access and complete data isolation between games.</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="glass-effect p-8 rounded-2xl hover:transform hover:scale-105 transition-transform">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-r from-blue-500 to-cyan-400 flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-users text-2xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold text-white mb-3 text-center">Shared User Base</h4>
                    <p class="text-white/80 text-center">Players register once and can access multiple games with a single account.</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="glass-effect p-8 rounded-2xl hover:transform hover:scale-105 transition-transform">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-r from-green-400 to-blue-500 flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-gamepad text-2xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold text-white mb-3 text-center">Multiplayer Logic</h4>
                    <p class="text-white/80 text-center">Built-in support for matchmaking, real-time game sessions, and player state management.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- API Structure Section -->
    <section id="api-structure" class="py-16 bg-gradient-to-b from-black/20 to-transparent">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h3 class="text-4xl font-black text-white mb-6">API Endpoints</h3>
                <p class="text-xl text-white/70 max-w-3xl mx-auto">Comprehensive REST API for multiplayer game development</p>
            </div>
            
            <div class="glass-effect rounded-2xl overflow-hidden">
                <div class="grid grid-cols-12 bg-white/5 border-b border-white/10 p-4 text-white/80 font-medium">
                    <div class="col-span-2">Method</div>
                    <div class="col-span-4">Endpoint</div>
                    <div class="col-span-6">Description</div>
                </div>
                
                <!-- Game Players -->
                <div class="p-4">
                    <h4 class="text-white/60 text-sm font-semibold mb-3">Game Players</h4>
                    <div class="space-y-4">
                        <div class="grid grid-cols-12 items-center">
                            <div class="col-span-2"><span class="inline-block bg-green-500/20 text-green-400 text-xs px-2 py-1 rounded">POST</span></div>
                            <div class="col-span-4 font-mono text-white/90">/php/game_players.php</div>
                            <div class="col-span-6">Register new Player with API key, returns Player private key</div>
                        </div>
                        <div class="grid grid-cols-12 items-center">
                            <div class="col-span-2"><span class="inline-block bg-purple-500/20 text-purple-400 text-xs px-2 py-1 rounded">PUT</span></div>
                            <div class="col-span-4 font-mono text-white/90">/php/game_players.php</div>
                            <div class="col-span-6">Authenticate Player with API key and Player private key</div>
                        </div>
                        <div class="grid grid-cols-12 items-center">
                            <div class="col-span-2"><span class="inline-block bg-blue-500/20 text-blue-400 text-xs px-2 py-1 rounded">GET</span></div>
                            <div class="col-span-4 font-mono text-white/90">/php/game_players.php</div>
                            <div class="col-span-6">List all Players with API key and API private key</div>
                        </div>
                    </div>
                </div>

                <!-- Game Data -->
                <div class="p-4 border-t border-white/10">
                    <h4 class="text-white/60 text-sm font-semibold mb-3">Game Data</h4>
                    <div class="space-y-4">
                        <div class="grid grid-cols-12 items-center">
                            <div class="col-span-2"><span class="inline-block bg-blue-500/20 text-blue-400 text-xs px-2 py-1 rounded">GET</span></div>
                            <div class="col-span-4 font-mono text-white/90">/php/game_data.php</div>
                            <div class="col-span-6">Get game data (requires API key)</div>
                        </div>
                        <div class="grid grid-cols-12 items-center">
                            <div class="col-span-2"><span class="inline-block bg-purple-500/20 text-purple-400 text-xs px-2 py-1 rounded">PUT</span></div>
                            <div class="col-span-4 font-mono text-white/90">/php/game_data.php</div>
                            <div class="col-span-6">Update game data (requires API key)</div>
                        </div>
                        <div class="grid grid-cols-12 items-center">
                            <div class="col-span-2"><span class="inline-block bg-blue-500/20 text-blue-400 text-xs px-2 py-1 rounded">GET</span></div>
                            <div class="col-span-4 font-mono text-white/90">/php/game_data.php</div>
                            <div class="col-span-6">Get player data (requires private key)</div>
                        </div>
                        <div class="grid grid-cols-12 items-center">
                            <div class="col-span-2"><span class="inline-block bg-purple-500/20 text-purple-400 text-xs px-2 py-1 rounded">PUT</span></div>
                            <div class="col-span-4 font-mono text-white/90">/php/game_data.php</div>
                            <div class="col-span-6">Update player data (requires private key)</div>
                        </div>
                    </div>
                </div>

                <!-- Server Data -->
                <div class="p-4 border-t border-white/10">
                    <h4 class="text-white/60 text-sm font-semibold mb-3">Server Data</h4>
                    <div class="space-y-4">
                        <div class="grid grid-cols-12 items-center">
                            <div class="col-span-2"><span class="inline-block bg-blue-500/20 text-blue-400 text-xs px-2 py-1 rounded">GET</span></div>
                            <div class="col-span-4 font-mono text-white/90">/php/time.php</div>
                            <div class="col-span-6">Get server time</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SDK Section -->
    <section id="sdk" class="py-20">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h3 class="text-4xl font-black text-white mb-6">SDK for Developers</h3>
                <p class="text-xl text-white/70 max-w-3xl mx-auto">Integrate our API into your game with our easy-to-use SDKs</p>
            </div>
            
            <div class="max-w-3xl mx-auto space-y-8">
                <!-- C# / Unity SDK -->
                <div class="glass-effect p-8 rounded-2xl">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-600 to-blue-600 flex items-center justify-center mr-4">
                            <i class="fab fa-unity text-2xl text-white"></i>
                        </div>
                        <h4 class="text-xl font-bold text-white">C# / Unity SDK</h4>
                    </div>
                    <p class="text-white/80 mb-6">
                        Seamlessly integrate the API into your Unity projects with our C# SDK. 
                        Handles authentication, HTTP requests, JSON parsing, and project key management.
                    </p>
                    <div class="flex justify-end mb-4 space-x-4">
                        <a href="#" id="downloadSdk" class="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 flex items-center">
                            <i class="fas fa-download mr-2"></i> Download C# SDK
                        </a>
                        <a href="#" id="downloadExample" class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-medium py-2 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 flex items-center">
                            <i class="fas fa-code mr-2"></i> Download Example
                        </a>
                    </div>
                    <div class="relative">
                        <pre><code class="language-csharp"><?php echo htmlspecialchars(file_get_contents('SDK.cs')); ?></code></pre>
                    </div>
                    <script>
                    document.getElementById('downloadSdk').addEventListener('click', () => {
                    const link = document.createElement('a');
                    link.href = 'SDK.cs';           // ← path relative to your HTML file
                    link.download = 'SDK.cs';        // suggested filename
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
                
                document.getElementById('downloadExample').addEventListener('click', () => {
                    const link = document.createElement('a');
                    link.href = 'Game.cs';          // ← path relative to your HTML file
                    link.download = 'Game.cs';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
                    </script>
                </div>

                <!-- Example Usage -->
                <div class="glass-effect p-8 rounded-2xl mt-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-600 to-orange-600 flex items-center justify-center mr-4">
                            <i class="fas fa-code-branch text-2xl text-white"></i>
                        </div>
                        <h4 class="text-xl font-bold text-white">Example Usage</h4>
                    </div>
                    <p class="text-white/80 mb-6">
                        Here's a complete example of how to use the michitai SDK in your C# application.
                        This example demonstrates common operations like fetching game data, updating player information,
                        and handling authentication.
                    </p>
                    
                    <div class="relative mt-8">
                        <pre><code class="language-csharp"><?php echo htmlspecialchars(file_get_contents('Game.cs')); ?></code></pre>
                    </div>
                    
                    <div class="mt-6 p-4 bg-blue-900/20 rounded-lg border border-blue-800/50">
                        <h5 class="text-blue-300 font-medium mb-2 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i> How to use this example
                        </h5>
                        <ol class="text-blue-100/80 text-sm space-y-2 list-decimal list-inside">
                            <li>Create a new C# console application in Visual Studio or your preferred IDE</li>
                            <li>Add the downloaded <code class="bg-blue-900/50 px-1 py-0.5 rounded">SDK.cs</code> file to your project</li>
                            <li>Copy this example code into your <code class="bg-blue-900/50 px-1 py-0.5 rounded">Program.cs</code> file</li>
                            <li>Replace <code class="bg-blue-900/50 px-1.5 py-0.5 rounded">your-api-key-here</code> with your actual API key</li>
                            <li>Run the application to see the SDK in action</li>
                        </ol>
                    </div>
                </div>
                
                <!-- REST API -->
                <div class="glass-effect p-8 rounded-2xl mt-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-600 to-blue-600 flex items-center justify-center mr-4">
                            <i class="fas fa-code text-2xl text-white"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-white">Multiplayer API <span class="font-light">– Core Cells</span></h1>
                    </div>
                    <p class="text-white/80 mb-6">
                        Use our REST API directly from any platform or language. 
                        All endpoints return JSON responses with consistent error handling.
                    </p>


<div class="space-y-4">

    <!-- 1. Register Player -->
    <div class="bg-black/50 p-4 rounded-lg">
        <div class="flex items-center text-sm text-green-400 mb-2">
            <span class="font-mono bg-green-900/50 px-2 py-1 rounded mr-2">POST</span>
            <span class="font-mono">/v1/php/game_players.php?api_token=YOUR_API_KEY</span>
        </div>
        <p class="text-xs text-gray-400 mb-2">
            <strong>Description:</strong> Creates a new player in the game. Returns a <code>player_id</code> and <code>private_key</code> needed for future requests.
        </p>
        <div class="text-xs text-gray-300 font-medium mb-2">Request Body:</div>
        <pre class="text-sm mb-4"><code class="language-json">{
  "player_name": "TestPlayer",
  "player_data": {
    "level": 1,
    "score": 0,
    "inventory": ["sword","shield"]
  }
}</code></pre>
        <div class="text-xs text-gray-400 mb-2">Response:</div>
        <pre class="text-xs text-gray-300 overflow-x-auto">{
  "success": true,
  "player_id": "7",
  "private_key": "46702c9b906e3361c26dbcd605ee9183",
  "player_name": "TestPlayer",
  "game_id": 4
}</code></pre>
    </div>

    <!-- 2. Update Player Info -->
    <div class="bg-black/50 p-4 rounded-lg">
        <div class="flex items-center text-sm text-purple-400 mb-2">
            <span class="font-mono bg-purple-900/50 px-2 py-1 rounded mr-2">PUT</span>
            <span class="font-mono">/v1/php/game_players.php?api_token=YOUR_API_KEY&game_player_token=PLAYER_PRIVATE_KEY</span>
        </div>
        <p class="text-xs text-gray-400 mb-2">
            <strong>Description:</strong> Updates player info such as active status. This does not change player data like level or inventory (those are in <code>/game_data.php</code>).
        </p>
        <div class="text-xs text-gray-300 font-medium mb-2">Request Body:</div>
        <pre class="text-sm mb-4"><code class="language-json">{}</code></pre>
        <div class="text-xs text-gray-400 mb-2">Response:</div>
        <pre class="text-xs text-gray-300 overflow-x-auto">{
  "success": true,
  "player": {
    "id": 7,
    "game_id": 4,
    "player_name": "TestPlayer",
    "player_data": {
      "level": 1,
      "score": 0,
      "inventory": ["sword","shield"]
    },
    "is_active": 1,
    "last_login": null,
    "created_at": "2026-01-13 14:21:16",
    "updated_at": "2026-01-13 14:21:16"
  }
}</code></pre>
    </div>

    <!-- 3. List Players -->
    <div class="bg-black/50 p-4 rounded-lg">
        <div class="flex items-center text-sm text-blue-400 mb-2">
            <span class="font-mono bg-blue-900/50 px-2 py-1 rounded mr-2">GET</span>
            <span class="font-mono">/v1/php/game_players.php?api_token=YOUR_API_KEY</span>
        </div>
        <p class="text-xs text-gray-400 mb-2">
            <strong>Description:</strong> Retrieves a list of all players in the game. Useful for admin dashboards or multiplayer matchmaking.
        </p>
        <div class="text-xs text-gray-300 font-medium mb-2">Response:</div>
        <pre class="text-sm"><code class="language-json">{
  "success": true,
  "count": 7,
  "players": [
    {"id":3,"player_name":"TestPlayer","is_active":1,"last_login":null,"created_at":"2026-01-13 12:30:47"},
    {"id":7,"player_name":"TestPlayer","is_active":1,"last_login":"2026-01-13 14:22:33","created_at":"2026-01-13 14:21:16"}
}</code></pre>
    </div>

    <!-- 4. Get Game Data -->
    <div class="bg-black/50 p-4 rounded-lg">
        <div class="flex items-center text-sm text-blue-400 mb-2">
            <span class="font-mono bg-blue-900/50 px-2 py-1 rounded mr-2">GET</span>
            <span class="font-mono">/v1/php/game_data.php?api_token=YOUR_API_KEY</span>
        </div>
        <p class="text-xs text-gray-400 mb-2">
            <strong>Description:</strong> Retrieves the global game data, including text, settings, and last update timestamp. Used to sync clients with the server.
        </p>
        <div class="text-xs text-gray-300 font-medium mb-2">Response:</div>
        <pre class="text-sm"><code class="language-json">{
  "success": true,
  "type": "game",
  "game_id": 4,
  "data": {
    "text": "hello world",
    "game_settings": {
      "difficulty": "hard",
      "max_players": 10
    },
    "last_updated": "2025-01-13T12:00:00Z"
  }
}</code></pre>
    </div>

    <!-- 5. Update Game Data -->
    <div class="bg-black/50 p-4 rounded-lg">
        <div class="flex items-center text-sm text-purple-400 mb-2">
            <span class="font-mono bg-purple-900/50 px-2 py-1 rounded mr-2">PUT</span>
            <span class="font-mono">/v1/php/game_data.php?api_token=YOUR_API_KEY</span>
        </div>
        <p class="text-xs text-gray-400 mb-2">
            <strong>Description:</strong> Updates global game data. For example, changing settings or max players. Requires API key authentication.
        </p>
        <div class="text-xs text-gray-300 font-medium mb-2">Request Body:</div>
        <pre class="text-sm mb-4"><code class="language-json">{
  "game_settings": {
    "difficulty": "hard",
    "max_players": 10
  },
  "last_updated": "2025-01-13T12:00:00Z"
}</code></pre>
        <div class="text-xs text-gray-400 mb-2">Response:</div>
        <pre class="text-xs text-gray-300 overflow-x-auto">{
  "success": true,
  "message": "Game data updated successfully",
  "updated_at": "2026-01-13 14:24:23"
}</code></pre>
    </div>

    <!-- 6. Get Player Data -->
    <div class="bg-black/50 p-4 rounded-lg">
        <div class="flex items-center text-sm text-blue-400 mb-2">
            <span class="font-mono bg-blue-900/50 px-2 py-1 rounded mr-2">GET</span>
            <span class="font-mono">/v1/php/game_data.php?api_token=YOUR_API_KEY&game_player_token=PLAYER_PRIVATE_KEY</span>
        </div>
        <p class="text-xs text-gray-400 mb-2">
            <strong>Description:</strong> Retrieves a specific player's data using their <code>private_key</code>. Includes level, score, and inventory.
        </p>
        <div class="text-xs text-gray-300 font-medium mb-2">Response:</div>
        <pre class="text-sm"><code class="language-json">{
  "success": true,
  "type": "player",
  "player_id": 7,
  "player_name": "TestPlayer",
  "data": {
    "level": 1,
    "score": 0,
    "inventory": ["sword","shield"]
  }
}</code></pre>
    </div>

    <!-- 7. Update Player Data -->
    <div class="bg-black/50 p-4 rounded-lg">
        <div class="flex items-center text-sm text-purple-400 mb-2">
            <span class="font-mono bg-purple-900/50 px-2 py-1 rounded mr-2">PUT</span>
            <span class="font-mono">/v1/php/game_data.php?api_token=YOUR_API_KEY&game_player_token=PLAYER_PRIVATE_KEY</span>
        </div>
        <p class="text-xs text-gray-400 mb-2">
            <strong>Description:</strong> Updates a specific player's data like level, score, inventory, and last played timestamp.
        </p>
        <div class="text-xs text-gray-300 font-medium mb-2">Request Body:</div>
        <pre class="text-sm mb-4"><code class="language-json">{
  "level": 2,
  "score": 100,
  "inventory": ["sword","shield","potion"],
  "last_played": "2025-01-13T12:30:00Z"
}</code></pre>
        <div class="text-xs text-gray-400 mb-2">Response:</div>
        <pre class="text-xs text-gray-300 overflow-x-auto">{
  "success": true,
  "message": "Player data updated successfully",
  "updated_at": "2026-01-13 14:27:10"
}</code></pre>
    </div>

    <!-- 8. Get Server Time -->
    <div class="bg-black/50 p-4 rounded-lg">
        <div class="flex items-center text-sm text-green-400 mb-2">
            <span class="font-mono bg-green-900/50 px-2 py-1 rounded mr-2">GET</span>
            <span class="font-mono">/v1/php/time.php?api_key=YOUR_API_KEY</span>
        </div>
        <p class="text-xs text-gray-400 mb-2">
            <strong>Description:</strong> Retrieves the current server time in multiple formats including UTC timestamp and human-readable format.
        </p>
        <div class="text-xs text-gray-300 font-medium mb-2">Response:</div>
        <pre class="text-sm"><code class="language-json">{
  "success": true,
  "utc": "2025-01-14T16:24:00+00:00",
  "timestamp": 1736864640,
  "readable": "2025-01-14 16:24:00 UTC"
}</code></pre>
    </div>

    <!-- 9. Error Response -->
    <div class="bg-red-900/20 border border-red-500/30 p-4 rounded-lg">
        <div class="text-sm text-red-400 mb-2">Error Response (401 Unauthorized):</div>
        <p class="text-xs text-red-400 mb-2">
            <strong>Description:</strong> Shows what happens when a request is sent with an invalid or missing API key.
        </p>
        <pre class="text-xs text-red-300 overflow-x-auto">{
  "success": false,
  "error": {
    "code": "unauthorized",
    "message": "Invalid or missing API key"
  }
}</code></pre>
    </div>

</div>


                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="glass-effect border-t border-white/10 mt-16">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 text-center">
            <div class="text-white/60 text-sm">
                &copy; 2026 Nichita Levandovici. All rights reserved.
            </div>
        </div>
    </footer>
    
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.floating-card, .glass-effect').forEach((el) => {
            el.classList.add('opacity-0', 'transition-opacity', 'duration-500');
            observer.observe(el);
        });
    </script>
    
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        /* Syntax highlighting for code blocks */
        pre code.hljs {
            background: #1a1a2e;
            border-radius: 0.5rem;
            padding: 1.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</body>
</html>