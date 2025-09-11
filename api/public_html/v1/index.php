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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .glass-effect {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
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
                            <div class="col-span-4 font-mono text-white/90">/api/games/players</div>
                            <div class="col-span-6">Register new player for a game</div>
                        </div>
                        <div class="grid grid-cols-12 items-center">
                            <div class="col-span-2"><span class="inline-block bg-green-500/20 text-green-400 text-xs px-2 py-1 rounded">POST</span></div>
                            <div class="col-span-4 font-mono text-white/90">/api/games/players/login</div>
                            <div class="col-span-6">Authenticate player with private key</div>
                        </div>
                        <div class="grid grid-cols-12 items-center">
                            <div class="col-span-2"><span class="inline-block bg-blue-500/20 text-blue-400 text-xs px-2 py-1 rounded">GET</span></div>
                            <div class="col-span-4 font-mono text-white/90">/api/games/players</div>
                            <div class="col-span-6">List all players for a game (admin only)</div>
                        </div>
                    </div>
                </div>

                <!-- Game Data -->
                <div class="p-4 border-t border-white/10">
                    <h4 class="text-white/60 text-sm font-semibold mb-3">Game Data</h4>
                    <div class="space-y-4">
                        <div class="grid grid-cols-12 items-center">
                            <div class="col-span-2"><span class="inline-block bg-blue-500/20 text-blue-400 text-xs px-2 py-1 rounded">GET</span></div>
                            <div class="col-span-4 font-mono text-white/90">/api/games/data</div>
                            <div class="col-span-6">Get game data (requires API key)</div>
                        </div>
                        <div class="grid grid-cols-12 items-center">
                            <div class="col-span-2"><span class="inline-block bg-purple-500/20 text-purple-400 text-xs px-2 py-1 rounded">PUT</span></div>
                            <div class="col-span-4 font-mono text-white/90">/api/games/data</div>
                            <div class="col-span-6">Update game data (requires API key)</div>
                        </div>
                        <div class="grid grid-cols-12 items-center">
                            <div class="col-span-2"><span class="inline-block bg-blue-500/20 text-blue-400 text-xs px-2 py-1 rounded">GET</span></div>
                            <div class="col-span-4 font-mono text-white/90">/api/games/players/data</div>
                            <div class="col-span-6">Get player data (requires private key)</div>
                        </div>
                        <div class="grid grid-cols-12 items-center">
                            <div class="col-span-2"><span class="inline-block bg-purple-500/20 text-purple-400 text-xs px-2 py-1 rounded">PUT</span></div>
                            <div class="col-span-4 font-mono text-white/90">/api/games/players/data</div>
                            <div class="col-span-6">Update player data (requires private key)</div>
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
                    <div class="flex justify-end mb-4">
                        <a href="#" id="downloadSdk" class="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 flex items-center">
                            <i class="fas fa-download mr-2"></i> Download C# SDK
                        </a>
                    </div>
                    <div class="bg-black/50 p-4 rounded-lg mb-6 overflow-x-auto">
                        <pre><code class="language-csharp">using System;
using System.Collections.Generic;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Threading.Tasks;

namespace Michitai.SDK
{
    /// &lt;summary&gt;
    /// Client for interacting with the Michitai Game Platform API
    /// &lt;/summary&gt;
    public class MichitaiClient
    {
        private readonly HttpClient _httpClient;
        private const string API_BASE_URL = "https://api.michitai.com/v1/php";
        private string _apiToken;
        private string _sessionToken;

        /// &lt;summary&gt;
        /// Event triggered when authentication is required
        /// &lt;/summary&gt;
        public event Action OnAuthenticationRequired;

        /// &lt;summary&gt;
        /// Indicates if the client is currently authenticated
        /// &lt;/summary&gt;
        public bool IsAuthenticated =&gt; !string.IsNullOrEmpty(_apiToken);

        /// &lt;summary&gt;
        /// Initialize a new instance of the MichitaiClient
        /// &lt;/summary&gt;
        public MichitaiClient()
        {
            _httpClient = new HttpClient();
            _httpClient.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));
            _httpClient.DefaultRequestHeaders.Add("X-Requested-With", "XMLHttpRequest");
        }

        #region Authentication

        /// &lt;summary&gt;
        /// Register a new player
        /// &lt;/summary&gt;
        public async Task&lt;PlayerRegistrationResponse&gt; RegisterPlayerAsync(string username, string email, string password)
        {
            var request = new { username, email, password };
            var response = await PostAsync&lt;PlayerRegistrationResponse&gt;("game_players.php?endpoint=register", request);
            
            if (response != null && !string.IsNullOrEmpty(response.ApiToken))
            {
                _apiToken = response.ApiToken;
                _sessionToken = response.SessionToken;
                UpdateAuthorizationHeader();
            }
            
            return response;
        }

        /// &lt;summary&gt;
        /// Authenticate a player
        /// &lt;/summary&gt;
        public async Task&lt;AuthResponse&gt; LoginAsync(string email, string password)
        {
            var request = new { email, password };
            var response = await PostAsync&lt;AuthResponse&gt;("login.php", request);
            
            if (response != null)
            {
                _apiToken = response.ApiToken;
                _sessionToken = response.SessionToken;
                UpdateAuthorizationHeader();
            }
            
            return response;
        }

        /// &lt;summary&gt;
        /// Set authentication tokens (useful for restoring session)
        /// &lt;/summary&gt;
        public void SetAuthTokens(string apiToken, string sessionToken)
        {
            _apiToken = apiToken;
            _sessionToken = sessionToken;
            UpdateAuthorizationHeader();
        }

        private void UpdateAuthorizationHeader()
        {
            if (!string.IsNullOrEmpty(_apiToken))
            {
                _httpClient.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Bearer", _apiToken);
                _httpClient.DefaultRequestHeaders.Add("X-API-Token", _apiToken);
            }
            else
            {
                _httpClient.DefaultRequestHeaders.Authorization = null;
                _httpClient.DefaultRequestHeaders.Remove("X-API-Token");
            }
        }

        #endregion

        #region Game Data

        /// &lt;summary&gt;
        /// Get game data
        /// &lt;/summary&gt;
        public async Task&lt;GameData&gt; GetGameDataAsync()
        {
            return await GetAsync&lt;GameData&gt;("game_data.php");
        }

        #endregion

        #region Player Data

        /// &lt;summary&gt;
        /// Get current player's data
        /// &lt;/summary&gt;
        public async Task&lt;PlayerData&gt; GetPlayerDataAsync()
        {
            return await GetAsync&lt;PlayerData&gt;("get_user_data.php");
        }

        #endregion

        #region HTTP Methods

        private async Task&lt;T&gt; GetAsync&lt;T&gt;(string endpoint)
        {
            try
            {
                var url = endpoint.StartsWith("http") ? endpoint : $"{API_BASE_URL}/{endpoint.TrimStart('/')}";
                var response = await _httpClient.GetAsync(url);
                await HandleResponse(response);
                var content = await response.Content.ReadAsStringAsync();
                return JsonSerializer.Deserialize&lt;T&gt;(content);
            }
            catch (HttpRequestException ex)
            {
                throw new MichitaiException("Network error occurred", ex);
            }
        }

        private async Task&lt;T&gt; PostAsync&lt;T&gt;(string endpoint, object data)
        {
            try
            {
                var content = new StringContent(
                    JsonSerializer.Serialize(data),
                    Encoding.UTF8,
                    "application/json"
                );

                var url = endpoint.StartsWith("http") ? endpoint : $"{API_BASE_URL}/{endpoint.TrimStart('/')}";
                var response = await _httpClient.PostAsync(url, content);
                await HandleResponse(response);
                var responseContent = await response.Content.ReadAsStringAsync();
                return JsonSerializer.Deserialize&lt;T&gt;(responseContent);
            }
            catch (HttpRequestException ex)
            {
                throw new MichitaiException("Network error occurred", ex);
            }
        }

        private async Task HandleResponse(HttpResponseMessage response)
        {
            if (!response.IsSuccessStatusCode)
            {
                var errorContent = await response.Content.ReadAsStringAsync();
                
                if (response.StatusCode == System.Net.HttpStatusCode.Unauthorized)
                {
                    OnAuthenticationRequired?.Invoke();
                    throw new MichitaiException("Authentication required", response.StatusCode, errorContent);
                }
                
                throw new MichitaiException("API request failed", response.StatusCode, errorContent);
            }
        }

        #endregion
    }

    #region Data Models

    public class PlayerRegistrationResponse
    {
        public string PlayerId { get; set; }
        public string Username { get; set; }
        public string Email { get; set; }
        public string ApiToken { get; set; }
        public string SessionToken { get; set; }
        public DateTime CreatedAt { get; set; }
    }

    public class AuthResponse
    {
        public string PlayerId { get; set; }
        public string Username { get; set; }
        public string Email { get; set; }
        public string ApiToken { get; set; }
        public string SessionToken { get; set; }
        public DateTime LastLogin { get; set; }
    }

    public class GameData
    {
        public string GameId { get; set; }
        public string Name { get; set; }
        public string Description { get; set; }
        public string Version { get; set; }
        public Dictionary&lt;string, object&gt; Settings { get; set; }
        public DateTime LastUpdated { get; set; }
    }

    public class PlayerData
    {
        public string PlayerId { get; set; }
        public string Username { get; set; }
        public string Email { get; set; }
        public int Level { get; set; }
        public int Experience { get; set; }
        public Dictionary&lt;string, object&gt; Stats { get; set; }
        public Dictionary&lt;string, object&gt; Inventory { get; set; }
        public DateTime LastActive { get; set; }
    }

    public class MichitaiException : Exception
    {
        public System.Net.HttpStatusCode StatusCode { get; }
        public string ResponseContent { get; }

        public MichitaiException(string message) : base(message) { }
        
        public MichitaiException(string message, Exception innerException) 
            : base(message, innerException) { }
            
        public MichitaiException(string message, System.Net.HttpStatusCode statusCode, string responseContent) 
            : base($"{message}. Status: {statusCode}, Response: {responseContent}")
        {
            StatusCode = statusCode;
            ResponseContent = responseContent;
        }
    }

    #endregion
}</code></pre>
                    </div>
                    <script>
                    document.getElementById('downloadSdk').addEventListener('click', function(e) {
                        e.preventDefault();
                        const sdkCode = `using System;
using System.Collections.Generic;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Threading.Tasks;

namespace Michitai.SDK
{
    /// <summary>
    /// Client for interacting with the Michitai Game Platform API
    /// </summary>
    public class MichitaiClient
    {
        private readonly HttpClient _httpClient;
        private const string API_BASE_URL = "https://api.michitai.com/v1/php";
        private string _apiToken;
        private string _sessionToken;

        public event Action OnAuthenticationRequired;
        public bool IsAuthenticated => !string.IsNullOrEmpty(_apiToken);

        public MichitaiClient()
        {
            _httpClient = new HttpClient();
            _httpClient.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));
            _httpClient.DefaultRequestHeaders.Add("X-Requested-With", "XMLHttpRequest");
        }

        public async Task<PlayerRegistrationResponse> RegisterPlayerAsync(string username, string email, string password)
        {
            var request = new { username, email, password };
            return await PostAsync<PlayerRegistrationResponse>("game_players.php?endpoint=register", request);
        }

        public async Task<AuthResponse> LoginAsync(string email, string password)
        {
            var request = new { email, password };
            return await PostAsync<AuthResponse>("login.php", request);
        }

        public async Task<GameData> GetGameDataAsync() => await GetAsync<GameData>("game_data.php");
        public async Task<PlayerData> GetPlayerDataAsync() => await GetAsync<PlayerData>("get_user_data.php");

        private async Task<T> GetAsync<T>(string endpoint)
        {
            var url = endpoint.StartsWith("http") ? endpoint : $"{API_BASE_URL}/{endpoint.TrimStart('/')}";
            var response = await _httpClient.GetAsync(url);
            response.EnsureSuccessStatusCode();
            var content = await response.Content.ReadAsStringAsync();
            return JsonSerializer.Deserialize<T>(content);
        }

        private async Task<T> PostAsync<T>(string endpoint, object data)
        {
            var content = new StringContent(
                JsonSerializer.Serialize(data),
                Encoding.UTF8,
                "application/json"
            );

            var url = endpoint.StartsWith("http") ? endpoint : $"{API_BASE_URL}/{endpoint.TrimStart('/')}";
            var response = await _httpClient.PostAsync(url, content);
            response.EnsureSuccessStatusCode();
            var responseContent = await response.Content.ReadAsStringAsync();
            return JsonSerializer.Deserialize<T>(responseContent);
        }
    }

    public class PlayerRegistrationResponse
    {
        public string PlayerId { get; set; }
        public string Username { get; set; }
        public string Email { get; set; }
        public string ApiToken { get; set; }
    }

    public class AuthResponse
    {
        public string PlayerId { get; set; }
        public string Username { get; set; }
        public string Email { get; set; }
        public string ApiToken { get; set; }
        public string SessionToken { get; set; }
    }

    public class GameData
    {
        public string GameId { get; set; }
        public Dictionary<string, object> Data { get; set; }
    }

    public class PlayerData
    {
        public string PlayerId { get; set; }
        public string Username { get; set; }
        public Dictionary<string, object> Data { get; set; }
    }
}`;

                        const blob = new Blob([sdkCode], { type: 'text/plain' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'SDK.cs';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    });
                    </script>
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
                        <!-- Register Player -->
                        <div class="bg-black/50 p-4 rounded-lg">
                            <div class="flex items-center text-sm text-green-400 mb-2">
                                <span class="font-mono bg-green-900/50 px-2 py-1 rounded mr-2">POST</span>
                                <span class="font-mono">/api/games/players</span>
                                <span class="ml-2 text-xs text-gray-400">(Requires API Key in header)</span>
                            </div>
                            <div class="text-xs text-gray-400 mb-2">Request:</div>
                            <pre class="text-xs text-gray-300 overflow-x-auto mb-3">{
  "player_name": "player123",
  "player_data": {
    "level": 1,
    "class": "warrior"
  }
}</pre>
                            <div class="text-xs text-gray-400 mb-2">Response (201 Created):</div>
                            <pre class="text-xs text-gray-300 overflow-x-auto">{
  "success": true,
  "data": {
    "player_id": 42,
    "private_key": "a1b2c3d4e5f6g7h8i9j0",
    "player_name": "player123",
    "game_id": 5
  }
}</pre>
                        </div>

                        <!-- Player Login -->
                        <div class="bg-black/50 p-4 rounded-lg">
                            <div class="flex items-center text-sm text-green-400 mb-2">
                                <span class="font-mono bg-green-900/50 px-2 py-1 rounded mr-2">POST</span>
                                <span class="font-mono">/api/games/players/login</span>
                            </div>
                            <div class="text-xs text-gray-400 mb-2">Request (with X-Private-Key header):</div>
                            <pre class="text-xs text-gray-300 overflow-x-auto mb-3">{}</pre>
                            <div class="text-xs text-gray-400 mb-2">Response (200 OK):</div>
                            <pre class="text-xs text-gray-300 overflow-x-auto">{
  "success": true,
  "data": {
    "player_id": 42,
    "player_name": "player123",
    "game_id": 5,
    "is_active": true,
    "player_data": {
      "level": 1,
      "class": "warrior"
    },
    "last_login": "2025-09-11 12:30:45"
  }
}</pre>
                        </div>

                        <!-- Get Game Data -->
                        <div class="bg-black/50 p-4 rounded-lg">
                            <div class="flex items-center text-sm text-blue-400 mb-2">
                                <span class="font-mono bg-blue-900/50 px-2 py-1 rounded mr-2">GET</span>
                                <span class="font-mono">/api/games/data</span>
                                <span class="ml-2 text-xs text-gray-400">(Requires API Key in header)</span>
                            </div>
                            <div class="text-xs text-gray-400 mb-2">Response (200 OK):</div>
                            <pre class="text-xs text-gray-300 overflow-x-auto">{
  "success": true,
  "game_id": 5,
  "data": {
    "name": "Epic Adventure",
    "version": "1.0.0",
    "max_players": 100,
    "game_state": "lobby",
    "settings": {
      "difficulty": "normal",
      "allow_pvp": true
    }
  }
}</pre>
                        </div>

                        <!-- Update Player Data -->
                        <div class="bg-black/50 p-4 rounded-lg">
                            <div class="flex items-center text-sm text-purple-400 mb-2">
                                <span class="font-mono bg-purple-900/50 px-2 py-1 rounded mr-2">PUT</span>
                                <span class="font-mono">/api/games/players/data</span>
                                <span class="ml-2 text-xs text-gray-400">(Requires Private Key in header)</span>
                            </div>
                            <div class="text-xs text-gray-400 mb-2">Request:</div>
                            <pre class="text-xs text-gray-300 overflow-x-auto mb-3">{
  "level": 2,
  "experience": 150,
  "inventory": ["sword", "potion"],
  "position": {"x": 100, "y": 200}
}</pre>
                            <div class="text-xs text-gray-400 mb-2">Response (200 OK):</div>
                            <pre class="text-xs text-gray-300 overflow-x-auto">{
  "success": true,
  "message": "Player data updated successfully"
}</pre>
                        </div>

                        <!-- Error Response Example -->
                        <div class="bg-red-900/20 border border-red-500/30 p-4 rounded-lg">
                            <div class="text-sm text-red-400 mb-2">Error Response (401 Unauthorized):</div>
                            <pre class="text-xs text-red-300 overflow-x-auto">{
  "success": false,
  "error": {
    "code": "unauthorized",
    "message": "Invalid or missing API key"
  }
}</pre>
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
                &copy; 2025 Nichita Levandovici. All rights reserved.
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