# API Documentation

## Overview

The Multiplayer API Web Constructor provides a comprehensive REST API for managing users, games, players, subscriptions, and system monitoring. All endpoints require HTTPS and use JSON for data exchange.

## Base URL
```
https://yourdomain.com/api
```

## Authentication

### API Token Authentication
Most endpoints require authentication via API token in the header:
```http
X-API-Token: your-api-token-here
```

### Rate Limiting
API calls are limited based on subscription plan:
- **Free**: 1,000 calls/day
- **Standard**: 10,000 calls/day  
- **Pro**: 50,000 calls/day

Rate limit headers are included in responses:
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1640995200
```

## Data Types

The API supports cross-platform data types compatible with JavaScript, PHP, and C#:

| Type | Description | Example |
|------|-------------|---------|
| Boolean | True/false value | `true` |
| Char | Single character | `"A"` |
| Byte | 8-bit integer (0-255) | `255` |
| Short | 16-bit integer | `32767` |
| Integer | 32-bit integer | `2147483647` |
| Long | 64-bit integer | `9223372036854775807` |
| Float | 32-bit floating point | `3.14159` |
| Double | 64-bit floating point | `2.718281828459045` |
| String | Text value | `"Hello World"` |
| Array | List of values | `[1, 2, 3]` |
| Enum | Predefined value | `"active"` |

## Error Handling

All endpoints return consistent error responses:

```json
{
  "error": "Error message",
  "code": "ERROR_CODE",
  "details": "Additional error details"
}
```

Common HTTP status codes:
- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden (rate limited)
- `404` - Not Found
- `429` - Too Many Requests
- `500` - Internal Server Error

## Authentication Endpoints

### Register User
Create a new user account.

**Endpoint:** `POST /register`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123!"
}
```

**Response:**
```json
{
  "user_id": 123,
  "email": "user@example.com",
  "api_token": "abc123def456...",
  "plan_type": "Free",
  "created_at": "2023-12-01T10:00:00Z"
}
```

### Login User
Authenticate existing user and get API token.

