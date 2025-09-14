#nullable enable
using System;
using System.Threading.Tasks;
using Michitai.SDK;

namespace Michitai.Example
{
    /// <summary>
    /// Example class demonstrating how to use the Michitai SDK
    /// </summary>
    public class Game
    {
        private readonly MichitaiClient _apiClient;

        public Game(string apiKey)
        {
            if (string.IsNullOrEmpty(apiKey))
                throw new ArgumentException("API key cannot be null or empty", nameof(apiKey));
                
            // Initialize the API client with your API key
            _apiClient = new MichitaiClient(apiKey);
            
            // Subscribe to authentication events
            _apiClient.OnAuthenticationRequired += OnAuthenticationRequired!;
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
            catch (MichitaiException ex)
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
            
            // You might want to handle retry logic or exit the application
            // Environment.Exit(1); // Uncomment to exit on authentication failure
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
}
