using System;
using System.Threading.Tasks;

namespace Michitai.Example
{
    /// <summary>
    /// Example class demonstrating how to use the Michitai SDK
    /// </summary>
    public class Game
    {
        private readonly Michitai.SDK.MichitaiClient _apiClient;

        public Game(string apiKey)
        {
            // Initialize the API client with your API key
            _apiClient = new Michitai.SDK.MichitaiClient(apiKey);
            
            // Subscribe to authentication events
            _apiClient.OnAuthenticationRequired += OnAuthenticationRequired;
        }

        /// <summary>
        /// Example method to demonstrate game data operations
        /// </summary>
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
            Console.WriteLine("Michitai Game Example");
            Console.WriteLine("=====================\n");

            // Replace with your actual API key
            string apiKey = "your-api-key-here";
            
            var game = new Game(apiKey);
            await game.RunGameExample();

            Console.WriteLine("\nPress any key to exit...");
            Console.ReadKey();
        }
    }
}