**Endpoint:** `POST /login`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123!"
}
```

**Response:**
```json
{
  "user_id": 123,
  "email": "user@example.com",
  "api_token": "abc123def456...",
  "plan_type": "Standard",
  "expires_at": "2024-01-01T00:00:00Z"
}
```

### Get User Profile
Get current user information.

**Endpoint:** `GET /user`

**Headers:** `X-API-Token: your-token`

**Response:**
```json
{
  "user_id": 123,
  "email": "user@example.com",
  "plan_type": "Standard",
  "memory_used_mb": 150.5,
  "memory_limit_mb": 10240,
  "api_calls_today": 45,
  "api_calls_limit": 10000,
  "subscription_expires": "2024-01-01T00:00:00Z"
}
```

## Game Management Endpoints

### Create Game
Create a new multiplayer game with puzzle logic.

**Endpoint:** `POST /game/create`

**Headers:** `X-API-Token: your-token`

**Request:**
```json
{
  "name": "My Puzzle Game",
  "description": "A challenging puzzle game",
  "json_structure": {
    "logic": [
      {
        "type": "if",
        "condition": "player.level > 5",
        "actions": [
          {"type": "set", "variable": "difficulty", "value": "hard"}
        ]
      },
      {
        "type": "for",
        "variable": "i",
        "start": 0,
        "end": 10,
        "actions": [
          {"type": "call", "function": "spawnEnemy"}
        ]
      }
    ],
    "data_types": ["Integer", "String", "Boolean", "Array"],
    "functions": ["Random", "Power", "Sqrt"],
    "operators": ["+", "-", "*", "/", "==", "<", ">", "&&", "||"]
  },
  "json_properties": {
    "max_players": 100,
    "game_mode": "multiplayer",
    "difficulty": "medium",
    "time_limit": 3600
  }
}
```

**Response:**
```json
{
  "game_id": 456,
  "name": "My Puzzle Game",
  "memory_used_mb": 2.5,
  "created_at": "2023-12-01T10:00:00Z",
  "validation_result": {
    "valid": true,
    "warnings": [],
    "generated_code": {
      "javascript": "if (player.level > 5) { difficulty = 'hard'; }",
      "php": "if ($player['level'] > 5) { $difficulty = 'hard'; }",
      "csharp": "if (player.Level > 5) { difficulty = \"hard\"; }"
    }
  }
}
```

### Get Game
Retrieve game details and logic.

**Endpoint:** `GET /game/get/{game_id}`

**Headers:** `X-API-Token: your-token`

**Response:**
```json
{
  "game_id": 456,
  "name": "My Puzzle Game",
  "description": "A challenging puzzle game",
  "json_structure": { /* game logic */ },
  "json_properties": { /* game properties */ },
  "memory_used_mb": 2.5,
  "player_count": 25,
  "room_count": 5,
  "created_at": "2023-12-01T10:00:00Z",
  "updated_at": "2023-12-01T15:30:00Z"
}
```

### Update Game
Update existing game configuration.

**Endpoint:** `POST /game/update`

**Headers:** `X-API-Token: your-token`

**Request:**
```json
{
  "game_id": 456,
  "name": "Updated Game Name",
  "json_structure": { /* updated logic */ },
  "json_properties": { /* updated properties */ }
}
```

### Delete Game
Delete a game and all associated data.

**Endpoint:** `DELETE /game/delete/{game_id}`

**Headers:** `X-API-Token: your-token`

**Response:**
```json
{
  "success": true,
  "message": "Game deleted successfully",
  "memory_freed_mb": 2.5
}
```

## Player Management Endpoints

### Create Player
Create a new player for a game.

**Endpoint:** `POST /player/create`

**Headers:** `X-API-Token: your-token`

**Request:**
```json
{
  "game_id": 456
}
```

**Response:**
```json
{
  "player_id": "player_789abc",
  "password_guid": "def456ghi789",
  "game_id": 456,
  "memory_used_mb": 0.1,
  "created_at": "2023-12-01T10:00:00Z"
}
```

### Authenticate Player
Authenticate a player for game access.

**Endpoint:** `POST /player/auth`

**Request:**
```json
{
  "player_id": "player_789abc",
  "password_guid": "def456ghi789"
}
```

**Response:**
```json
{
  "player_id": "player_789abc",
  "game_id": 456,
  "authenticated": true,
  "json_data": { /* current player data */ },
  "last_active": "2023-12-01T15:30:00Z"
}
```

### Update Player Data
Update player's game data with type validation.

**Endpoint:** `POST /player/update`

**Headers:** `X-API-Token: your-token`

**Request:**
```json
{
  "player_id": "player_789abc",
  "json_data": {
    "level": {"type": "Integer", "value": 5},
    "score": {"type": "Long", "value": 1500},
    "health": {"type": "Float", "value": 85.5},
    "name": {"type": "String", "value": "Player One"},
    "active": {"type": "Boolean", "value": true},
    "inventory": {"type": "Array", "value": ["sword", "potion", "key"]},
    "status": {"type": "Enum", "value": "playing"}
  }
}
```

**Response:**
```json
{
  "player_id": "player_789abc",
  "memory_used_mb": 0.3,
  "validation_result": {
    "valid": true,
    "type_errors": [],
    "converted_data": {
      "javascript": { /* JS-compatible data */ },
      "php": { /* PHP-compatible data */ },
      "csharp": { /* C#-compatible data */ }
    }
  },
  "updated_at": "2023-12-01T15:30:00Z"
}
```

### Get Player Data
Retrieve player information and game data.

**Endpoint:** `GET /player/get/{player_id}`

**Headers:** `X-API-Token: your-token`

**Response:**
```json
{
  "player_id": "player_789abc",
  "game_id": 456,
  "json_data": { /* player data with types */ },
  "memory_used_mb": 0.3,
  "last_active": "2023-12-01T15:30:00Z",
  "created_at": "2023-12-01T10:00:00Z"
}
```

## Subscription Endpoints

### Create Subscription
Create a new PayPal subscription.

**Endpoint:** `POST /subscribe`

**Headers:** `X-API-Token: your-token`

**Request:**
```json
{
  "plan_type": "Standard"
}
```

**Response:**
```json
{
  "subscription_id": "I-BW452GLLEP1G",
  "plan_type": "Standard",
  "approval_url": "https://www.paypal.com/webapps/billing/subscriptions/...",
  "payment_method": "paypal",
  "fallback_options": {
    "paynet": {
      "available": true,
      "instructions": "Visit paynet.md for local payment"
    },
    "maib_transfer": {
      "available": true,
      "bank_details": {
        "bank_name": "Moldova Agroindbank",
        "swift": "AGRNMD2X",
        "instructions": "Contact support for transfer details"
      }
    }
  }
}
```

### Get Subscription Status
Check current subscription status.

**Endpoint:** `GET /subscription/status`

**Headers:** `X-API-Token: your-token`

**Response:**
```json
{
  "subscription_id": "I-BW452GLLEP1G",
  "plan_type": "Standard",
  "status": "ACTIVE",
  "next_billing_time": "2024-01-01T00:00:00Z",
  "payment_method": "paypal",
  "auto_renewal": true
}
```

### Cancel Subscription
Cancel an active subscription.

**Endpoint:** `POST /subscription/cancel`

**Headers:** `X-API-Token: your-token`

**Request:**
```json
{
  "reason": "No longer needed"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Subscription cancelled successfully",
  "effective_date": "2024-01-01T00:00:00Z",
  "plan_downgrade": "Free"
}
```

## Room Management Endpoints

### Create Room
Create a game room for players.

**Endpoint:** `POST /room/create`

**Headers:** `X-API-Token: your-token`

**Request:**
```json
{
  "game_id": 456,
  "name": "Room 1",
  "max_players": 10,
  "is_private": false
}
```

### Join Room
Add a player to a room.

**Endpoint:** `POST /room/join`

**Headers:** `X-API-Token: your-token`

**Request:**
```json
{
  "room_id": 789,
  "player_id": "player_789abc"
}
```

## Community Endpoints

### Create Community
Create a community for players.

**Endpoint:** `POST /community/create`

**Headers:** `X-API-Token: your-token`

**Request:**
```json
{
  "name": "Puzzle Masters",
  "description": "Community for puzzle game enthusiasts",
  "is_public": true
}
```

## Monitoring Endpoints

### User Statistics
Get current user's usage statistics.

**Endpoint:** `GET /monitor/user`

**Headers:** `X-API-Token: your-token`

**Response:**
```json
{
  "user_id": 123,
  "plan_type": "Standard",
  "memory_used_mb": 150.5,
  "memory_limit_mb": 10240,
  "storage_used_mb": 2048.7,
  "storage_limit_mb": 10240,
  "api_calls_today": 45,
  "api_calls_limit": 10000,
  "games_count": 5,
  "games_limit": 20,
  "players_count": 125,
  "players_limit": 1000,
  "subscription_expires": "2024-01-01T00:00:00Z"
}
```

### System Statistics
Get overall system statistics (admin only).

**Endpoint:** `GET /monitor/system`

**Headers:** `X-API-Token: your-token`

**Response:**
```json
{
  "total_users": 150,
  "total_games": 450,
  "total_players": 12500,
  "total_memory_mb": 15360.5,
  "total_storage_mb": 51200.8,
  "memory_limit_mb": 204800,
  "storage_limit_mb": 204800,
  "api_calls_today": 125000,
  "active_subscriptions": {
    "Free": 100,
    "Standard": 35,
    "Pro": 15
  },
  "system_health": "healthy"
}
```

## Webhook Endpoints

### PayPal Webhook
Receive PayPal subscription events.

**Endpoint:** `POST /webhook/paypal`

**Headers:** 
```http
Content-Type: application/json
PAYPAL-TRANSMISSION-ID: transmission-id
PAYPAL-CERT-ID: cert-id
PAYPAL-AUTH-ALGO: SHA256withRSA
PAYPAL-TRANSMISSION-SIG: signature
```

**Supported Events:**
- `BILLING.SUBSCRIPTION.ACTIVATED`
- `PAYMENT.SALE.COMPLETED`
- `BILLING.SUBSCRIPTION.CANCELLED`
- `BILLING.SUBSCRIPTION.SUSPENDED`

## Code Generation

The puzzle logic constructor generates equivalent code in multiple languages:

### JavaScript Example
```javascript
// Generated from puzzle logic
if (player.level > 5) {
    difficulty = 'hard';
    for (let i = 0; i < 10; i++) {
        spawnEnemy();
    }
}
```

### PHP Example
```php
// Generated from puzzle logic
if ($player['level'] > 5) {
    $difficulty = 'hard';
    for ($i = 0; $i < 10; $i++) {
        spawnEnemy();
    }
}
```

### C# Example
```csharp
// Generated from puzzle logic
if (player.Level > 5) {
    difficulty = "hard";
    for (int i = 0; i < 10; i++) {
        SpawnEnemy();
    }
}
```

## SDK Examples

### JavaScript/Node.js
```javascript
const api = new MultiplayerAPI('https://yourdomain.com/api', 'your-api-token');

