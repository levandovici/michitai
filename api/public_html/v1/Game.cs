using System;
using System.Collections.Generic;
using System.Threading.Tasks;
using Michitai.SDK;

namespace Michitai.Example
{
    public class Game
    {
        private readonly MichitaiClient _client;
        private string _playerToken;

        public Game(string apiKey)
        {
            _client = new MichitaiClient(apiKey);
            _client.OnAuthenticationRequired += OnAuthenticationRequired;
        }

        public async Task RunExample()
        {
            try
            {
                Console.WriteLine("=== Michitai Game Example ===");
                Console.WriteLine("1. Registering a new player...");
                
                // Register a new player
                var registerResponse = await _client.RegisterPlayerAsync("TestPlayer", new Dictionary<string, object>
                {
                    ["level"] = 1,
                    ["score"] = 0,
                    ["inventory"] = new[] { "sword", "shield" }
                });

                if (!registerResponse.Success)
                {
                    Console.WriteLine($"Failed to register player: {registerResponse.Error}");
                    return;
                }

                _playerToken = registerResponse.GamePlayerToken;
                Console.WriteLine($"Player registered! ID: {registerResponse.PlayerId}, Token: {_playerToken}");

                // Authenticate the player
                Console.WriteLine("\n2. Authenticating player...");
                var authResponse = await _client.AuthenticatePlayerAsync(_playerToken);
                if (!authResponse.Success)
                {
                    Console.WriteLine($"Authentication failed: {authResponse.Error}");
                    return;
                }

                Console.WriteLine($"Authenticated as {authResponse.PlayerName} (ID: {authResponse.PlayerId})");

                // Get game data
                Console.WriteLine("\n3. Getting game data...");
                var gameData = await _client.GetGameDataAsync();
                if (gameData.Success)
                {
                    Console.WriteLine($"Game data: {System.Text.Json.JsonSerializer.Serialize(gameData.Data)}");
                }
                else
                {
                    Console.WriteLine($"Failed to get game data: {gameData.Error}");
                }

                // Get player data
                Console.WriteLine("\n4. Getting player data...");
                var playerData = await _client.GetPlayerDataAsync();
                if (playerData.Success)
                {
                    Console.WriteLine($"Player data: {System.Text.Json.JsonSerializer.Serialize(playerData.Data)}");
                }
                else
                {
                    Console.WriteLine($"Failed to get player data: {playerData.Error}");
                }

                // Update player data
                Console.WriteLine("\n5. Updating player data...");
                var updateResponse = await _client.UpdatePlayerDataAsync(new Dictionary<string, object>
                {
                    ["level"] = 2,
                    ["score"] = 100,
                    ["last_played"] = DateTime.UtcNow
                });

                if (updateResponse.Success)
                {
                    Console.WriteLine("Player data updated successfully!");
                }
                else
                {
                    Console.WriteLine($"Failed to update player data: {updateResponse.Error}");
                }

                // List all players (admin only)
                Console.WriteLine("\n6. Listing all players...");
                var playersResponse = await _client.ListPlayersAsync();
                if (playersResponse.Success)
                {
                    Console.WriteLine($"Found {playersResponse.Count} players:");
                    foreach (var player in playersResponse.Players)
                    {
                        Console.WriteLine($"- {player.PlayerName} (ID: {player.Id}, Active: {player.IsActive})");
                    }
                }
                else
                {
                    Console.WriteLine($"Failed to list players: {playersResponse.Error}");
                }
            }
            catch (Exception ex)
            {
                Console.WriteLine($"An error occurred: {ex.Message}");
                if (ex.InnerException != null)
                {
                    Console.WriteLine($"Inner exception: {ex.InnerException.Message}");
                }
            }
        }

        private void OnAuthenticationRequired()
        {
            Console.WriteLine("\n=== AUTHENTICATION REQUIRED ===");
            Console.WriteLine("Please provide valid API credentials.");
            // In a real app, you would prompt the user for credentials here
        }

        public static async Task Main(string[] args)
        {
            if (args.Length == 0)
            {
                Console.WriteLine("Please provide your API key as a command line argument.");
                return;
            }

            var game = new Game(args[0]);
            await game.RunExample();
            
            Console.WriteLine("\nPress any key to exit...");
            Console.ReadKey();
        }
    }
}