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
                    <div class="flex justify-end mb-4 space-x-4">
                        <a href="#" id="downloadSdk" class="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 flex items-center">
                            <i class="fas fa-download mr-2"></i> Download C# SDK
                        </a>
                        <a href="#" id="downloadExample" class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-medium py-2 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 flex items-center">
                            <i class="fas fa-code mr-2"></i> Download Example
                        </a>
                    </div>
                    <div class="bg-black/50 p-4 rounded-lg mb-6 overflow-x-auto">
                        <pre><code class="language-csharp">
using System;
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
        private readonly string _apiKey;
        
        /// <summary>
        /// Event raised when authentication is required
        /// </summary>
        public event Action OnAuthenticationRequired;

        /// <summary>
        /// Initialize a new instance of the MichitaiClient with API key authentication
        /// </summary>
        /// <param name="apiKey">Your Michitai API key</param>
        /// <exception cref="ArgumentException">Thrown when API key is null or empty</exception>
        public MichitaiClient(string apiKey)
        {
            if (string.IsNullOrEmpty(apiKey))
                throw new ArgumentException("API key is required", nameof(apiKey));

            _apiKey = apiKey;
            _httpClient = new HttpClient();
            _httpClient.DefaultRequestHeaders.Accept.Add(
                new MediaTypeWithQualityHeaderValue("application/json"));
            _httpClient.DefaultRequestHeaders.Add("X-Requested-With", "XMLHttpRequest");
            _httpClient.DefaultRequestHeaders.Add("X-API-Key", _apiKey);
        }

        #region Game Data

        /// <summary>
        /// Get game data
        /// </summary>
        /// <returns>Game data</returns>
        public async Task<GameData> GetGameDataAsync()
        {
            return await GetAsync<GameData>("/games/data");
        }

        /// <summary>
        /// Update game data (admin only)
        /// </summary>
        /// <param name="gameData">Updated game data</param>
        /// <returns>Updated game data</returns>
        public async Task<GameData> UpdateGameDataAsync(GameData gameData)
        {
            return await PutAsync<GameData>("/games/data", gameData);
        }

        #endregion

        #region Player Data

        /// <summary>
        /// Get current player's data
        /// </summary>
        /// <returns>Player data</returns>
        public async Task<PlayerData> GetPlayerDataAsync()
        {
            return await GetAsync<PlayerData>("/games/players/data");
        }

        /// <summary>
        /// Update current player's data
        /// </summary>
        /// <param name="playerData">Updated player data</param>
        /// <returns>Updated player data</returns>
        public async Task<PlayerData> UpdatePlayerDataAsync(PlayerData playerData)
        {
            return await PutAsync<PlayerData>("/games/players/data", playerData);
        }

        /// <summary>
        /// List all players (admin only)
        /// </summary>
        /// <returns>List of players</returns>
        public async Task<List<PlayerInfo>> ListPlayersAsync()
        {
            return await GetAsync<List<PlayerInfo>>("/games/players");
        }

        #endregion

        #region HTTP Methods

        private async Task<T> GetAsync<T>(string endpoint)
        {
            try
            {
                var url = endpoint.StartsWith("http") ? endpoint : $"{API_BASE_URL}/{endpoint.TrimStart('/')}";
                var response = await _httpClient.GetAsync(url);
                await HandleResponse(response);
                var content = await response.Content.ReadAsStringAsync();
                return JsonSerializer.Deserialize<T>(content);
            }
            catch (HttpRequestException ex)
            {
                throw new MichitaiException("Network error occurred", ex);
            }
        }

        private async Task<T> PostAsync<T>(string endpoint, object data)
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
                return JsonSerializer.Deserialize<T>(responseContent);
            }
            catch (HttpRequestException ex)
            {
                throw new MichitaiException("Network error occurred", ex);
            }
        }

        private async Task<T> PutAsync<T>(string endpoint, object data)
        {
            try
            {
                var content = new StringContent(
                    JsonSerializer.Serialize(data),
                    Encoding.UTF8,
                    "application/json"
                );

                var url = endpoint.StartsWith("http") ? endpoint : $"{API_BASE_URL}/{endpoint.TrimStart('/')}";
                var response = await _httpClient.PutAsync(url, content);
                await HandleResponse(response);
                var responseContent = await response.Content.ReadAsStringAsync();
                return JsonSerializer.Deserialize<T>(responseContent);
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

    public class GameData
    {
        public int Id { get; set; }
        public string Name { get; set; }
        public string JsonStructure { get; set; }
        public DateTime CreatedAt { get; set; }
        public DateTime? UpdatedAt { get; set; }
    }

    public class PlayerData
    {
        public int Id { get; set; } = 0;
        public string Username { get; set; } = string.Empty;
        public string Email { get; set; } = string.Empty;
        public string JsonData { get; set; } = string.Empty;
        public int Level { get; set; } = 1;
        public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
        public DateTime? LastLogin { get; set; }
    }

    public class PlayerInfo
    {
        public string PlayerId { get; set; }
        public string Username { get; set; }
        public string Email { get; set; }
        public DateTime CreatedAt { get; set; }
        public DateTime? LastLogin { get; set; }
    }

    #endregion

    #region Exceptions

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
}
</code></pre>
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
        private readonly string _apiKey;

        /// <summary>
        /// Initialize a new instance of the MichitaiClient with API key authentication
        /// </summary>
        /// <param name="apiKey">Your Michitai API key</param>
        /// <exception cref="ArgumentException">Thrown when API key is null or empty</exception>
        public MichitaiClient(string apiKey)
        {
            if (string.IsNullOrEmpty(apiKey))
                throw new ArgumentException("API key is required", nameof(apiKey));

            _apiKey = apiKey;
            _httpClient = new HttpClient();
            _httpClient.DefaultRequestHeaders.Accept.Add(
                new MediaTypeWithQualityHeaderValue("application/json"));
            _httpClient.DefaultRequestHeaders.Add("X-Requested-With", "XMLHttpRequest");
            _httpClient.DefaultRequestHeaders.Add("X-API-Key", _apiKey);
        }

        #region Game Data

        /// <summary>
        /// Get game data
        /// </summary>
        /// <returns>Game data</returns>
        public async Task<GameData> GetGameDataAsync()
        {
            return await GetAsync<GameData>("/games/data");
        }

        /// <summary>
        /// Update game data (admin only)
        /// </summary>
        /// <param name="gameData">Updated game data</param>
        /// <returns>Updated game data</returns>
        public async Task<GameData> UpdateGameDataAsync(GameData gameData)
        {
            return await PutAsync<GameData>("/games/data", gameData);
        }

        #endregion

        #region Player Data

        /// <summary>
        /// Get current player's data
        /// </summary>
        /// <returns>Player data</returns>
        public async Task<PlayerData> GetPlayerDataAsync()
        {
            return await GetAsync<PlayerData>("/games/players/data");
        }

        /// <summary>
        /// Update current player's data
        /// </summary>
        /// <param name="playerData">Updated player data</param>
        /// <returns>Updated player data</returns>
        public async Task<PlayerData> UpdatePlayerDataAsync(PlayerData playerData)
        {
            return await PutAsync<PlayerData>("/games/players/data", playerData);
        }

        /// <summary>
        /// List all players (admin only)
        /// </summary>
        /// <returns>List of players</returns>
        public async Task<List<PlayerInfo>> ListPlayersAsync()
        {
            return await GetAsync<List<PlayerInfo>>("/games/players");
        }

        #endregion

        #region HTTP Methods

        private async Task<T> GetAsync<T>(string endpoint)
        {
            try
            {
                var url = endpoint.StartsWith("http") ? endpoint : $"{API_BASE_URL}/{endpoint.TrimStart('/')}";
                var response = await _httpClient.GetAsync(url);
                await HandleResponse(response);
                var content = await response.Content.ReadAsStringAsync();
                return JsonSerializer.Deserialize<T>(content);
            }
            catch (HttpRequestException ex)
            {
                throw new MichitaiException("Network error occurred", ex);
            }
        }

        private async Task<T> PostAsync<T>(string endpoint, object data)
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
                return JsonSerializer.Deserialize<T>(responseContent);
            }
            catch (HttpRequestException ex)
            {
                throw new MichitaiException("Network error occurred", ex);
            }
        }

        private async Task<T> PutAsync<T>(string endpoint, object data)
        {
            try
            {
                var content = new StringContent(
                    JsonSerializer.Serialize(data),
                    Encoding.UTF8,
                    "application/json"
                );

                var url = endpoint.StartsWith("http") ? endpoint : $"{API_BASE_URL}/{endpoint.TrimStart('/')}";
                var response = await _httpClient.PutAsync(url, content);
                await HandleResponse(response);
                var responseContent = await response.Content.ReadAsStringAsync();
                return JsonSerializer.Deserialize<T>(responseContent);
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

        /// <summary>
        /// Event triggered when authentication is required
        /// </summary>
        public event Action OnAuthenticationRequired;
    }

    #region Data Models

    public class GameData
    {
        public int Id { get; set; }
        public string Name { get; set; }
        public string JsonStructure { get; set; }
        public DateTime CreatedAt { get; set; }
        public DateTime? UpdatedAt { get; set; }
    }

    public class PlayerData
    {
        public int Id { get; set; } = 0;
        public string Username { get; set; } = string.Empty;
        public string Email { get; set; } = string.Empty;
        public string JsonData { get; set; } = string.Empty;
        public int Level { get; set; } = 1;
        public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
        public DateTime? LastLogin { get; set; }
    }

    public class PlayerInfo
    {
        public string PlayerId { get; set; }
        public string Username { get; set; }
        public string Email { get; set; }
        public DateTime CreatedAt { get; set; }
        public DateTime? LastLogin { get; set; }
    }

    #endregion

    #region Exceptions

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

                    // Add event listener for downloading the example
                    document.getElementById('downloadExample').addEventListener('click', function(e) {
                        e.preventDefault();
                        const exampleCode = `using System;
using System.Threading.Tasks;

namespace Michitai.Example
{
    /// <summary>
    /// Example class demonstrating how to use the Michitai SDK
    #region Example Usage
    public class Game
    {
        private readonly Michitai.SDK.MichitaiClient _apiClient;

        public Game(string apiKey)
        {
            if (string.IsNullOrEmpty(apiKey))
                throw new ArgumentException("API key cannot be null or empty", nameof(apiKey));
        public async Task RunGameExample()
        {
            try
            {
                Console.WriteLine("Fetching game data...");
                var gameData = await _apiClient.GetGameDataAsync();
                Console.WriteLine($"Game: {gameData.Name}, Last Updated: {gameData.UpdatedAt}");

                Console.WriteLine("\nFetching player data...");
                var playerData = await _apiClient.GetPlayerDataAsync();
                Console.WriteLine($"Player: {playerData.Username}, Level: {playerData.Level}");

                // Example of updating player data
                Console.WriteLine("\nUpdating player data...");
                playerData.Level += 1;
                playerData.LastLogin = DateTime.UtcNow;
                
                var updatedPlayer = await _apiClient.UpdatePlayerDataAsync(playerData);
                Console.WriteLine($"Player level updated to: {updatedPlayer.Level}");

                // Example of listing all players (admin only)
                Console.WriteLine("\nFetching all players...");
                var players = await _apiClient.ListPlayersAsync();
                foreach (var player in players)
                {
                    Console.WriteLine($"- {player.Username} (Last login: {player.LastLogin})");
                }
            }
            catch (Michitai.SDK.MichitaiException ex)
            {
                Console.WriteLine($"Error: {ex.Message}");
                if (!string.IsNullOrEmpty(ex.ResponseContent))
                {
                    Console.WriteLine($"Details: {ex.ResponseContent}");
                }
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Unexpected error: {ex.Message}");
            }
        }

        private void OnAuthenticationRequired()
        {
            Console.WriteLine("Authentication required! Please provide valid API credentials.");
            // Here you would typically show a login dialog or redirect to login page
        }

        /// <summary>
        /// Entry point for the example
        /// </summary>
        public static async Task Main(string[] args)
        {
            try
            {
                Console.WriteLine("Michitai Game Example");
                Console.WriteLine("=====================\n");

                // Get API key from command line arguments or use a default one
                string apiKey = args.Length > 0 ? args[0] : "your-api-key-here";
                
                if (apiKey == "your-api-key-here")
                {
                    Console.WriteLine("Warning: Using default API key. Please provide your own API key as a command line argument.");
                }
                
                var game = new Game(apiKey);
                await game.RunGameExample();
            }
            catch (Exception ex)
            {
                Console.WriteLine($"An error occurred: {ex.Message}");
            }
            
            Console.WriteLine("\nPress any key to exit...");
            Console.ReadKey();
        }
    }
}`;

                        const blob = new Blob([exampleCode], { type: 'text/plain' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'Game.cs';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
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
                        Here's a complete example of how to use the Michitai SDK in your C# application.
                        This example demonstrates common operations like fetching game data, updating player information,
                        and handling authentication.
                    </p>
                    
                    <div class="bg-black/50 p-4 rounded-lg mb-6 overflow-x-auto">
                        <pre><code class="language-csharp">using System;
using System.Threading.Tasks;

namespace Michitai.Example
{
    /// &lt;summary&gt;
    /// Example class demonstrating how to use the Michitai SDK
    /// &lt;/summary&gt;
    public class Game
    {
        private readonly Michitai.SDK.MichitaiClient _apiClient;

        public Game(string apiKey)
        {
            if (string.IsNullOrEmpty(apiKey))
                throw new ArgumentException("API key cannot be null or empty", nameof(apiKey));
                
            // Initialize the API client with your API key
            _apiClient = new Michitai.SDK.MichitaiClient(apiKey);
            
            // Subscribe to authentication events
            _apiClient.OnAuthenticationRequired += OnAuthenticationRequired!;
        }

        /// &lt;summary&gt;
        /// Example method to demonstrate game data operations
        /// &lt;/summary&gt;
        public async Task RunGameExample()
        {
            try
            {
                Console.WriteLine("Fetching game data...");
                var gameData = await _apiClient.GetGameDataAsync();
                Console.WriteLine($"Game: {gameData.Name}, Last Updated: {gameData.UpdatedAt}");

                Console.WriteLine("\nFetching player data...");
                var playerData = await _apiClient.GetPlayerDataAsync();
                Console.WriteLine($"Player: {playerData.Username}, Level: {playerData.Level}");

                // Example of updating player data
                Console.WriteLine("\nUpdating player data...");
                playerData.Level += 1;
                playerData.LastLogin = DateTime.UtcNow;
                
                var updatedPlayer = await _apiClient.UpdatePlayerDataAsync(playerData);
                Console.WriteLine($"Player level updated to: {updatedPlayer.Level}");

                // Example of listing all players (admin only)
                Console.WriteLine("\nFetching all players...");
                var players = await _apiClient.ListPlayersAsync();
                foreach (var player in players)
                {
                    Console.WriteLine($"- {player.Username} (Last login: {player.LastLogin})");
                }
            }
            catch (Michitai.SDK.MichitaiException ex)
            {
                Console.WriteLine($"Error: {ex.Message}");
                if (!string.IsNullOrEmpty(ex.ResponseContent))
                {
                    Console.WriteLine($"Details: {ex.ResponseContent}");
                }
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Unexpected error: {ex.Message}");
            }
        }

        private void OnAuthenticationRequired()
        {
            Console.WriteLine("Authentication required! Please provide valid API credentials.");
            // Here you would typically show a login dialog or redirect to login page
        }

        /// &lt;summary&gt;
        /// Entry point for the example
        /// &lt;/summary&gt;
        public static async Task Main(string[] args)
        {
            try
            {
                Console.WriteLine("Michitai Game Example");
                Console.WriteLine("=====================\n");

                // Get API key from command line arguments or use a default one
                string apiKey = args.Length > 0 ? args[0] : "your-api-key-here";
                
                if (apiKey == "your-api-key-here")
                {
                    Console.WriteLine("Warning: Using default API key. Please provide your own API key as a command line argument.");
                }
                
                var game = new Game(apiKey);
                await game.RunGameExample();
            }
            catch (Exception ex)
            {
                Console.WriteLine($"An error occurred: {ex.Message}");
            }
            
            Console.WriteLine("\nPress any key to exit...");
            Console.ReadKey();
        }
    }
}</code></pre>
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