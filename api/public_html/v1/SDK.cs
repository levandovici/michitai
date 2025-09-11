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
        private string _apiToken;
        private string _sessionToken;

        /// <summary>
        /// Event triggered when authentication is required
        /// </summary>
        public event Action OnAuthenticationRequired;

        /// <summary>
        /// Indicates if the client is currently authenticated
        /// </summary>
        public bool IsAuthenticated => !string.IsNullOrEmpty(_apiToken);

        /// <summary>
        /// Initialize a new instance of the MichitaiClient
        /// </summary>
        public MichitaiClient()
        {
            _httpClient = new HttpClient();
            _httpClient.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));
            _httpClient.DefaultRequestHeaders.Add("X-Requested-With", "XMLHttpRequest");
        }

        #region Authentication

        /// <summary>
        /// Register a new player
        /// </summary>
        /// <param name="username">Player's username</param>
        /// <param name="email">Player's email</param>
        /// <param name="password">Player's password</param>
        /// <returns>Player registration response</returns>
        public async Task<PlayerRegistrationResponse> RegisterPlayerAsync(string username, string email, string password)
        {
            var request = new
            {
                username,
                email,
                password
            };

            var response = await PostAsync<PlayerRegistrationResponse>("/games/players", request);
            
            if (response != null && !string.IsNullOrEmpty(response.ApiToken))
            {
                _apiToken = response.ApiToken;
                _sessionToken = response.SessionToken;
                UpdateAuthorizationHeader();
            }
            
            return response;
        }

        /// <summary>
        /// Authenticate a player
        /// </summary>
        /// <param name="email">Player's email</param>
        /// <param name="password">Player's password</param>
        /// <returns>Authentication response</returns>
        public async Task<AuthResponse> LoginAsync(string email, string password)
        {
            var request = new
            {
                email,
                password
            };

            var response = await PostAsync<AuthResponse>("/games/players/login", request);
            
            if (response != null)
            {
                _apiToken = response.ApiToken;
                _sessionToken = response.SessionToken;
                UpdateAuthorizationHeader();
            }
            
            return response;
        }

        /// <summary>
        /// Set authentication tokens (useful for restoring session)
        /// </summary>
        /// <param name="apiToken">API token</param>
        /// <param name="sessionToken">Session token</param>
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
        public Dictionary<string, object> Settings { get; set; }
        public DateTime LastUpdated { get; set; }
    }

    public class PlayerData
    {
        public string PlayerId { get; set; }
        public string Username { get; set; }
        public string Email { get; set; }
        public int Level { get; set; }
        public int Experience { get; set; }
        public Dictionary<string, object> Stats { get; set; }
        public Dictionary<string, object> Inventory { get; set; }
        public DateTime LastActive { get; set; }
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
