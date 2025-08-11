# Blockly Puzzle Constructor - Developer Guide

## Overview
The Blockly Puzzle Constructor is a visual programming interface integrated into the Michitai multiplayer game platform. It allows users to create game logic using drag-and-drop blocks, similar to Scratch, with the logic stored as JSON in the MySQL database.

## Architecture

### Frontend Components
- **puzzle_constructor.html** - Main HTML page with Blockly workspace
- **js/puzzle_constructor.js** - JavaScript logic for Blockly integration
- **Custom Blockly Toolbox** - Predefined blocks for game logic

### Backend Components
- **GameManager.php** - Extended with logic management endpoints
- **MySQL Database** - Games table with JSON storage for logic

### Database Schema
```sql
-- Main games table stores logic in settings field
ALTER TABLE games ADD COLUMN IF NOT EXISTS settings JSON;

-- Optional versioning table
CREATE TABLE game_logic_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    version_number INT NOT NULL,
    logic_json JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    description TEXT
);

-- Optional simulation tracking
CREATE TABLE game_logic_simulations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    logic_json JSON NOT NULL,
    simulation_result JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT
);
```

## API Endpoints

### Save Logic
- **Endpoint**: `POST /api/game/saveLogic`
- **Headers**: `X-API-Token: {user_token}`
- **Body**: 
```json
{
    "game_id": 123,
    "logic_json": {...},
    "description": "Updated player movement logic"
}
```

### Get Logic
- **Endpoint**: `GET /api/game/getLogic?game_id=123`
- **Headers**: `X-API-Token: {user_token}`
- **Response**:
```json
{
    "success": true,
    "logic": {...},
    "last_modified": "2024-01-15T10:30:00Z"
}
```

### Export Logic
- **Endpoint**: `GET /api/game/exportLogic?game_id=123`
- **Headers**: `X-API-Token: {user_token}`
- **Response**: JSON file download

### Import Logic
- **Endpoint**: `POST /api/game/importLogic`
- **Headers**: `X-API-Token: {user_token}`
- **Body**: Form data with JSON file

### Simulate Logic
- **Endpoint**: `POST /api/game/simulateLogic`
- **Headers**: `X-API-Token: {user_token}`
- **Body**:
```json
{
    "logic_json": {...},
    "test_data": {...}
}
```

## Block Categories

### 1. Triggers
- **game_start** - Executes when game begins
- **player_join** - Executes when player joins
- **player_leave** - Executes when player leaves
- **timer_trigger** - Executes on timer intervals

### 2. Actions
- **send_message** - Send message to players
- **update_score** - Modify player scores
- **move_player** - Change player position
- **end_game** - Terminate game session

### 3. Conditions
- **if_player_count** - Check number of players
- **if_score_greater** - Compare scores
- **if_time_elapsed** - Check game duration

### 4. Data
- **get_player_data** - Retrieve player information
- **set_game_variable** - Store game state
- **get_game_variable** - Retrieve game state

## Deployment Instructions

### 1. Prerequisites
- PHP 8.0+
- MySQL 8.0+
- Web server (Apache/Nginx)
- Existing Michitai platform authentication

### 2. File Deployment
```bash
# Copy files to web directory
cp puzzle_constructor.html /path/to/public_html/
cp js/puzzle_constructor.js /path/to/public_html/js/
```

### 3. Database Setup
```sql
-- Run the schema updates
SOURCE database/blockly_logic_schema.sql;
```

### 4. API Configuration
- Ensure GameManager.php includes logic endpoints
- Verify API base URL in puzzle_constructor.js
- Test authentication integration

### 5. Navigation Integration
The "Builder" button has been added to:
- index.html (main page)
- game_manager.html
- game_constructor.html

## Adding Custom Blocks

### 1. Define Block in Toolbox
```javascript
// In puzzle_constructor.js, add to toolbox JSON
{
    "kind": "block",
    "type": "custom_action_block"
}
```

### 2. Create Block Definition
```javascript
Blockly.Blocks['custom_action_block'] = {
    init: function() {
        this.appendDummyInput()
            .appendField("Custom Action");
        this.setPreviousStatement(true, null);
        this.setNextStatement(true, null);
        this.setColour(160);
        this.setTooltip("Performs a custom action");
    }
};
```

### 3. Add Code Generator
```javascript
Blockly.JavaScript['custom_action_block'] = function(block) {
    return 'customAction();\n';
};
```

## Usage Guide

### 1. Accessing the Constructor
- Click the "Builder" button in the navigation menu
- Or navigate directly to `/puzzle_constructor.html`

### 2. Authentication
- The system uses existing Michitai authentication
- API tokens are stored in localStorage
- No separate login required

### 3. Creating Logic
1. Select a game from the dropdown
2. Drag blocks from the toolbox to the workspace
3. Connect blocks to create logic flow
4. Use the toolbar to save, load, or test logic

### 4. Workspace Features
- **Auto-save**: Logic saved every 60 seconds
- **Undo/Redo**: Navigate through changes
- **Zoom**: Scale workspace view
- **Clear**: Remove all blocks
- **Export/Import**: Share logic as JSON files

### 5. Testing Logic
- Click "Simulate" to test logic without saving
- View simulation results in the status panel
- Fix any validation errors before saving

## Best Practices

### 1. Logic Organization
- Use comments to document complex logic
- Group related blocks together
- Keep logic flows readable and maintainable

### 2. Performance
- Avoid deeply nested conditions
- Use efficient trigger combinations
- Test logic with realistic player counts

### 3. Security
- All logic is validated server-side
- User permissions enforced via API tokens
- JSON schema validation prevents malicious code

## Troubleshooting

### Common Issues
1. **Authentication Errors**: Check API token in localStorage
2. **Save Failures**: Verify game ownership and permissions
3. **Block Errors**: Ensure all required fields are filled
4. **Simulation Failures**: Check logic syntax and connections

### Debug Mode
Enable debug logging in puzzle_constructor.js:
```javascript
const DEBUG_MODE = true;
```

## Support
For technical support or feature requests, contact the Michitai development team.