// Create a game
const game = await api.createGame({
    name: 'My Game',
    json_structure: { /* logic */ },
    json_properties: { /* properties */ }
});

// Create a player
const player = await api.createPlayer(game.game_id);

// Update player data
await api.updatePlayer(player.player_id, {
    level: {type: 'Integer', value: 5},
    score: {type: 'Long', value: 1500}
});
```

### PHP
```php
$api = new MultiplayerAPI('https://yourdomain.com/api', 'your-api-token');

// Create a game
$game = $api->createGame([
    'name' => 'My Game',
    'json_structure' => [/* logic */],
    'json_properties' => [/* properties */]
]);

// Create a player
$player = $api->createPlayer($game['game_id']);

// Update player data
$api->updatePlayer($player['player_id'], [
    'level' => ['type' => 'Integer', 'value' => 5],
    'score' => ['type' => 'Long', 'value' => 1500]
]);
```

### C#
```csharp
var api = new MultiplayerAPI("https://yourdomain.com/api", "your-api-token");

// Create a game
var game = await api.CreateGameAsync(new GameRequest {
    Name = "My Game",
    JsonStructure = new { /* logic */ },
    JsonProperties = new { /* properties */ }
});

// Create a player
var player = await api.CreatePlayerAsync(game.GameId);

// Update player data
await api.UpdatePlayerAsync(player.PlayerId, new Dictionary<string, object> {
    ["level"] = new { type = "Integer", value = 5 },
    ["score"] = new { type = "Long", value = 1500 }
});
```

## Best Practices

### Performance
- Use appropriate data types for optimal memory usage
- Implement client-side caching for frequently accessed data
- Batch API calls when possible
- Monitor rate limits and implement exponential backoff

### Security
- Always use HTTPS in production
- Store API tokens securely
- Validate all input data
- Implement proper error handling
- Use webhook verification for PayPal events

### Data Management
- Use cross-platform compatible data types
- Validate data types before API calls
- Implement proper memory management
- Clean up unused players and games regularly

This completes the comprehensive API documentation for the Multiplayer API Web Constructor.
