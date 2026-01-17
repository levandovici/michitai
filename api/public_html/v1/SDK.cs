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
            return Send<GameDataResponse>(HttpMethod.Get, Url("game_data.php"));
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
                Url("time.php")
            );
        }

        // ------------------------------------
        // GAME ROOM
        // ------------------------------------

        public Task<RoomCreateResponse> CreateRoomAsync(
            string gamePlayerToken,
            string roomName,
            string? password = null,
            int maxPlayers = 4)
        {
            return Send<RoomCreateResponse>(
                HttpMethod.Post,
                Url("game_room.php/rooms", $"&game_player_token={gamePlayerToken}"),
                new
                {
                    room_name = roomName,
                    password = password,
                    max_players = maxPlayers
                }
            );
        }

        public Task<RoomListResponse> GetRoomsAsync()
        {
            return Send<RoomListResponse>(
                HttpMethod.Get,
                Url("game_room.php/rooms")
            );
        }

        public Task<RoomJoinResponse> JoinRoomAsync(
            string gamePlayerToken,
            string roomId,
            string? password = null)
        {
            return Send<RoomJoinResponse>(
                HttpMethod.Post,
                Url($"game_room.php/rooms/{roomId}/join", $"&game_player_token={gamePlayerToken}"),
                password != null ? new { password = password } : new { password = "" }
            );
        }

        public Task<RoomLeaveResponse> LeaveRoomAsync(string gamePlayerToken)
        {
            return Send<RoomLeaveResponse>(
                HttpMethod.Post,
                Url("game_room.php/rooms/leave", $"&game_player_token={gamePlayerToken}")
            );
        }

        public Task<RoomPlayersResponse> GetRoomPlayersAsync(string gamePlayerToken)
        {
            return Send<RoomPlayersResponse>(
                HttpMethod.Get,
                Url("game_room.php/players", $"&game_player_token={gamePlayerToken}")
            );
        }

        public Task<HeartbeatResponse> SendHeartbeatAsync(string gamePlayerToken)
        {
            return Send<HeartbeatResponse>(
                HttpMethod.Post,
                Url("game_room.php/players/heartbeat", $"&game_player_token={gamePlayerToken}")
            );
        }

        public Task<ActionSubmitResponse> SubmitActionAsync(
            string gamePlayerToken,
            string actionType,
            object requestData)
        {
            return Send<ActionSubmitResponse>(
                HttpMethod.Post,
                Url("game_room.php/actions", $"&game_player_token={gamePlayerToken}"),
                new
                {
                    action_type = actionType,
                    request_data = requestData
                }
            );
        }

        public Task<ActionPollResponse> PollActionsAsync(string gamePlayerToken)
        {
            return Send<ActionPollResponse>(
                HttpMethod.Get,
                Url("game_room.php/actions/poll", $"&game_player_token={gamePlayerToken}")
            );
        }

        public Task<ActionPendingResponse> GetPendingActionsAsync(string gamePlayerToken)
        {
            return Send<ActionPendingResponse>(
                HttpMethod.Get,
                Url("game_room.php/actions/pending", $"&game_player_token={gamePlayerToken}")
            );
        }

        public Task<ActionCompleteResponse> CompleteActionAsync(
            string actionId,
            string gamePlayerToken,
            string status,
            object? responseData = null)
        {
            return Send<ActionCompleteResponse>(
                HttpMethod.Post,
                Url($"game_room.php/actions/{actionId}/complete", $"&game_player_token={gamePlayerToken}"),
                new
                {
                    status = status,
                    response_data = responseData
                }
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

    public class RoomCreateResponse
    {
        public bool Success { get; set; }
        public string Room_id { get; set; }
        public string Room_name { get; set; }
        public bool Is_host { get; set; }
    }

    public class RoomShort
    {
        public string Room_id { get; set; }
        public string Room_name { get; set; }
        public int Max_players { get; set; }
        public int Current_players { get; set; }
        public int Has_password { get; set; }
    }

    public class RoomListResponse
    {
        public bool Success { get; set; }
        public List<RoomShort> Rooms { get; set; }
    }

    public class RoomJoinResponse
    {
        public bool Success { get; set; }
        public string Room_id { get; set; }
        public string Message { get; set; }
    }

    public class RoomPlayer
    {
        public string Player_id { get; set; }
        public string Player_name { get; set; }
        public int Is_host { get; set; }
        public int Is_online { get; set; }
    }

    public class RoomPlayersResponse
    {
        public bool Success { get; set; }
        public List<RoomPlayer> Players { get; set; }
        public string Last_updated { get; set; }
    }

    public class RoomLeaveResponse
    {
        public bool Success { get; set; }
        public string Message { get; set; }
    }

    public class HeartbeatResponse
    {
        public bool Success { get; set; }
        public string Status { get; set; }
    }

    public class ActionSubmitResponse
    {
        public bool Success { get; set; }
        public string Action_id { get; set; }
        public string Status { get; set; }
    }

    public class ActionInfo
    {
        public string Action_id { get; set; }
        public string Action_type { get; set; }
        public string? Response_data { get; set; }
        public string Status { get; set; }
    }

    public class ActionPollResponse
    {
        public bool Success { get; set; }
        public List<ActionInfo> Actions { get; set; }
    }

    public class PendingAction
    {
        public string Action_id { get; set; }
        public string Player_id { get; set; }
        public string Action_type { get; set; }
        public string Request_data { get; set; }
        public string Created_at { get; set; }
        public string Player_name { get; set; }
    }

    public class ActionPendingResponse
    {
        public bool Success { get; set; }
        public List<PendingAction> Actions { get; set; }
    }

    public class ActionCompleteResponse
    {
        public bool Success { get; set; }
        public string Message { get; set; }
    }
}
