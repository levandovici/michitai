# Multiplayer API - C# SDK and Examples

This folder contains downloadable resources for Multiplayer API integration.

## Available Downloads

### C# SDK (`csharp-sdk.zip`)
Complete C# SDK for integrating Multiplayer API into your Unity or .NET applications.

**Features:**
- Full API client with async/await support
- Strong typing for all data models
- Built-in authentication handling
- Error handling and retry logic
- Unity-compatible (2021.3+)
- .NET Standard 2.0 support

**Installation:**
1. Download `csharp-sdk.zip`
2. Extract to your project folder
3. Add reference to `MultiplayerAPI.dll`
4. Initialize with your API token

**Quick Start:**
```csharp
using MultiplayerAPI;

var client = new MultiplayerAPIClient("your_api_token_here");
var games = await client.GetGamesAsync();
```

### Constructor Examples (`constructor-examples.zip`)
Sample logic constructor configurations and generated code examples.

**Includes:**
- Player authentication logic
- Game state management
- Timer and trigger examples
- Data validation patterns
- Cross-platform data type usage

## Documentation

For complete documentation, visit: [https://api.michitai.com/docs.html](../docs.html)

## Support

- Email: support@michitai.com
- Phone: +373 788 14810
- Documentation: [docs.html](../docs.html)

## License

The C# SDK is provided under a limited license for Multiplayer API integration only.
Redistribution or modification is not permitted without written consent.

---
© 2025 Nichita Levandovici. All rights reserved.
