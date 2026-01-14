using System;
using System.Collections.Generic;
using System.Net.Http;
using System.Text;
using System.Text.Json;
using System.Text.Json.Serialization;
using System.Threading.Tasks;

namespace michitai
{
    public class GameSDK
    {
        private readonly string _apiToken;
        private readonly string _apiPrivateToken;
        private readonly string _baseUrl;
        private static readonly HttpClient _http = new HttpClient();

        private readonly JsonSerializerOptions _jsonOptions = new JsonSerializerOptions
        {
            PropertyNameCaseInsensitive = true,
            DefaultIgnoreCondition = JsonIgnoreCondition.WhenWritingNull
        };

        public GameSDK(string apiToken, string apiPrivateToken, string baseUrl = "https://api.michitai.com/v1/php/")
        {
            _apiToken = apiToken;
            _apiPrivateToken = apiPrivateToken;
            _baseUrl = baseUrl.EndsWith("/") ? baseUrl : baseUrl + "/";
        }

        private string Url(string endpoint, string extra = "")
        {
            return $"{_baseUrl}{endpoint}?api_token={_apiToken}{extra}";
        }

        private async Task<T> Send<T>(HttpMethod method, string url, object body = null)
        {
            var req = new HttpRequestMessage(method, url);

            if (body != null)
            {
                string json = JsonSerializer.Serialize(body, _jsonOptions);
                req.Content = new StringContent(json, Encoding.UTF8, "application/json");
            }

            var res = await _http.SendAsync(req);
            string str = await res.Content.ReadAsStringAsync();

            return JsonSerializer.Deserialize<T>(str, _jsonOptions);
        }

        // ------------------------------------
        // PLAYER API
        // ------------------------------------

        public Task<PlayerRegisterResponse> RegisterPlayer(string name, object playerData)
        {
            return Send<PlayerRegisterResponse>(
                HttpMethod.Post,
                Url("game_players.php"),
                new { player_name = name, player_data = playerData }
            );
        }

        public Task<PlayerAuthResponse> AuthenticatePlayer(string playerToken)
        {
            return Send<PlayerAuthResponse>(
                HttpMethod.Put,
                Url("game_players.php", $"&game_player_token={playerToken}")
            );
        }

        public Task<PlayerListResponse> GetAllPlayers()
        {
            return Send<PlayerListResponse>(HttpMethod.Get, Url("game_players.php", $"&api_private_token={_apiPrivateToken}"));
        }

        // ------------------------------------
        // GAME DATA
        // ------------------------------------

        public Task<GameDataResponse> GetGameData()
        {
            return Send<GameDataResponse>(HttpMethod.Get, Url("game_data.php", $"&api_private_token={_apiPrivateToken}"));
        }

        public Task<SuccessResponse> UpdateGameData(object data)
        {
            return Send<SuccessResponse>(HttpMethod.Put, Url("game_data.php", $"&api_private_token={_apiPrivateToken}"), data);
        }

        // ------------------------------------
        // PLAYER DATA
        // ------------------------------------

        public Task<PlayerDataResponse> GetPlayerData(string playerToken)
        {
            return Send<PlayerDataResponse>(
                HttpMethod.Get,
                Url("game_data.php", $"&game_player_token={playerToken}")
            );
        }

        public Task<SuccessResponse> UpdatePlayerData(string playerToken, object data)
        {
            return Send<SuccessResponse>(
                HttpMethod.Put,
                Url("game_data.php", $"&game_player_token={playerToken}"),
                data
            );
        }

        // ------------------------------------
        // SERVER TIME
        // ------------------------------------

        public Task<ServerTimeResponse> GetServerTime()
        {
            return Send<ServerTimeResponse>(
                HttpMethod.Get,
                $"{_baseUrl}time.php?api_key={_apiToken}"
            );
        }
    }

    // ------------------------------------
    // MODELS
    // ------------------------------------

    public class PlayerRegisterResponse
    {
        public bool Success { get; set; }
        public string Player_id { get; set; }
        public string Private_key { get; set; }
        public string Player_name { get; set; }
        public int Game_id { get; set; }
    }

    public class PlayerAuthResponse
    {
        public bool Success { get; set; }
        public PlayerInfo Player { get; set; }
    }

    public class PlayerListResponse
    {
        public bool Success { get; set; }
        public int Count { get; set; }
        public List<PlayerShort> Players { get; set; }
    }

    public class PlayerShort
    {
        public int Id { get; set; }
        public string Player_name { get; set; }
        public int Is_active { get; set; }
        public string Last_login { get; set; }
        public string Created_at { get; set; }
    }

    public class PlayerInfo
    {
        public int Id { get; set; }
        public int Game_id { get; set; }
        public string Player_name { get; set; }
        public Dictionary<string, object> Player_data { get; set; }
        public int Is_active { get; set; }
        public string Last_login { get; set; }
        public string Created_at { get; set; }
        public string Updated_at { get; set; }
    }

    public class GameDataResponse
    {
        public bool Success { get; set; }
        public string Type { get; set; }
        public int Game_id { get; set; }
        public Dictionary<string, object> Data { get; set; }
    }

    public class PlayerDataResponse
    {
        public bool Success { get; set; }
        public string Type { get; set; }
        public int Player_id { get; set; }
        public string Player_name { get; set; }
        public Dictionary<string, object> Data { get; set; }
    }

    public class SuccessResponse
    {
        public bool Success { get; set; }
        public string Message { get; set; }
        public string Updated_at { get; set; }
    }

    public class ServerTimeResponse
    {
        public bool Success { get; set; }
        public string Utc { get; set; }
        public long Timestamp { get; set; }
        public string Readable { get; set; }
    }
}
