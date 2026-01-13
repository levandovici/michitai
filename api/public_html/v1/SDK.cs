using System;
using System.Collections.Generic;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Text.Json.Serialization;
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
        private string _gamePlayerToken;

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

            _apiKey = apiKey.Trim();
            
            // Configure HTTP client
            _httpClient = new HttpClient
            {
                BaseAddress = new Uri(API_BASE_URL)
            };
            
            // Set default headers
            _httpClient.DefaultRequestHeaders.Accept.Clear();
            _httpClient.DefaultRequestHeaders.Accept.Add(
                new MediaTypeWithQualityHeaderValue("application/json"));
        }

        #region Game Data

        /// <summary>
        /// Get game data
        /// </summary>
        /// <returns>Game data response</returns>
        public async Task<GameDataResponse> GetGameDataAsync()
        {
            var query = $"?api_token={_apiKey}";
            return await GetAsync<GameDataResponse>($"/game_data.php{query}");
        }

        /// <summary>
        /// Update game data
        /// </summary>
        /// <param name="data">Data to update</param>
        /// <returns>Update response</returns>
        public async Task<ApiResponse> UpdateGameDataAsync(Dictionary<string, object> data)
        {
            var query = $"?api_token={_apiKey}";
            return await PutAsync<ApiResponse>($"/game_data.php{query}", data);
        }

        #endregion

        #region Player Management

        /// <summary>
        /// Register a new player for the game
        /// </summary>
        /// <param name="playerName">Player's display name</param>
        /// <param name="playerData">Optional initial player data</param>
        /// <returns>Player registration response</returns>
        public async Task<PlayerRegistrationResponse> RegisterPlayerAsync(string playerName, Dictionary<string, object> playerData = null)
        {
            var query = $"?api_token={_apiKey}";
            var requestData = new Dictionary<string, object>
            {
                ["player_name"] = playerName
            };

            if (playerData != null)
            {
                requestData["player_data"] = playerData;
            }

            var response = await PostAsync<PlayerRegistrationResponse>($"/game_players.php{query}", requestData);
            
            // Store the player token for future requests
            if (response.Success && !string.IsNullOrEmpty(response.GamePlayerToken))
            {
                _gamePlayerToken = response.GamePlayerToken;
            }

            return response;
        }

        /// <summary>
        /// Authenticate a player with their game player token
        /// </summary>
        /// <param name="gamePlayerToken">The player's authentication token</param>
        /// <returns>Player data</returns>
        public async Task<PlayerDataResponse> AuthenticatePlayerAsync(string gamePlayerToken = null)
        {
            _gamePlayerToken = gamePlayerToken ?? _gamePlayerToken;
            
            if (string.IsNullOrEmpty(_gamePlayerToken))
            {
                throw new InvalidOperationException("Game player token is required. Call RegisterPlayerAsync first or provide a token.");
            }

            var query = $"?api_token={_apiKey}&game_player_token={_gamePlayerToken}";
            return await PutAsync<PlayerDataResponse>($"/game_players.php{query}", null);
        }

        /// <summary>
        /// Get player data (requires authentication)
        /// </summary>
        /// <returns>Player data</returns>
        public async Task<PlayerDataResponse> GetPlayerDataAsync()
        {
            if (string.IsNullOrEmpty(_gamePlayerToken))
            {
                throw new InvalidOperationException("Player is not authenticated. Call AuthenticatePlayerAsync first.");
            }

            var query = $"?api_token={_apiKey}&game_player_token={_gamePlayerToken}";
            return await GetAsync<PlayerDataResponse>($"/game_data.php{query}");
        }

        /// <summary>
        /// Update player data
        /// </summary>
        /// <param name="data">Data to update</param>
        /// <returns>Update response</returns>
        public async Task<ApiResponse> UpdatePlayerDataAsync(Dictionary<string, object> data)
        {
            if (string.IsNullOrEmpty(_gamePlayerToken))
            {
                throw new InvalidOperationException("Player is not authenticated. Call AuthenticatePlayerAsync first.");
            }

            var query = $"?api_token={_apiKey}&game_player_token={_gamePlayerToken}";
            return await PutAsync<ApiResponse>($"/game_data.php{query}", data);
        }

        /// <summary>
        /// List all players (admin only)
        /// </summary>
        /// <returns>List of players</returns>
        public async Task<PlayerListResponse> ListPlayersAsync()
        {
            var query = $"?api_token={_apiKey}";
            return await GetAsync<PlayerListResponse>($"/game_players.php{query}");
        }

        #endregion

        #region HTTP Helpers

        private async Task<T> GetAsync<T>(string requestUri)
        {
            var response = await _httpClient.GetAsync(requestUri);
            return await HandleResponse<T>(response);
        }

        private async Task<T> PostAsync<T>(string requestUri, object data)
        {
            var content = new StringContent(
                JsonSerializer.Serialize(data),
                Encoding.UTF8,
                "application/json");
                
            var response = await _httpClient.PostAsync(requestUri, content);
            return await HandleResponse<T>(response);
        }

        private async Task<T> PutAsync<T>(string requestUri, object data)
        {
            var content = data != null 
                ? new StringContent(JsonSerializer.Serialize(data), Encoding.UTF8, "application/json")
                : null;
                
            var response = await _httpClient.PutAsync(requestUri, content);
            return await HandleResponse<T>(response);
        }

        private async Task<T> HandleResponse<T>(HttpResponseMessage response)
        {
            var content = await response.Content.ReadAsStringAsync();
            
            if (!response.IsSuccessStatusCode)
            {
                string errorMessage = $"API request failed with status code {response.StatusCode}";
                
                try
                {
                    var errorResponse = JsonSerializer.Deserialize<ApiResponse>(content);
                    errorMessage = errorResponse?.Error ?? errorMessage;
                }
                catch
                {
                    // If we can't parse the error response, use the default message
                }
                
                throw new MichitaiException(errorMessage, response.StatusCode, content);
            }

            try
            {
                var options = new JsonSerializerOptions
                {
                    PropertyNameCaseInsensitive = true,
                    AllowTrailingCommas = true,
                    ReadCommentHandling = JsonCommentHandling.Skip
                };
                
                return JsonSerializer.Deserialize<T>(content, options) ?? 
                    throw new MichitaiException("Received null response from server");
            }
            catch (JsonException ex)
            {
                throw new MichitaiException("Failed to deserialize server response", ex);
            }
        }

        #endregion
    }

    #region Data Models

    public class ApiResponse
    {
        [JsonPropertyName("success")]
        public bool Success { get; set; }
        
        [JsonPropertyName("error")]
        public string Error { get; set; }
        
        [JsonPropertyName("message")]
        public string Message { get; set; }
    }

    public class GameDataResponse : ApiResponse
    {
        [JsonPropertyName("type")]
        public string Type { get; set; }
        
        [JsonPropertyName("game_id")]
        public int GameId { get; set; }
        
        [JsonPropertyName("data")]
        public Dictionary<string, object> Data { get; set; } = new();
    }

    public class PlayerRegistrationResponse : ApiResponse
    {
        [JsonPropertyName("game_player_token")]
        public string GamePlayerToken { get; set; }
        
        [JsonPropertyName("player_id")]
        public int PlayerId { get; set; }
        
        [JsonPropertyName("player_name")]
        public string PlayerName { get; set; }
        
        [JsonPropertyName("game_id")]
        public int GameId { get; set; }
    }

    public class PlayerDataResponse : ApiResponse
    {
        [JsonPropertyName("type")]
        public string Type { get; set; }
        
        [JsonPropertyName("player_id")]
        public int PlayerId { get; set; }
        
        [JsonPropertyName("player_name")]
        public string PlayerName { get; set; }
        
        [JsonPropertyName("data")]
        public Dictionary<string, object> Data { get; set; } = new();
    }

    public class PlayerListResponse : ApiResponse
    {
        [JsonPropertyName("count")]
        public int Count { get; set; }
        
        [JsonPropertyName("players")]
        public List<PlayerInfo> Players { get; set; } = new();
    }

    public class PlayerInfo
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }
        
        [JsonPropertyName("player_name")]
        public string PlayerName { get; set; }
        
        [JsonPropertyName("is_active")]
        public bool IsActive { get; set; }
        
        [JsonPropertyName("last_login")]
        public DateTime? LastLogin { get; set; }
        
        [JsonPropertyName("created_at")]
        public DateTime CreatedAt { get; set; }
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