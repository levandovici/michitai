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
        private const string API_BASE_URL = "https://api.michitai.com/v1";
        private readonly string _apiKey;
        private readonly string _projectName;
        
        /// <summary>
        /// Event raised when authentication is required
        /// </summary>
        public event Action OnAuthenticationRequired;

        /// <summary>
        /// Initialize a new instance of the MichitaiClient with API key authentication
        /// </summary>
        /// <param name="apiKey">Your Michitai API key</param>
        /// <exception cref="ArgumentException">Thrown when API key is null or empty</exception>
        public MichitaiClient(string apiKey, string projectName = "default")
        {
            if (string.IsNullOrEmpty(apiKey))
                throw new ArgumentException("API key is required", nameof(apiKey));
            if (string.IsNullOrEmpty(projectName))
                throw new ArgumentException("Project name is required", nameof(projectName));

            _apiKey = apiKey.Trim();
            _projectName = projectName.Trim();
            
            // Configure HTTP client with handler
            var handler = new HttpClientHandler
            {
                UseDefaultCredentials = true,
                AllowAutoRedirect = false,
                UseCookies = false
            };
            
            _httpClient = new HttpClient(handler);
            
            // Clear and set default headers
            _httpClient.DefaultRequestHeaders.Clear();
            
            // Set content type and accept headers
            _httpClient.DefaultRequestHeaders.Accept.Clear();
            _httpClient.DefaultRequestHeaders.Accept.Add(
                new MediaTypeWithQualityHeaderValue("application/json"));
                
            // Add custom headers - using TryAddWithoutValidation to prevent duplicates
            _httpClient.DefaultRequestHeaders.TryAddWithoutValidation("X-Requested-With", "XMLHttpRequest");
            _httpClient.DefaultRequestHeaders.TryAddWithoutValidation("X-API-Key", _apiKey);
            _httpClient.DefaultRequestHeaders.TryAddWithoutValidation("X-Project-Name", _projectName);
            
            // Set base address to avoid URL concatenation issues
            _httpClient.BaseAddress = new Uri(API_BASE_URL);
        }

        #region Game Data

        /// <summary>
        /// Get game data
        /// </summary>
        /// <returns>Game data</returns>
        public async Task<GameData> GetGameDataAsync()
        {
            try 
            {
                // Log request details
                var relativeUri = "game_data.php";
                var requestUri = new Uri(_httpClient.BaseAddress, relativeUri);
                
                Console.WriteLine($"[DEBUG] Making GET request to: {requestUri}");
                Console.WriteLine($"[DEBUG] Base Address: {_httpClient.BaseAddress}");
                Console.WriteLine($"[DEBUG] Headers:");
                foreach (var header in _httpClient.DefaultRequestHeaders)
                {
                    Console.WriteLine($"  {header.Key}: {string.Join(", ", header.Value)}");
                }

                // Create request message for more control
                using var request = new HttpRequestMessage(HttpMethod.Get, relativeUri);
                
                // Add headers to the request
                request.Headers.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));
                request.Headers.Add("X-Requested-With", "XMLHttpRequest");
                
                // Make the request with a timeout
                using var cts = new CancellationTokenSource(TimeSpan.FromSeconds(30));
                var response = await _httpClient.SendAsync(request, cts.Token);
                
                // Log the actual request that was sent
                Console.WriteLine($"[DEBUG] Request URI: {request.RequestUri}");
                Console.WriteLine($"[DEBUG] Request Headers: {string.Join(", ", request.Headers.Select(h => $"{h.Key}={string.Join(",", h.Value)}"))}");
                
                // Read response content
                var responseContent = await response.Content.ReadAsStringAsync();
                var responseHeaders = string.Join("\n  ", 
                    response.Headers.Select(h => $"{h.Key}: {string.Join(", ", h.Value)}"));
                
                Console.WriteLine($"[DEBUG] Response Status: {(int)response.StatusCode} {response.StatusCode}");
                Console.WriteLine($"[DEBUG] Response Headers:\n  {responseHeaders}");
                Console.WriteLine($"[DEBUG] Response Content (first 1000 chars):\n{responseContent.Substring(0, Math.Min(1000, responseContent.Length))}");
                
                if (!response.IsSuccessStatusCode)
                {
                    var errorMessage = $"API request failed with status code {response.StatusCode}.";
                    if (!string.IsNullOrEmpty(responseContent))
                    {
                        errorMessage += $"\nResponse: {responseContent}";
                    }
                    throw new MichitaiException(errorMessage);
                }
                
                // Try to parse the response
                try 
                {
                    var options = new JsonSerializerOptions
                    {
                        PropertyNameCaseInsensitive = true,
                        AllowTrailingCommas = true,
                        ReadCommentHandling = JsonCommentHandling.Skip
                    };
                    
                    var result = JsonSerializer.Deserialize<GameData>(responseContent, options);
                    if (result == null)
                    {
                        throw new MichitaiException("Received null response from server");
                    }
                    return result;
                }
                catch (JsonException ex)
                {
                    Console.WriteLine($"[ERROR] JSON Deserialization Error: {ex.Message}");
                    Console.WriteLine($"[ERROR] Response Content Type: {response.Content.Headers.ContentType}");
                    Console.WriteLine($"[ERROR] Response Content (first 1000 chars):\n{responseContent.Substring(0, Math.Min(1000, responseContent.Length))}");
                    throw new MichitaiException("Failed to deserialize server response. The server may be returning an error page instead of JSON.", ex);
                }
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Unexpected error in GetGameDataAsync: {ex}");
                throw;
            }
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
                Console.WriteLine($"GET Request: {url}");
                
                var response = await _httpClient.GetAsync(url);
                var responseContent = await response.Content.ReadAsStringAsync();
                
                Console.WriteLine($"Status Code: {(int)response.StatusCode} {response.StatusCode}");
                Console.WriteLine($"Response Headers: {string.Join(", ", response.Headers.Select(h => $"{h.Key}={string.Join(",", h.Value)}"))}");
                Console.WriteLine($"Response Content (first 500 chars): {responseContent.Substring(0, Math.Min(500, responseContent.Length))}");
                
                if (!response.IsSuccessStatusCode)
                {
                    throw new MichitaiException($"API request failed with status code {response.StatusCode}. Response: {responseContent}");
                }
                
                try 
                {
                    return JsonSerializer.Deserialize<T>(responseContent);
                }
                catch (JsonException ex)
                {
                    Console.WriteLine($"JSON Deserialization Error: {ex.Message}");
                    Console.WriteLine($"Response Content: {responseContent}");
                    throw new MichitaiException("Failed to deserialize response", ex);
                }
            }
            catch (HttpRequestException ex)
            {
                Console.WriteLine($"HTTP Request Error: {ex}");
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
