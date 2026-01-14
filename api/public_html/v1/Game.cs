using System;
using System.Threading.Tasks;
using System.Text.Json;
using michitai;

public class Game
{
    private static GameSDK sdk;

    public static async Task Main()
    {
        Console.WriteLine("=== MICHITAI Game SDK Usage Example ===\n");

        // 1️⃣ Initialize SDK
        sdk = new GameSDK("YOUR_API_TOKEN");
        Console.WriteLine("[INIT] SDK initialized\n");

        // 2️⃣ Register Player
        Console.WriteLine("[PLAYER] Registering new player...");
        var reg = await sdk.RegisterPlayer("TestPlayer", new
        {
            level = 1,
            score = 0,
            inventory = new[] { "sword", "shield" }
        });

        string playerToken = reg.Private_key;
        int playerId = int.Parse(reg.Player_id);

        Console.WriteLine($"[PLAYER] Registered: ID={playerId}, Token={playerToken}\n");

        // 3️⃣ Authenticate Player
        Console.WriteLine("[PLAYER] Authenticating player...");
        var auth = await sdk.AuthenticatePlayer(playerToken);

        if (auth.Success)
        {
            var pdata = auth.Player.Player_data;
            int level = pdata.ContainsKey("level") ? ((JsonElement)pdata["level"]).GetInt32() : 0;

            Console.WriteLine($"[PLAYER] Authenticated: {auth.Player.Player_name} (Level={level})\n");
        }
        else
        {
            Console.WriteLine("[PLAYER] Authentication failed\n");
        }

        // 4️⃣ List all players
        Console.WriteLine("[ADMIN] Fetching all players...");
        var allPlayers = await sdk.GetAllPlayers();
        Console.WriteLine($"[ADMIN] Total players: {allPlayers.Count}");
        foreach (var p in allPlayers.Players)
        {
            Console.WriteLine($" - ID={p.Id}, Name={p.Player_name}, Active={p.Is_active}");
        }
        Console.WriteLine();

        // 5️⃣ Get global game data
        Console.WriteLine("[GAME] Loading game data...");
        var gameData = await sdk.GetGameData();
        Console.WriteLine($"[GAME] Game ID={gameData.Game_id}, Settings={gameData.Data["game_settings"]}\n");

        // 6️⃣ Update global game data
        Console.WriteLine("[GAME] Updating game settings...");
        var updateGame = await sdk.UpdateGameData(new
        {
            game_settings = new { difficulty = "hard", max_players = 10 },
            last_updated = DateTime.UtcNow.ToString("o")
        });
        Console.WriteLine($"[GAME] {updateGame.Message} at {updateGame.Updated_at}\n");

        // 7️⃣ Get player-specific data
        Console.WriteLine("[PLAYER] Loading player data...");
        var playerData = await sdk.GetPlayerData(playerToken);

        var pDataDict = playerData.Data;
        int playerLevel = pDataDict.ContainsKey("level") ? ((JsonElement)pDataDict["level"]).GetInt32() : 0;
        int playerScore = pDataDict.ContainsKey("score") ? ((JsonElement)pDataDict["score"]).GetInt32() : 0;
        string[] inventory = pDataDict.ContainsKey("inventory")
            ? JsonSerializer.Deserialize<string[]>(((JsonElement)pDataDict["inventory"]).GetRawText())
            : new string[0];

        Console.WriteLine($"[PLAYER] Level={playerLevel}, Score={playerScore}, Inventory=[{string.Join(", ", inventory)}]\n");

        // 8️⃣ Update player data
        Console.WriteLine("[PLAYER] Updating player progress...");
        var updatedPlayer = await sdk.UpdatePlayerData(playerToken, new
        {
            level = 2,
            score = 100,
            inventory = new[] { "sword", "shield", "potion" },
            last_played = DateTime.UtcNow.ToString("o")
        });
        Console.WriteLine($"[PLAYER] {updatedPlayer.Message} at {updatedPlayer.Updated_at}\n");

        Console.WriteLine("=== Demo Complete ===");
    }
}
