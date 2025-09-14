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
